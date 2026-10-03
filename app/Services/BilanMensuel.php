<?php

namespace App\Services;

use App\Models\Bilan;
use App\Models\Depense;
use App\Models\Lot;
use App\Models\Rapport;
use App\Models\RapportLigne;
use App\Models\Site;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcule le bilan d'un site (ou d'un ensemble de sites) pour un mois donné.
 *
 * - Ventes, tickets, commissions, argent versé : rapports dont la date est dans le mois.
 * - Dépenses : dépenses du site datées dans le mois.
 * - Lots remis : lots dont la date de remise est dans le mois.
 */
class BilanMensuel
{
    /** « 2026-09 » → 1er septembre 2026 (mois précédent par défaut). */
    public static function mois(?string $valeur): Carbon
    {
        try {
            return $valeur ? Carbon::createFromFormat('!Y-m', $valeur)->startOfMonth() : now()->subMonthNoOverflow()->startOfMonth();
        } catch (\Throwable) {
            return now()->subMonthNoOverflow()->startOfMonth();
        }
    }

    /** Chiffres calculés en direct pour un site (null = dépenses générales seules). */
    public function calculer(?Site $site, Carbon $mois): array
    {
        [$debut, $fin] = [$mois->copy()->startOfMonth(), $mois->copy()->endOfMonth()];

        $rapports = $site
            ? Rapport::with('lot.agent')
                ->whereBetween('date_rapport', [$debut->toDateString(), $fin->toDateString()])
                ->whereHas('lot', fn ($q) => $q->where('site_id', $site->id))
                ->get()
            : collect();

        $depenses = Depense::query()
            ->whereBetween('date_depense', [$debut->toDateString(), $fin->toDateString()])
            ->when($site, fn ($q) => $q->where('site_id', $site->id), fn ($q) => $q->whereNull('site_id'))
            ->orderBy('date_depense')
            ->get();

        $ventes = (int) $rapports->sum('montant_vendu');
        $commAgents = (int) $rapports->sum('commission_agent');
        $commSup = (int) $rapports->sum('commission_superviseur');
        $totalDepenses = (int) $depenses->sum('montant');

        return [
            'rapports' => $rapports->count(),
            'lots_remis' => $site ? Lot::where('site_id', $site->id)->where('statut', '!=', Lot::ANNULE)
                ->whereBetween('date_remise', [$debut->toDateString(), $fin->toDateString()])->count() : 0,
            'tickets_remis' => (int) $rapports->sum(fn ($r) => $r->quantite_remise),
            'vendus' => (int) $rapports->sum('quantite_vendue'),
            'defectueux' => (int) $rapports->sum('quantite_defectueuse'),
            'rendus' => (int) $rapports->sum('quantite_rendue'),
            'ventes' => $ventes,
            'commission_agents' => $commAgents,
            'commission_superviseur' => $commSup,
            'depenses' => $totalDepenses,
            'benefice' => $ventes - $commAgents - $commSup - $totalDepenses,
            'montant_verse' => (int) $rapports->sum('montant_verse'),
            'manquants' => (int) $rapports->sum(fn ($r) => max(0, -$r->ecart)),
            'details' => [
                'forfaits' => $this->parForfait($rapports),
                'agents' => $this->parAgent($rapports),
                'categories' => $depenses->groupBy('categorie')
                    ->map(fn ($g, $cat) => ['categorie' => $cat, 'label' => Depense::CATEGORIES[$cat] ?? $cat, 'montant' => (int) $g->sum('montant')])
                    ->sortByDesc('montant')->values()->all(),
                'depenses' => $depenses->map(fn (Depense $d) => [
                    'id' => $d->id, 'date' => $d->date_depense->format('Y-m-d'), 'libelle' => $d->libelle,
                    'categorie' => $d->categorie_label, 'montant' => $d->montant,
                ])->all(),
                'rapports' => $rapports->sortBy('date_rapport')->map(fn (Rapport $r) => [
                    'id' => $r->id, 'code' => $r->code, 'lot' => $r->lot->code, 'date' => $r->date_rapport->format('Y-m-d'),
                    'agent' => $r->lot->agent?->nom_complet, 'vendus' => $r->quantite_vendue,
                    'defectueux' => $r->quantite_defectueuse, 'ventes' => $r->montant_vendu, 'ecart' => $r->ecart,
                ])->values()->all(),
            ],
        ];
    }

    /** Bilan d'un site : clôturé (chiffres figés) ou calculé en direct. */
    public function pour(Site $site, Carbon $mois): array
    {
        $bilan = Bilan::where('site_id', $site->id)->whereDate('mois', $mois->toDateString())->first();

        return $bilan
            ? array_merge($bilan->only(Bilan::CHIFFRES), ['details' => $bilan->details, 'bilan' => $bilan])
            : array_merge($this->calculer($site, $mois), ['bilan' => null]);
    }

    /** Bilans de tous les sites ayant de l'activité (ou un bilan) sur le mois + dépenses générales. */
    public function reseau(Carbon $mois): array
    {
        $sites = Site::withTrashed()->orderBy('nom')->get()
            ->map(fn (Site $site) => ['site' => $site] + $this->pour($site, $mois))
            ->filter(fn ($l) => $l['bilan'] || $l['rapports'] || $l['depenses'] || $l['lots_remis'] || (! $l['site']->trashed() && $l['site']->statut !== 'inactif'))
            ->values();

        $generales = $this->calculer(null, $mois);

        $total = collect(Bilan::CHIFFRES)->mapWithKeys(fn ($c) => [$c => (int) $sites->sum($c)])->all();
        $total['depenses'] += $generales['depenses'];
        $total['benefice'] -= $generales['depenses'];

        return ['sites' => $sites, 'generales' => $generales, 'total' => $total];
    }

    /* ---------------------------------------------------------------- */

    private function parForfait(Collection $rapports): array
    {
        if ($rapports->isEmpty()) {
            return [];
        }

        return RapportLigne::with('forfait')->whereIn('rapport_id', $rapports->pluck('id'))->get()
            ->groupBy('forfait_id')
            ->map(fn ($g) => [
                'forfait' => $g->first()->forfait->nom,
                'remis' => (int) $g->sum('quantite_remise'),
                'vendus' => (int) $g->sum('vendus'),
                'defectueux' => (int) $g->sum('defectueux'),
                'rendus' => (int) $g->sum('rendus'),
                'ventes' => (int) $g->sum('montant_vendu'),
            ])
            ->sortByDesc('ventes')->values()->all();
    }

    private function parAgent(Collection $rapports): array
    {
        return $rapports->groupBy(fn ($r) => $r->lot->agent_id)
            ->map(fn ($g) => [
                'agent' => $g->first()->lot->agent?->nom_complet ?? '—',
                'rapports' => $g->count(),
                'vendus' => (int) $g->sum('quantite_vendue'),
                'defectueux' => (int) $g->sum('quantite_defectueuse'),
                'ventes' => (int) $g->sum('montant_vendu'),
                'commission' => (int) $g->sum('commission_agent'),
                'manquants' => (int) $g->sum(fn ($r) => max(0, -$r->ecart)),
            ])
            ->sortByDesc('ventes')->values()->all();
    }
}
