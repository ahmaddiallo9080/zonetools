<?php

namespace App\Http\Controllers;

use App\Http\Requests\LotRequest;
use App\Models\Agent;
use App\Models\Forfait;
use App\Models\Lot;
use App\Models\Site;
use App\Models\Superviseur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LotController extends Controller
{
    private const TRIS = ['code', 'date_remise', 'date_fin_prevue', 'quantite_totale', 'montant_total', 'statut'];

    public function index(Request $request): View
    {
        $tri = in_array($request->query('tri'), self::TRIS, true) ? $request->query('tri') : 'date_remise';
        $sens = $request->query('sens') === 'asc' ? 'asc' : 'desc';

        $requete = Lot::query()
            ->when($request->filled('q'), fn ($q) => $q->where('code', 'like', '%' . $request->query('q') . '%'))
            ->when(array_key_exists((string) $request->query('statut'), Lot::STATUTS), fn ($q) => $q->where('statut', $request->query('statut')))
            ->when(ctype_digit((string) $request->query('site')), fn ($q) => $q->where('site_id', $request->query('site')))
            ->when(ctype_digit((string) $request->query('agent')), fn ($q) => $q->where('agent_id', $request->query('agent')))
            ->when(ctype_digit((string) $request->query('superviseur')), fn ($q) => $q->where('superviseur_id', $request->query('superviseur')))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('date_remise', '>=', $request->query('du')))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('date_remise', '<=', $request->query('au')));

        // Totaux des lots en cours affichés (selon les filtres)
        $enCirculation = (clone $requete)->enCours()
            ->selectRaw('COUNT(*) as lots, COALESCE(SUM(quantite_totale), 0) as tickets, COALESCE(SUM(montant_total), 0) as montant')
            ->first();

        $lots = $requete
            ->with(['site', 'agent', 'superviseur'])
            ->orderBy($tri, $sens)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $compteurs = Lot::query()->selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return view('lots.index', [
            'lots' => $lots,
            'compteurs' => $compteurs,
            'enCirculation' => $enCirculation,
            'sites' => Site::orderBy('nom')->get(['id', 'nom']),
            'agents' => Agent::orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']),
            'superviseurs' => Superviseur::orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']),
            'tri' => $tri,
            'sens' => $sens,
        ]);
    }

    public function create(Request $request): View
    {
        $lot = new Lot([
            'date_remise' => now(),
            'site_id' => $request->integer('site') ?: null,
            'agent_id' => $request->integer('agent') ?: null,
        ]);

        // Pré-remplissage depuis la fiche d'un agent
        if ($lot->agent_id && ! $lot->site_id) {
            $lot->site_id = Agent::find($lot->agent_id)?->site_id;
        }

        return view('lots.create', $this->donneesFormulaire($lot) + [
            'codeSuggere' => Lot::prochainCode(),
        ]);
    }

    public function store(LotRequest $request): RedirectResponse
    {
        $lot = DB::transaction(function () use ($request) {
            $lot = new Lot($request->safe()->except('lignes'));
            $lot->statut = Lot::EN_COURS;
            $lot->superviseur_id = Site::find($request->validated('site_id'))->superviseur_id;
            $lot->save();

            $this->enregistrerLignes($lot, $request->validated('lignes'));

            return $lot;
        });

        return redirect()
            ->route('lots.show', $lot)
            ->with('success', "Le lot {$lot->code} a été créé : {$lot->quantite_totale} tickets remis à {$lot->agent->nom_complet}.");
    }

    public function show(Lot $lot): View
    {
        $lot->load(['site', 'agent', 'superviseur', 'lignes.forfait', 'rapport']);

        return view('lots.show', compact('lot'));
    }

    public function edit(Lot $lot): View|RedirectResponse
    {
        if (! $lot->estModifiable()) {
            return redirect()->route('lots.show', $lot)->with('error', 'Seul un lot en cours peut être modifié.');
        }

        $lot->load('lignes');

        return view('lots.edit', $this->donneesFormulaire($lot));
    }

    public function update(LotRequest $request, Lot $lot): RedirectResponse
    {
        if (! $lot->estModifiable()) {
            return redirect()->route('lots.show', $lot)->with('error', 'Seul un lot en cours peut être modifié.');
        }

        DB::transaction(function () use ($request, $lot) {
            $changementDeSite = (int) $lot->site_id !== (int) $request->validated('site_id');

            $lot->fill($request->safe()->except('lignes'));
            if ($changementDeSite) {
                $lot->superviseur_id = Site::find($lot->site_id)->superviseur_id;
            }
            $lot->save();

            $this->enregistrerLignes($lot, $request->validated('lignes'), $changementDeSite);
        });

        return redirect()
            ->route('lots.show', $lot)
            ->with('success', "Le lot {$lot->code} a été mis à jour.");
    }

    public function destroy(Lot $lot): RedirectResponse
    {
        if ($lot->statut === Lot::TERMINE) {
            return back()->with('error', 'Un lot terminé ne peut pas être supprimé : il contient un rapport de vente.');
        }

        $lot->delete();

        return redirect()
            ->route('lots.index')
            ->with('success', "Le lot {$lot->code} a été supprimé.");
    }

    /** Annuler un lot en cours, ou le réactiver s'il était annulé. */
    public function basculerAnnulation(Lot $lot): RedirectResponse
    {
        if ($lot->statut === Lot::TERMINE) {
            return back()->with('error', 'Un lot terminé ne peut pas être annulé.');
        }

        $lot->update(['statut' => $lot->statut === Lot::ANNULE ? Lot::EN_COURS : Lot::ANNULE]);

        return back()->with('success', $lot->statut === Lot::ANNULE
            ? "Le lot {$lot->code} a été annulé."
            : "Le lot {$lot->code} est de nouveau en cours.");
    }

    /* ----------------------------------------------------------------
     |  Outils
     | ---------------------------------------------------------------- */

    /**
     * Enregistre les lignes du lot. Le prix unitaire est figé :
     * une ligne existante garde son prix, sauf si le site a changé.
     */
    private function enregistrerLignes(Lot $lot, array $lignes, bool $recalculerPrix = false): void
    {
        $existantes = $lot->lignes()->get()->keyBy('forfait_id');
        $forfaits = Forfait::with('sites')->findMany(collect($lignes)->pluck('forfait_id'))->keyBy('id');
        $gardees = [];

        foreach ($lignes as $donnees) {
            $forfaitId = (int) $donnees['forfait_id'];
            $ligne = $existantes->get($forfaitId) ?? $lot->lignes()->make(['forfait_id' => $forfaitId]);

            if (! $ligne->exists || $recalculerPrix) {
                $ligne->prix_unitaire = $forfaits[$forfaitId]->prixPour($lot->site_id);
            }

            $ligne->quantite = (int) $donnees['quantite'];
            $ligne->save();
            $gardees[] = $ligne->id;
        }

        $lot->lignes()->whereNotIn('id', $gardees)->delete();
        $lot->recalculerTotaux();
    }

    /** Données nécessaires au formulaire (listes + tarifs pour le calcul en direct). */
    private function donneesFormulaire(Lot $lot): array
    {
        $sites = Site::with('superviseur')
            ->where(fn ($q) => $q->where('statut', '!=', 'inactif')->orWhere('id', $lot->site_id))
            ->orderBy('nom')
            ->get();

        $agents = Agent::whereNotNull('site_id')
            ->where(fn ($q) => $q->where('statut', 'actif')->orWhere('id', $lot->agent_id))
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $idsLignes = $lot->exists ? $lot->lignes->pluck('forfait_id')->all() : [];
        $forfaits = Forfait::with('sites')
            ->where(fn ($q) => $q->where('statut', 'actif')->orWhereIn('id', $idsLignes))
            ->orderBy('duree_minutes')
            ->get();

        // Tarifs : [forfait_id => ['base' => prix, 'sites' => [site_id => prix]]]
        $tarifs = $forfaits->mapWithKeys(fn (Forfait $f) => [$f->id => [
            'nom' => $f->nom,
            'base' => $f->prix,
            'sites' => $f->sites->mapWithKeys(fn ($s) => [$s->id => $s->pivot->prix]),
        ]]);

        // Prix déjà figés des lignes existantes
        $prixFiges = $lot->exists ? $lot->lignes->mapWithKeys(fn ($l) => [$l->forfait_id => $l->prix_unitaire]) : collect();

        $lignes = old('lignes', $lot->exists
            ? $lot->lignes->map(fn ($l) => ['forfait_id' => $l->forfait_id, 'quantite' => $l->quantite])->values()->all()
            : [['forfait_id' => '', 'quantite' => '']]);

        return [
            'lot' => $lot,
            'sites' => $sites,
            'agents' => $agents,
            'forfaits' => $forfaits,
            'config' => [
                'sites' => $sites->mapWithKeys(fn (Site $s) => [$s->id => [
                    'superviseur' => $s->superviseur?->nom_complet,
                    'taux' => (float) ($s->superviseur?->taux_commission ?? 0),
                ]]),
                'agents' => $agents->map(fn (Agent $a) => [
                    'id' => $a->id, 'nom' => $a->nom_complet, 'site_id' => $a->site_id, 'taux' => (float) $a->taux_commission,
                ])->values(),
                'tarifs' => $tarifs,
                'prixFiges' => $prixFiges,
                'siteInitial' => $lot->site_id,
            ],
            'lignes' => $lignes,
        ];
    }
}
