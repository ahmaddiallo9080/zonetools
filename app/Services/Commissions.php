<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Paiement;
use App\Models\Rapport;
use App\Models\Superviseur;
use Illuminate\Support\Collection;

/**
 * Calcule ce que le gérant doit à chaque superviseur et agent.
 *
 * - Superviseur : toutes ses commissions (rapports des lots qu'il supervise).
 * - Agent : seulement les commissions qu'il n'a pas gardées sur l'argent des ventes
 *   (rapports avec commission_agent_deduite = false).
 * Solde = commissions dues − paiements déjà faits.
 */
class Commissions
{
    /** Soldes de tous les superviseurs, indexés par id. */
    public function superviseurs(?Collection $personnes = null): Collection
    {
        $personnes ??= Superviseur::orderBy('nom')->orderBy('prenom')->get();

        $gagnees = Rapport::query()
            ->join('lots', 'lots.id', '=', 'rapports.lot_id')
            ->whereNotNull('lots.superviseur_id')
            ->groupBy('lots.superviseur_id')
            ->selectRaw('lots.superviseur_id as id, SUM(rapports.commission_superviseur) as total, COUNT(*) as rapports')
            ->get()->keyBy('id');

        $payees = $this->paiementsPar('superviseur');

        return $personnes->mapWithKeys(function (Superviseur $s) use ($gagnees, $payees) {
            $dues = (int) ($gagnees[$s->id]->total ?? 0);
            $paye = (int) ($payees[$s->id] ?? 0);

            return [$s->id => (object) [
                'personne' => $s,
                'rapports' => (int) ($gagnees[$s->id]->rapports ?? 0),
                'gagnees' => $dues,
                'gardees' => 0,
                'dues' => $dues,
                'payees' => $paye,
                'solde' => $dues - $paye,
                'manquants' => 0,
            ]];
        });
    }

    /** Soldes de tous les agents, indexés par id. */
    public function agents(?Collection $personnes = null): Collection
    {
        $personnes ??= Agent::orderBy('nom')->orderBy('prenom')->get();

        $stats = Rapport::query()
            ->join('lots', 'lots.id', '=', 'rapports.lot_id')
            ->groupBy('lots.agent_id')
            ->selectRaw('
                lots.agent_id as id,
                COUNT(*) as rapports,
                SUM(rapports.commission_agent) as gagnees,
                SUM(CASE WHEN rapports.commission_agent_deduite = 1 THEN rapports.commission_agent ELSE 0 END) as gardees,
                SUM(CASE WHEN rapports.ecart < 0 THEN -rapports.ecart ELSE 0 END) as manquants
            ')
            ->get()->keyBy('id');

        $payees = $this->paiementsPar('agent');

        return $personnes->mapWithKeys(function (Agent $a) use ($stats, $payees) {
            $st = $stats[$a->id] ?? null;
            $gagnees = (int) ($st->gagnees ?? 0);
            $gardees = (int) ($st->gardees ?? 0);
            $paye = (int) ($payees[$a->id] ?? 0);

            return [$a->id => (object) [
                'personne' => $a,
                'rapports' => (int) ($st->rapports ?? 0),
                'gagnees' => $gagnees,
                'gardees' => $gardees,
                'dues' => $gagnees - $gardees,
                'payees' => $paye,
                'solde' => $gagnees - $gardees - $paye,
                'manquants' => (int) ($st->manquants ?? 0),
            ]];
        });
    }

    /** Solde d'une seule personne. */
    public function pour(Agent|Superviseur $personne): object
    {
        return $personne instanceof Agent
            ? $this->agents(collect([$personne]))->first()
            : $this->superviseurs(collect([$personne]))->first();
    }

    /** Total payé par bénéficiaire pour un type donné : [id => montant]. */
    private function paiementsPar(string $type): Collection
    {
        return Paiement::where('beneficiaire_type', $type)
            ->groupBy('beneficiaire_id')
            ->selectRaw('beneficiaire_id, SUM(montant) as total')
            ->pluck('total', 'beneficiaire_id');
    }
}
