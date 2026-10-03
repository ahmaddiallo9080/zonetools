<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Depense;
use App\Models\Forfait;
use App\Models\Lot;
use App\Models\Paiement;
use App\Models\Rapport;
use App\Models\Site;
use App\Models\Superviseur;
use App\Services\Commissions;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration : php artisan db:seed --class=DemoSeeder
 * (ne pas lancer en production)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $superviseurs = Superviseur::factory()->count(4)->create();

        Site::factory()->count(10)->create()->each(function (Site $site) use ($superviseurs) {
            $site->update(['superviseur_id' => $superviseurs->random()->id]);
            Agent::factory()->count(rand(1, 3))->create(['site_id' => $site->id]);
        });

        Agent::factory()->count(2)->create(['site_id' => null]); // agents pas encore affectés

        // Forfaits types
        $forfaits = collect([
            ['nom' => 'Pass 1 heure', 'duree_valeur' => 1, 'duree_unite' => 'heure', 'nb_appareils' => 1, 'prix' => 1000, 'couleur' => 'gray'],
            ['nom' => 'Pass 3 heures', 'duree_valeur' => 3, 'duree_unite' => 'heure', 'nb_appareils' => 1, 'prix' => 2000, 'couleur' => 'primary'],
            ['nom' => 'Pass 1 jour', 'duree_valeur' => 1, 'duree_unite' => 'jour', 'nb_appareils' => 1, 'prix' => 5000, 'couleur' => 'green'],
            ['nom' => 'Pass 7 jours', 'duree_valeur' => 7, 'duree_unite' => 'jour', 'nb_appareils' => 2, 'prix' => 25000, 'couleur' => 'amber'],
            ['nom' => 'Pass 30 jours', 'duree_valeur' => 1, 'duree_unite' => 'mois', 'nb_appareils' => 3, 'prix' => 80000, 'couleur' => 'red'],
        ])->map(fn ($f) => Forfait::firstOrCreate(['nom' => $f['nom']], $f));

        // Quelques prix particuliers (sites plus chers ou moins chers)
        $sites = Site::inRandomOrder()->take(2)->get();
        $forfaits->first()->sites()->syncWithoutDetaching([$sites[0]->id => ['prix' => 1500]]);
        $forfaits[2]->sites()->syncWithoutDetaching([$sites[1]->id => ['prix' => 4000]]);

        // Lots de démonstration sur les agents actifs
        Agent::actifs()->whereNotNull('site_id')->with('site')->get()->each(function (Agent $agent) use ($forfaits) {
            $choix = $forfaits->random(rand(1, 3));
            Lot::factory()
                ->avecLignes($choix->mapWithKeys(fn ($f) => [$f->id => rand(2, 10) * 10])->all())
                ->create([
                    'site_id' => $agent->site_id,
                    'agent_id' => $agent->id,
                    'superviseur_id' => $agent->site->superviseur_id,
                    'taux_agent' => $agent->taux_commission,
                    'taux_superviseur' => $agent->site->superviseur?->taux_commission ?? 0,
                ]);
        });
    
        // Rapports sur environ la moitié des lots
        Lot::with('lignes')->get()->filter(fn () => rand(0, 1))->each(function (Lot $lot) {
            $rapport = Rapport::create([
                'lot_id' => $lot->id,
                'date_rapport' => $lot->date_remise->copy()->addDays(rand(3, 10))->min(now()),
                'montant_verse' => 0,
            ]);
            foreach ($lot->lignes as $ligne) {
                $defectueux = rand(0, (int) ceil($ligne->quantite * 0.05));
                $vendus = rand((int) ($ligne->quantite * 0.7), $ligne->quantite - $defectueux);
                $rapport->lignes()->create([
                    'lot_ligne_id' => $ligne->id, 'forfait_id' => $ligne->forfait_id, 'quantite_remise' => $ligne->quantite,
                    'prix_unitaire' => $ligne->prix_unitaire, 'vendus' => $vendus, 'defectueux' => $defectueux,
                ]);
            }
            $rapport->recalculer();
            // La plupart du temps le compte est juste, parfois il manque de l'argent
            $rapport->montant_verse = rand(0, 3) ? $rapport->montant_attendu : max(0, $rapport->montant_attendu - rand(1, 5) * 1000);
            $rapport->recalculer();
            $lot->update(['statut' => Lot::TERMINE]);
        });
    
        // Paiements : la moitié du solde de chaque superviseur a déjà été payée
        app(Commissions::class)->superviseurs()->filter(fn ($l) => $l->solde > 0)->each(function ($l) {
            Paiement::create([
                'beneficiaire_type' => 'superviseur',
                'beneficiaire_id' => $l->personne->id,
                'date_paiement' => now()->subDays(rand(1, 10)),
                'montant' => intdiv($l->solde, 2),
                'mode' => collect(array_keys(Paiement::MODES))->random(),
                'periode_du' => now()->subMonth()->startOfMonth(),
                'periode_au' => now()->subMonth()->endOfMonth(),
            ]);
        });
    
        // Dépenses : 3 à 6 par site sur les 3 derniers mois + quelques dépenses générales
        Site::all()->each(fn (Site $site) => Depense::factory()->count(rand(3, 6))->create(['site_id' => $site->id]));
        Depense::factory()->count(2)->create(['site_id' => null, 'categorie' => 'transport', 'libelle' => 'Tournée des sites', 'fournisseur' => null]);
    }
}
