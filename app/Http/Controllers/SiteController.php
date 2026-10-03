<?php

namespace App\Http\Controllers;

use App\Http\Requests\SiteRequest;
use App\Models\Forfait;
use App\Models\Site;
use App\Models\Superviseur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    /** Colonnes autorisées pour le tri. */
    private const TRIS = ['code', 'nom', 'ville', 'statut', 'date_ouverture', 'created_at', 'agents_count'];

    public function index(Request $request): View
    {
        $tri = in_array($request->query('tri'), self::TRIS, true) ? $request->query('tri') : 'nom';
        $sens = $request->query('sens') === 'desc' ? 'desc' : 'asc';

        $sites = Site::query()
            ->with('superviseur')
            ->withCount('agents')
            ->recherche($request->query('q'))
            ->when($request->query('superviseur') === 'aucun', fn ($q) => $q->whereNull('superviseur_id'))
            ->when(ctype_digit((string) $request->query('superviseur')), fn ($q) => $q->where('superviseur_id', $request->query('superviseur')))
            ->when(
                array_key_exists((string) $request->query('statut'), Site::STATUTS),
                fn ($q) => $q->where('statut', $request->query('statut'))
            )
            ->orderBy($tri, $sens)
            ->paginate(15)
            ->withQueryString();

        // Compteurs par statut pour les cartes du haut
        $compteurs = Site::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return view('sites.index', [
            'sites' => $sites,
            'compteurs' => $compteurs,
            'superviseurs' => Superviseur::orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']),
            'tri' => $tri,
            'sens' => $sens,
        ]);
    }

    public function create(): View
    {
        return view('sites.create', [
            'site' => new Site(['statut' => Site::STATUT_ACTIF]),
            'codeSuggere' => Site::prochainCode(),
            'superviseurs' => $this->superviseurs(),
        ]);
    }

    public function store(SiteRequest $request): RedirectResponse
    {
        $site = Site::create($request->validated());

        return redirect()
            ->route('sites.show', $site)
            ->with('success', "Le site « {$site->nom} » a été créé.");
    }

    public function show(Site $site): View
    {
        $site->load(['superviseur', 'agents' => fn ($q) => $q->orderBy('nom')]);

        // Tarifs des forfaits actifs sur ce site
        $tarifs = Forfait::actifs()
            ->with(['sites' => fn ($q) => $q->where('sites.id', $site->id)])
            ->orderBy('duree_minutes')
            ->get()
            ->map(fn (Forfait $f) => [
                'forfait' => $f,
                'prix' => $f->prixPour($site),
                'particulier' => $f->sites->isNotEmpty(),
            ]);

        $lots = $site->lots()->with('agent')->latest('date_remise')->latest('id')->take(8)->get();

        $depenses = $site->depenses()->latest('date_depense')->latest('id')->take(5)->get();
        $depensesMois = (int) $site->depenses()->whereBetween('date_depense', [now()->startOfMonth(), now()->endOfMonth()])->sum('montant');

        return view('sites.show', compact('site', 'tarifs', 'lots', 'depenses', 'depensesMois'));
    }

    public function edit(Site $site): View
    {
        return view('sites.edit', [
            'site' => $site,
            'superviseurs' => $this->superviseurs(),
        ]);
    }

    public function update(SiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        return redirect()
            ->route('sites.show', $site)
            ->with('success', "Le site « {$site->nom} » a été mis à jour.");
    }

    public function destroy(Site $site): RedirectResponse
    {
        if ($site->lots()->enCours()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$site->nom} » : il a des lots de tickets en cours.");
        }

        // Les agents du site se retrouvent « sans site »
        $site->agents()->update(['site_id' => null]);
        $site->delete();

        return redirect()
            ->route('sites.index')
            ->with('success', "Le site « {$site->nom} » a été supprimé.");
    }

    /** Liste des superviseurs pour le menu déroulant. */
    private function superviseurs()
    {
        return Superviseur::orderBy('nom')->orderBy('prenom')->get();
    }
}
