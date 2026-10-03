<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForfaitRequest;
use App\Models\Forfait;
use App\Models\Lot;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ForfaitController extends Controller
{
    private const TRIS = ['code', 'nom', 'duree_minutes', 'nb_appareils', 'prix', 'sites_count', 'statut'];

    public function index(Request $request): View
    {
        $tri = in_array($request->query('tri'), self::TRIS, true) ? $request->query('tri') : 'duree_minutes';
        $sens = $request->query('sens') === 'desc' ? 'desc' : 'asc';

        $forfaits = Forfait::query()
            ->withCount('sites')
            ->recherche($request->query('q'))
            ->when(
                array_key_exists((string) $request->query('statut'), Forfait::STATUTS),
                fn ($q) => $q->where('statut', $request->query('statut'))
            )
            ->orderBy($tri, $sens)
            ->orderBy('prix')
            ->paginate(15)
            ->withQueryString();

        $compteurs = Forfait::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return view('forfaits.index', compact('forfaits', 'compteurs', 'tri', 'sens'));
    }

    public function create(): View
    {
        return view('forfaits.create', [
            'forfait' => new Forfait(['statut' => 'actif', 'duree_valeur' => 1, 'duree_unite' => 'heure', 'nb_appareils' => 1, 'couleur' => 'primary']),
            'codeSuggere' => Forfait::prochainCode(),
            'sites' => Site::orderBy('nom')->get(['id', 'nom']),
            'prixSites' => [],
        ]);
    }

    public function store(ForfaitRequest $request): RedirectResponse
    {
        $forfait = DB::transaction(function () use ($request) {
            $forfait = Forfait::create($request->safe()->except('prix_sites'));
            $forfait->sites()->sync($request->prixParSite());

            return $forfait;
        });

        return redirect()
            ->route('forfaits.show', $forfait)
            ->with('success', "Le forfait « {$forfait->nom} » a été créé.");
    }

    public function show(Forfait $forfait): View
    {
        // Prix appliqué sur chaque site (particulier ou prix de base)
        $forfait->load('sites');
        $sites = Site::orderBy('nom')->get()->map(fn (Site $site) => [
            'site' => $site,
            'prix' => $forfait->prixPour($site),
            'particulier' => $forfait->sites->contains('id', $site->id),
        ]);

        $statsLots = $forfait->lotLignes()
            ->whereHas('lot', fn ($q) => $q->where('statut', Lot::EN_COURS))
            ->selectRaw('COUNT(DISTINCT lot_id) as lots, COALESCE(SUM(quantite), 0) as tickets')
            ->first();

        return view('forfaits.show', compact('forfait', 'sites', 'statsLots'));
    }

    public function edit(Forfait $forfait): View
    {
        return view('forfaits.edit', [
            'forfait' => $forfait,
            'sites' => Site::orderBy('nom')->get(['id', 'nom']),
            'prixSites' => $forfait->sites->map(fn ($s) => ['site_id' => $s->id, 'prix' => $s->pivot->prix])->values()->all(),
        ]);
    }

    public function update(ForfaitRequest $request, Forfait $forfait): RedirectResponse
    {
        DB::transaction(function () use ($request, $forfait) {
            $forfait->update($request->safe()->except('prix_sites'));
            $forfait->sites()->sync($request->prixParSite());
        });

        return redirect()
            ->route('forfaits.show', $forfait)
            ->with('success', "Le forfait « {$forfait->nom} » a été mis à jour.");
    }

    public function destroy(Forfait $forfait): RedirectResponse
    {
        if ($forfait->lotLignes()->whereHas('lot', fn ($q) => $q->where('statut', Lot::EN_COURS))->exists()) {
            return back()->with('error', "Impossible de supprimer « {$forfait->nom} » : il est utilisé dans des lots en cours.");
        }

        $forfait->delete();

        return redirect()
            ->route('forfaits.index')
            ->with('success', "Le forfait « {$forfait->nom} » a été supprimé.");
    }
}
