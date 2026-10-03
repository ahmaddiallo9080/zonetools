<?php

namespace App\Http\Controllers;

use App\Http\Requests\RapportRequest;
use App\Models\Agent;
use App\Models\Lot;
use App\Models\Rapport;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RapportController extends Controller
{
    private const TRIS = ['code', 'date_rapport', 'quantite_vendue', 'quantite_defectueuse', 'montant_vendu', 'montant_verse', 'ecart'];

    public function index(Request $request): View
    {
        $tri = in_array($request->query('tri'), self::TRIS, true) ? $request->query('tri') : 'date_rapport';
        $sens = $request->query('sens') === 'asc' ? 'asc' : 'desc';

        $requete = Rapport::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($s) => $s
                ->where('code', 'like', '%' . $request->query('q') . '%')
                ->orWhereHas('lot', fn ($l) => $l->where('code', 'like', '%' . $request->query('q') . '%'))))
            ->when(ctype_digit((string) $request->query('site')), fn ($q) => $q->whereHas('lot', fn ($l) => $l->where('site_id', $request->query('site'))))
            ->when(ctype_digit((string) $request->query('agent')), fn ($q) => $q->whereHas('lot', fn ($l) => $l->where('agent_id', $request->query('agent'))))
            ->when($request->query('ecart') === 'manquant', fn ($q) => $q->where('ecart', '<', 0))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('date_rapport', '>=', $request->query('du')))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('date_rapport', '<=', $request->query('au')));

        // Totaux selon les filtres
        $totaux = (clone $requete)->selectRaw('
            COUNT(*) as rapports,
            COALESCE(SUM(quantite_vendue), 0) as vendus,
            COALESCE(SUM(quantite_defectueuse), 0) as defectueux,
            COALESCE(SUM(quantite_rendue), 0) as rendus,
            COALESCE(SUM(montant_vendu), 0) as montant_vendu,
            COALESCE(SUM(commission_agent + commission_superviseur), 0) as commissions,
            COALESCE(SUM(CASE WHEN ecart < 0 THEN -ecart ELSE 0 END), 0) as manquants
        ')->first();

        $rapports = $requete
            ->with(['lot.site', 'lot.agent'])
            ->orderBy($tri, $sens)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('rapports.index', [
            'rapports' => $rapports,
            'totaux' => $totaux,
            'sites' => Site::orderBy('nom')->get(['id', 'nom']),
            'agents' => Agent::orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']),
            'lotsEnAttente' => Lot::enCours()->count(),
            'tri' => $tri,
            'sens' => $sens,
        ]);
    }

    public function create(Lot $lot): View|RedirectResponse
    {
        if ($lot->rapport) {
            return redirect()->route('rapports.show', $lot->rapport);
        }
        if ($lot->statut !== Lot::EN_COURS) {
            return redirect()->route('lots.show', $lot)->with('error', 'Seul un lot en cours peut recevoir un rapport.');
        }

        return view('rapports.create', $this->donneesFormulaire($lot, new Rapport([
            'date_rapport' => now(),
            'commission_agent_deduite' => (bool) config('zonetools.commission_agent_deduite'),
        ])) + ['codeSuggere' => Rapport::prochainCode()]);
    }

    public function store(RapportRequest $request, Lot $lot): RedirectResponse
    {
        if ($lot->rapport || $lot->statut !== Lot::EN_COURS) {
            return redirect()->route('lots.show', $lot)->with('error', 'Ce lot a déjà un rapport ou n\'est plus en cours.');
        }

        $rapport = DB::transaction(function () use ($request, $lot) {
            $rapport = new Rapport($request->safe()->except('lignes'));
            $rapport->lot_id = $lot->id;
            $rapport->commission_agent_deduite = (bool) config('zonetools.commission_agent_deduite');
            $rapport->save();

            $this->enregistrerLignes($rapport, $lot, $request->validated('lignes'));
            $lot->update(['statut' => Lot::TERMINE]);

            return $rapport;
        });

        return redirect()
            ->route('rapports.show', $rapport)
            ->with('success', "Rapport du lot {$lot->code} enregistré : le lot est terminé.");
    }

    public function show(Rapport $rapport): View
    {
        $rapport->load(['lot.site', 'lot.agent', 'lot.superviseur', 'lignes.forfait']);

        return view('rapports.show', compact('rapport'));
    }

    public function edit(Rapport $rapport): View
    {
        $rapport->load('lignes');

        return view('rapports.edit', $this->donneesFormulaire($rapport->lot, $rapport));
    }

    public function update(RapportRequest $request, Rapport $rapport): RedirectResponse
    {
        DB::transaction(function () use ($request, $rapport) {
            $rapport->fill($request->safe()->except('lignes'))->save();
            $this->enregistrerLignes($rapport, $rapport->lot, $request->validated('lignes'));
        });

        return redirect()
            ->route('rapports.show', $rapport)
            ->with('success', "Le rapport {$rapport->code} a été mis à jour.");
    }

    public function destroy(Rapport $rapport): RedirectResponse
    {
        $lot = $rapport->lot;

        DB::transaction(function () use ($rapport, $lot) {
            $rapport->delete();
            $lot->update(['statut' => Lot::EN_COURS]);
        });

        return redirect()
            ->route('lots.show', $lot)
            ->with('success', "Le rapport a été supprimé : le lot {$lot->code} est de nouveau en cours.");
    }

    /* ----------------------------------------------------------------
     |  Outils
     | ---------------------------------------------------------------- */

    /** Enregistre une ligne de rapport par ligne du lot, puis recalcule les totaux. */
    private function enregistrerLignes(Rapport $rapport, Lot $lot, array $saisies): void
    {
        foreach ($lot->lignes as $ligneLot) {
            $rapport->lignes()->updateOrCreate(
                ['lot_ligne_id' => $ligneLot->id],
                [
                    'forfait_id' => $ligneLot->forfait_id,
                    'quantite_remise' => $ligneLot->quantite,
                    'prix_unitaire' => $ligneLot->prix_unitaire,
                    'vendus' => (int) $saisies[$ligneLot->id]['vendus'],
                    'defectueux' => (int) $saisies[$ligneLot->id]['defectueux'],
                ]
            );
        }

        $rapport->recalculer();
    }

    private function donneesFormulaire(Lot $lot, Rapport $rapport): array
    {
        $lot->load(['site', 'agent', 'superviseur', 'lignes.forfait']);
        $existantes = $rapport->exists ? $rapport->lignes->keyBy('lot_ligne_id') : collect();

        $lignes = $lot->lignes->map(fn ($l) => [
            'id' => $l->id,
            'forfait' => $l->forfait->nom,
            'couleur' => $l->forfait->couleur,
            'quantite' => $l->quantite,
            'prix' => $l->prix_unitaire,
            'vendus' => old("lignes.{$l->id}.vendus", $existantes->get($l->id)?->vendus ?? ''),
            'defectueux' => old("lignes.{$l->id}.defectueux", $existantes->get($l->id)?->defectueux ?? 0),
        ])->values();

        return [
            'lot' => $lot,
            'rapport' => $rapport,
            'config' => [
                'lignes' => $lignes,
                'tauxAgent' => (float) $lot->taux_agent,
                'tauxSuperviseur' => (float) $lot->taux_superviseur,
                'agentDeduit' => (bool) ($rapport->exists ? $rapport->commission_agent_deduite : config('zonetools.commission_agent_deduite')),
                'montantVerse' => old('montant_verse', $rapport->exists ? $rapport->montant_verse : ''),
            ],
        ];
    }
}
