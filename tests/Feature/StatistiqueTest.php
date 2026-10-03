<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Forfait;
use App\Models\Lot;
use App\Models\Rapport;
use App\Models\Site;
use App\Models\Superviseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatistiqueTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gerant = User::factory()->create();
    }

    private function rapport(string $nomSite, int $vendus, int $defectueux, string $date): Rapport
    {
        $sup = Superviseur::factory()->create();
        $site = Site::factory()->create(['nom' => $nomSite, 'superviseur_id' => $sup->id]);
        $agent = Agent::factory()->create(['site_id' => $site->id]);
        $forfait = Forfait::factory()->create(['nom' => "Pass {$nomSite}", 'prix' => 1000]);
        $lot = Lot::factory()->avecLignes([$forfait->id => 100])->create([
            'site_id' => $site->id, 'agent_id' => $agent->id, 'superviseur_id' => $sup->id, 'statut' => Lot::TERMINE,
        ]);
        $rapport = Rapport::create(['lot_id' => $lot->id, 'date_rapport' => $date, 'montant_verse' => 0]);
        $rapport->lignes()->create([
            'lot_ligne_id' => $lot->lignes->first()->id, 'forfait_id' => $forfait->id, 'quantite_remise' => 100,
            'prix_unitaire' => 1000, 'vendus' => $vendus, 'defectueux' => $defectueux,
        ]);
        $rapport->recalculer();

        return $rapport;
    }

    public function test_page_vide(): void
    {
        $this->actingAs($this->gerant)->get(route('statistiques.index'))->assertOk()->assertSee('Aucun rapport sur cette période');
    }

    public function test_statistiques_de_la_periode(): void
    {
        $this->rapport('Hotspot Kipe', 80, 5, now()->toDateString());
        $this->rapport('Hotspot Matam', 50, 10, now()->toDateString());
        $this->rapport('Hotspot Ancien', 90, 0, now()->subYears(2)->toDateString()); // hors période

        $this->actingAs($this->gerant)->get(route('statistiques.index', ['periode' => 'mois']))
            ->assertOk()
            ->assertSee('130 000 GNF')            // 80 000 + 50 000
            ->assertSee('7,5 %')                  // 15 défectueux / 200 remis
            ->assertSeeInOrder(['Ventes par site', 'Hotspot Kipe', 'Hotspot Matam'])
            ->assertDontSee('Pass Hotspot Ancien');

        // Filtre par site
        $site = Site::where('nom', 'Hotspot Matam')->first();
        $this->actingAs($this->gerant)->get(route('statistiques.index', ['periode' => 'mois', 'site' => $site->id]))
            ->assertSee('50 000 GNF')->assertDontSee('Pass Hotspot Kipe');

        // Période personnalisée et autres périodes
        $this->actingAs($this->gerant)->get(route('statistiques.index', ['periode' => 'perso', 'du' => now()->subYears(3)->toDateString(), 'au' => now()->toDateString()]))
            ->assertOk()->assertSee('Pass Hotspot Ancien');
        foreach (['mois_dernier', '3_mois', '6_mois', 'annee'] as $p) {
            $this->actingAs($this->gerant)->get(route('statistiques.index', ['periode' => $p]))->assertOk();
        }
    }
}
