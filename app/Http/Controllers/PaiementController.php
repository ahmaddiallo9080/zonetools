<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaiementRequest;
use App\Models\Agent;
use App\Models\Paiement;
use App\Models\Superviseur;
use App\Services\Commissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaiementController extends Controller
{
    public function index(Request $request): View
    {
        $requete = Paiement::query()
            ->when(array_key_exists((string) $request->query('type'), Paiement::TYPES), fn ($q) => $q->where('beneficiaire_type', $request->query('type')))
            ->when(array_key_exists((string) $request->query('mode'), Paiement::MODES), fn ($q) => $q->where('mode', $request->query('mode')))
            ->when($request->filled('beneficiaire'), function ($q) use ($request) {
                [$type, $id] = array_pad(explode(':', $request->query('beneficiaire')), 2, null);
                $q->where('beneficiaire_type', $type)->where('beneficiaire_id', (int) $id);
            })
            ->when($request->filled('du'), fn ($q) => $q->whereDate('date_paiement', '>=', $request->query('du')))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('date_paiement', '<=', $request->query('au')));

        $total = (int) (clone $requete)->sum('montant');

        return view('paiements.index', [
            'paiements' => $requete->with('beneficiaire')->latest('date_paiement')->latest('id')->paginate(20)->withQueryString(),
            'total' => $total,
            'beneficiaires' => $this->beneficiaires(),
        ]);
    }

    public function create(Request $request, Commissions $commissions): View
    {
        $beneficiaire = $request->filled('beneficiaire') ? $request->query('beneficiaire') : null;

        // Montant proposé : le solde dû à la personne choisie
        $soldes = $this->soldes($commissions);
        $montant = $beneficiaire && isset($soldes[$beneficiaire]) ? max(0, $soldes[$beneficiaire]) : null;

        return view('paiements.create', [
            'paiement' => new Paiement(['date_paiement' => now(), 'mode' => 'especes', 'montant' => $montant]),
            'beneficiaireChoisi' => $beneficiaire,
            'beneficiaires' => $this->beneficiaires(true),
            'soldes' => $soldes,
            'codeSuggere' => Paiement::prochainCode(),
        ]);
    }

    public function store(PaiementRequest $request): RedirectResponse
    {
        $paiement = Paiement::create($request->donnees());

        return redirect()
            ->route('paiements.show', $paiement)
            ->with('success', "Paiement de {$paiement->montant_affiche} enregistré pour {$paiement->beneficiaire->nom_complet}.");
    }

    public function show(Paiement $paiement, Commissions $commissions): View
    {
        $paiement->load('beneficiaire');

        return view('paiements.show', [
            'paiement' => $paiement,
            'solde' => $commissions->pour($paiement->beneficiaire),
        ]);
    }

    public function edit(Paiement $paiement, Commissions $commissions): View
    {
        $soldes = $this->soldes($commissions);
        // En modification, le solde affiché inclut le montant de ce paiement
        $cle = "{$paiement->beneficiaire_type}:{$paiement->beneficiaire_id}";
        if (isset($soldes[$cle])) {
            $soldes[$cle] += $paiement->montant;
        }

        return view('paiements.edit', [
            'paiement' => $paiement,
            'beneficiaireChoisi' => $cle,
            'beneficiaires' => $this->beneficiaires(true, $paiement),
            'soldes' => $soldes,
        ]);
    }

    public function update(PaiementRequest $request, Paiement $paiement): RedirectResponse
    {
        $paiement->update($request->donnees());

        return redirect()
            ->route('paiements.show', $paiement)
            ->with('success', "Le paiement {$paiement->code} a été mis à jour.");
    }

    public function destroy(Paiement $paiement): RedirectResponse
    {
        $paiement->delete();

        return redirect()
            ->route('paiements.index')
            ->with('success', "Le paiement {$paiement->code} a été supprimé.");
    }

    /* ----------------------------------------------------------------
     |  Outils
     | ---------------------------------------------------------------- */

    /** Listes pour les menus déroulants : ['Superviseurs' => [clé => nom], 'Agents' => [...]] */
    private function beneficiaires(bool $actifsSeulement = false, ?Paiement $paiement = null): array
    {
        $filtre = fn ($q, string $type) => $q->when($actifsSeulement, fn ($q) => $q->where(fn ($w) => $w
            ->where('statut', 'actif')
            ->when($paiement?->beneficiaire_type === $type, fn ($w) => $w->orWhere('id', $paiement->beneficiaire_id))));

        return [
            'Superviseurs' => $filtre(Superviseur::query(), 'superviseur')->orderBy('nom')->orderBy('prenom')->get()
                ->mapWithKeys(fn ($s) => ["superviseur:{$s->id}" => $s->nom_complet])->all(),
            'Agents' => $filtre(Agent::query(), 'agent')->orderBy('nom')->orderBy('prenom')->get()
                ->mapWithKeys(fn ($a) => ["agent:{$a->id}" => $a->nom_complet])->all(),
        ];
    }

    /** Soldes de tout le monde : [« type:id » => solde]. */
    private function soldes(Commissions $commissions): array
    {
        return $commissions->superviseurs()->mapWithKeys(fn ($l, $id) => ["superviseur:{$id}" => $l->solde])
            ->merge($commissions->agents()->mapWithKeys(fn ($l, $id) => ["agent:{$id}" => $l->solde]))
            ->all();
    }
}
