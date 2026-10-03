<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Services\Commissions;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vue d'ensemble : ce que le gérant doit à chaque superviseur et agent.
 */
class VersementController extends Controller
{
    public function index(Request $request, Commissions $commissions): View
    {
        $onglet = $request->query('onglet') === 'agents' ? 'agents' : 'superviseurs';

        $superviseurs = $commissions->superviseurs();
        $agents = $commissions->agents();

        $lignes = $onglet === 'agents' ? $agents : $superviseurs;

        // Filtre : seulement ceux à qui on doit encore de l'argent
        if ($request->query('filtre') === 'a_payer') {
            $lignes = $lignes->filter(fn ($l) => $l->solde > 0);
        }
        if ($request->filled('q')) {
            $terme = mb_strtolower($request->query('q'));
            $lignes = $lignes->filter(fn ($l) => str_contains(mb_strtolower($l->personne->nom_complet . ' ' . $l->personne->code), $terme));
        }

        // On cache les personnes sans aucune activité ni paiement
        $lignes = $lignes->filter(fn ($l) => $l->rapports > 0 || $l->payees > 0 || $l->personne->statut === 'actif')
            ->sortByDesc('solde');

        return view('versements.index', [
            'onglet' => $onglet,
            'lignes' => $lignes,
            'totaux' => [
                'superviseurs' => $superviseurs->sum(fn ($l) => max(0, $l->solde)),
                'agents' => $agents->sum(fn ($l) => max(0, $l->solde)),
                'payeMois' => (int) Paiement::whereBetween('date_paiement', [now()->startOfMonth(), now()->endOfMonth()])->sum('montant'),
                'manquants' => $agents->sum('manquants'),
            ],
            'derniersPaiements' => Paiement::with('beneficiaire')->latest('date_paiement')->latest('id')->take(5)->get(),
        ]);
    }
}
