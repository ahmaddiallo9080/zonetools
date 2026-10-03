<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\ForfaitController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\SuperviseurController;
use App\Http\Controllers\VersementController;
use App\Models\Agent;
use App\Models\Depense;
use App\Models\Lot;
use App\Models\Rapport;
use App\Models\Site;
use App\Models\Superviseur;
use Illuminate\Support\Facades\Route;

// La page d'accueil renvoie directement vers le tableau de bord (ou la connexion)
Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn (\App\Services\Commissions $commissions) => view('dashboard', [
        'sitesActifs' => Site::actifs()->count(),
        'superviseursActifs' => Superviseur::actifs()->count(),
        'agentsActifs' => Agent::actifs()->count(),
        'lotsEnCours' => Lot::enCours()->count(),
        'ticketsEnCirculation' => (int) Lot::enCours()->sum('quantite_totale'),
        'mois' => Rapport::whereBetween('date_rapport', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(montant_vendu), 0) as ventes, COALESCE(SUM(quantite_vendue), 0) as vendus,
                COALESCE(SUM(quantite_defectueuse), 0) as defectueux, COALESCE(SUM(quantite_vendue + quantite_defectueuse + quantite_rendue), 0) as remis,
                COALESCE(SUM(commission_agent + commission_superviseur), 0) as commissions,
                COALESCE(SUM(CASE WHEN ecart < 0 THEN -ecart ELSE 0 END), 0) as manquants')
            ->first(),
        'commissionsAPayer' => $commissions->superviseurs()->sum(fn ($l) => max(0, $l->solde))
            + $commissions->agents()->sum(fn ($l) => max(0, $l->solde)),
        'depensesMois' => (int) Depense::whereBetween('date_depense', [now()->startOfMonth(), now()->endOfMonth()])->sum('montant'),
        'derniersRapports' => Rapport::with(['lot.site', 'lot.agent'])->latest('date_rapport')->latest('id')->take(5)->get(),
    ]))->name('dashboard');

    // Paramètres du compte gérant
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Module 2 : Sites / Hotspots
    Route::resource('sites', SiteController::class);

    // Module 3 : Superviseurs & agents
    Route::resource('superviseurs', SuperviseurController::class)->parameters(['superviseurs' => 'superviseur']);
    Route::resource('agents', AgentController::class);

    // Module 4 : Forfaits de tickets
    Route::resource('forfaits', ForfaitController::class);

    // Module 5 : Lots de tickets
    Route::patch('lots/{lot}/annulation', [LotController::class, 'basculerAnnulation'])->name('lots.annulation');
    Route::resource('lots', LotController::class);

    // Module 6 : Rapports de fin de lot
    Route::get('lots/{lot}/rapport/create', [RapportController::class, 'create'])->name('rapports.create');
    Route::post('lots/{lot}/rapport', [RapportController::class, 'store'])->name('rapports.store');
    Route::resource('rapports', RapportController::class)->except(['create', 'store']);

    // Module 7 : Versements & commissions
    Route::get('versements', [VersementController::class, 'index'])->name('versements.index');
    Route::resource('paiements', PaiementController::class);

    // Finances : dépenses des sites
    Route::get('depenses/{depense}/justificatif', [DepenseController::class, 'justificatif'])->name('depenses.justificatif');
    Route::resource('depenses', DepenseController::class);

    // Module 8 : Statistiques
    Route::get('statistiques', [StatistiqueController::class, 'index'])->name('statistiques.index');
});

require __DIR__.'/auth.php';
