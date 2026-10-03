<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use App\Models\Rapport;
use App\Models\RapportLigne;
use App\Models\Site;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Statistiques de vente calculées à partir des rapports de fin de lot
 * (date de référence : date du rapport).
 */
class StatistiqueController extends Controller
{
    /** Périodes rapides proposées dans le filtre. */
    public const PERIODES = [
        'mois' => 'Ce mois-ci',
        'mois_dernier' => 'Le mois dernier',
        '3_mois' => '3 derniers mois',
        '6_mois' => '6 derniers mois',
        'annee' => 'Cette année',
        'perso' => 'Personnalisée',
    ];

    public function index(Request $request): View
    {
        [$du, $au, $periode] = $this->periode($request);
        $siteId = ctype_digit((string) $request->query('site')) ? (int) $request->query('site') : null;

        // Rapports de la période (avec lot pour site / agent / superviseur)
        $rapports = Rapport::query()
            ->with(['lot.site', 'lot.agent', 'lot.superviseur'])
            ->whereBetween('date_rapport', [$du->toDateString(), $au->toDateString()])
            ->when($siteId, fn ($q) => $q->whereHas('lot', fn ($l) => $l->where('site_id', $siteId)))
            ->get();

        // Dépenses de la période (un site : ses dépenses ; tous : y compris les dépenses générales)
        $depenses = Depense::query()
            ->with('site')
            ->whereBetween('date_depense', [$du->toDateString(), $au->toDateString()])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->get();

        $parSite = $this->regrouper($rapports, fn ($r) => $r->lot->site);
        $depensesParSite = $depenses->whereNotNull('site_id')->groupBy('site_id')->map->sum('montant');

        // Bénéfice par site = net gérant du site − dépenses du site
        $beneficeParSite = $parSite->map(fn ($l) => (object) [
            'entite' => $l->entite,
            'net' => $l->ventes - $l->commissions,
            'depenses' => (int) ($depensesParSite[$l->entite->id] ?? 0),
            'benefice' => $l->ventes - $l->commissions - (int) ($depensesParSite[$l->entite->id] ?? 0),
        ]);
        // Sites qui ont des dépenses mais aucune vente sur la période
        foreach ($depensesParSite as $id => $montant) {
            if (! $beneficeParSite->contains(fn ($l) => $l->entite->id === $id)) {
                $site = $depenses->firstWhere('site_id', $id)->site;
                $beneficeParSite->push((object) ['entite' => $site, 'net' => 0, 'depenses' => (int) $montant, 'benefice' => -(int) $montant]);
            }
        }

        $kpis = $this->kpis($rapports);
        $kpis->depenses = (int) $depenses->sum('montant');
        $kpis->benefice = $kpis->net - $kpis->depenses;

        return view('statistiques.index', [
            'periode' => $periode,
            'du' => $du,
            'au' => $au,
            'siteId' => $siteId,
            'sites' => Site::orderBy('nom')->get(['id', 'nom']),
            'kpis' => $kpis,
            'evolution' => $this->evolution($rapports, $du, $au),
            'parSite' => $parSite,
            'beneficeParSite' => $beneficeParSite->sortByDesc('benefice')->values(),
            'depensesParCategorie' => $depenses->groupBy('categorie')->map->sum('montant')->sortDesc(),
            'parAgent' => $this->regrouper($rapports, fn ($r) => $r->lot->agent),
            'parSuperviseur' => $this->regrouper($rapports, fn ($r) => $r->lot->superviseur),
            'parForfait' => $this->parForfait($rapports),
        ]);
    }

    /* ----------------------------------------------------------------
     |  Calculs
     | ---------------------------------------------------------------- */

    /** Dates de début / fin selon le filtre. */
    private function periode(Request $request): array
    {
        $periode = array_key_exists((string) $request->query('periode'), self::PERIODES) ? $request->query('periode') : '6_mois';
        $aujourdhui = now();

        [$du, $au] = match ($periode) {
            'mois' => [$aujourdhui->copy()->startOfMonth(), $aujourdhui->copy()->endOfMonth()],
            'mois_dernier' => [$aujourdhui->copy()->subMonthNoOverflow()->startOfMonth(), $aujourdhui->copy()->subMonthNoOverflow()->endOfMonth()],
            '3_mois' => [$aujourdhui->copy()->subMonthsNoOverflow(2)->startOfMonth(), $aujourdhui->copy()->endOfMonth()],
            'annee' => [$aujourdhui->copy()->startOfYear(), $aujourdhui->copy()->endOfYear()],
            'perso' => [
                $this->date($request->query('du')) ?? $aujourdhui->copy()->startOfMonth(),
                $this->date($request->query('au')) ?? $aujourdhui->copy(),
            ],
            default => [$aujourdhui->copy()->subMonthsNoOverflow(5)->startOfMonth(), $aujourdhui->copy()->endOfMonth()],
        };

        if ($du->greaterThan($au)) {
            [$du, $au] = [$au, $du];
        }

        return [$du->startOfDay(), $au->endOfDay(), $periode];
    }

    private function date(?string $valeur): ?Carbon
    {
        try {
            return $valeur ? Carbon::parse($valeur) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function kpis(Collection $rapports): object
    {
        $remis = $rapports->sum(fn ($r) => $r->quantite_remise);

        return (object) [
            'rapports' => $rapports->count(),
            'ventes' => $rapports->sum('montant_vendu'),
            'vendus' => $rapports->sum('quantite_vendue'),
            'defectueux' => $rapports->sum('quantite_defectueuse'),
            'rendus' => $rapports->sum('quantite_rendue'),
            'remis' => $remis,
            'tauxDefectueux' => $remis ? round($rapports->sum('quantite_defectueuse') * 100 / $remis, 1) : 0,
            'tauxVente' => $remis ? round($rapports->sum('quantite_vendue') * 100 / $remis, 1) : 0,
            'commissions' => $rapports->sum(fn ($r) => $r->commission_agent + $r->commission_superviseur),
            'net' => $rapports->sum(fn ($r) => $r->net_gerant),
            'manquants' => $rapports->sum(fn ($r) => max(0, -$r->ecart)),
        ];
    }

    /** Ventes par mois (ou par jour si la période fait moins de 45 jours). */
    private function evolution(Collection $rapports, Carbon $du, Carbon $au): array
    {
        $parJour = $du->diffInDays($au) < 45;
        $format = $parJour ? 'Y-m-d' : 'Y-m';

        $groupes = $rapports->groupBy(fn ($r) => $r->date_rapport->format($format));

        $points = [];
        foreach (CarbonPeriod::create($du, $parJour ? '1 day' : '1 month', $au) as $date) {
            $cle = $date->format($format);
            $groupe = $groupes->get($cle, collect());
            $points[] = [
                'label' => $parJour ? $date->format('d/m') : ucfirst($date->translatedFormat('M y')),
                'titre' => $parJour ? $date->translatedFormat('d F Y') : ucfirst($date->translatedFormat('F Y')),
                'valeur' => (int) $groupe->sum('montant_vendu'),
                'tickets' => (int) $groupe->sum('quantite_vendue'),
            ];
        }

        return ['parJour' => $parJour, 'points' => $points];
    }

    /** Totaux regroupés par site / agent / superviseur, triés par ventes. */
    private function regrouper(Collection $rapports, callable $cle): Collection
    {
        return $rapports
            ->filter(fn ($r) => $cle($r) !== null)
            ->groupBy(fn ($r) => $cle($r)->id)
            ->map(function (Collection $groupe) use ($cle) {
                $remis = $groupe->sum(fn ($r) => $r->quantite_remise);

                return (object) [
                    'entite' => $cle($groupe->first()),
                    'rapports' => $groupe->count(),
                    'ventes' => (int) $groupe->sum('montant_vendu'),
                    'vendus' => (int) $groupe->sum('quantite_vendue'),
                    'defectueux' => (int) $groupe->sum('quantite_defectueuse'),
                    'tauxDefectueux' => $remis ? round($groupe->sum('quantite_defectueuse') * 100 / $remis, 1) : 0,
                    'tauxVente' => $remis ? round($groupe->sum('quantite_vendue') * 100 / $remis, 1) : 0,
                    'commissions' => (int) $groupe->sum(fn ($r) => $r->commission_agent + $r->commission_superviseur),
                    'manquants' => (int) $groupe->sum(fn ($r) => max(0, -$r->ecart)),
                ];
            })
            ->sortByDesc('ventes')
            ->values();
    }

    private function parForfait(Collection $rapports): Collection
    {
        if ($rapports->isEmpty()) {
            return collect();
        }

        return RapportLigne::query()
            ->with('forfait')
            ->whereIn('rapport_id', $rapports->pluck('id'))
            ->get()
            ->groupBy('forfait_id')
            ->map(fn (Collection $g) => (object) [
                'entite' => $g->first()->forfait,
                'vendus' => (int) $g->sum('vendus'),
                'defectueux' => (int) $g->sum('defectueux'),
                'ventes' => (int) $g->sum('montant_vendu'),
            ])
            ->sortByDesc('ventes')
            ->values();
    }
}
