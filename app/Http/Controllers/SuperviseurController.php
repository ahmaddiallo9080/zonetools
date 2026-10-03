<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuperviseurRequest;
use App\Models\Site;
use App\Models\Superviseur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SuperviseurController extends Controller
{
    private const TRIS = ['code', 'nom', 'telephone', 'sites_count', 'agents_count', 'taux_commission', 'statut'];

    public function index(Request $request): View
    {
        $tri = in_array($request->query('tri'), self::TRIS, true) ? $request->query('tri') : 'nom';
        $sens = $request->query('sens') === 'desc' ? 'desc' : 'asc';

        $superviseurs = Superviseur::query()
            ->withCount(['sites', 'agents'])
            ->recherche($request->query('q'))
            ->when(
                array_key_exists((string) $request->query('statut'), Superviseur::statuts()),
                fn ($q) => $q->where('statut', $request->query('statut'))
            )
            ->orderBy($tri, $sens)
            ->when($tri === 'nom', fn ($q) => $q->orderBy('prenom', $sens))
            ->paginate(15)
            ->withQueryString();

        $compteurs = Superviseur::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return view('superviseurs.index', compact('superviseurs', 'compteurs', 'tri', 'sens'));
    }

    public function create(): View
    {
        return view('superviseurs.create', [
            'superviseur' => new Superviseur(['statut' => 'actif']),
            'codeSuggere' => Superviseur::prochainCode(),
            'sites' => $this->sitesDisponibles(),
            'sitesSelectionnes' => [],
        ]);
    }

    public function store(SuperviseurRequest $request): RedirectResponse
    {
        $superviseur = DB::transaction(function () use ($request) {
            $superviseur = Superviseur::create($request->safe()->except('sites'));
            $this->affecterSites($superviseur, $request->validated('sites', []));

            return $superviseur;
        });

        return redirect()
            ->route('superviseurs.show', $superviseur)
            ->with('success', "Le superviseur « {$superviseur->nom_complet} » a été créé.");
    }

    public function show(Superviseur $superviseur, \App\Services\Commissions $commissions): View
    {
        $superviseur->load(['sites' => fn ($q) => $q->withCount('agents')->orderBy('nom')]);
        $agents = $superviseur->agents()->with('site')->orderBy('nom')->get();

        $statsLots = $superviseur->lots()->enCours()
            ->selectRaw('COUNT(*) as lots, COALESCE(SUM(quantite_totale), 0) as tickets, COALESCE(SUM(montant_total), 0) as montant')
            ->first();

        $commission = $commissions->pour($superviseur);

        return view('superviseurs.show', compact('superviseur', 'agents', 'statsLots', 'commission'));
    }

    public function edit(Superviseur $superviseur): View
    {
        return view('superviseurs.edit', [
            'superviseur' => $superviseur,
            'sites' => $this->sitesDisponibles(),
            'sitesSelectionnes' => $superviseur->sites()->pluck('id')->all(),
        ]);
    }

    public function update(SuperviseurRequest $request, Superviseur $superviseur): RedirectResponse
    {
        DB::transaction(function () use ($request, $superviseur) {
            $superviseur->update($request->safe()->except('sites'));
            $this->affecterSites($superviseur, $request->validated('sites', []));
        });

        return redirect()
            ->route('superviseurs.show', $superviseur)
            ->with('success', "Le superviseur « {$superviseur->nom_complet} » a été mis à jour.");
    }

    public function destroy(Superviseur $superviseur): RedirectResponse
    {
        if ($superviseur->lots()->enCours()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$superviseur->nom_complet} » : il supervise des lots en cours.");
        }

        DB::transaction(function () use ($superviseur) {
            // Ses sites se retrouvent sans superviseur
            $superviseur->sites()->update(['superviseur_id' => null]);
            $superviseur->delete();
        });

        return redirect()
            ->route('superviseurs.index')
            ->with('success', "Le superviseur « {$superviseur->nom_complet} » a été supprimé.");
    }

    /** Tous les sites, avec leur superviseur actuel (pour la liste à cocher). */
    private function sitesDisponibles()
    {
        return Site::with('superviseur')->orderBy('nom')->get();
    }

    /** Rattache exactement les sites cochés au superviseur. */
    private function affecterSites(Superviseur $superviseur, array $siteIds): void
    {
        $superviseur->sites()->whereNotIn('id', $siteIds)->update(['superviseur_id' => null]);

        if ($siteIds) {
            Site::whereIn('id', $siteIds)->update(['superviseur_id' => $superviseur->id]);
        }
    }
}
