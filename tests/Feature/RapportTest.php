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

class RapportTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;
    private Lot $lot;
    private int $ligneHeure;
    private int $ligneJour;

    protected function setUp(): void
    {
        parent::setUp();
        config(['zonetools.commission_agent_deduite' => true]);

        $this->gerant = User::factory()->create();
        $sup = Superviseur::factory()->create();
        $site = Site::factory()->create(['superviseur_id' => $sup->id]);
        $agent = Agent::factory()->create(['site_id' => $site->id]);
        $heure = Forfait::factory()->create(['prix' => 1000]);
        $jour = Forfait::factory()->create(['prix' => 5000]);

        // Lot : 100 × 1 000 + 20 × 5 000 = 200 000 GNF ; agent 10 %, superviseur 5 %
        $this->lot = Lot::factory()->avecLignes([$heure->id => 100, $jour->id => 20])->create([
            'site_id' => $site->id, 'agent_id' => $agent->id, 'superviseur_id' => $sup->id,
            'taux_agent' => 10, 'taux_superviseur' => 5, 'date_remise' => now()->subDays(7),
        ]);
        $this->ligneHeure = $this->lot->lignes->firstWhere('forfait_id', $heure->id)->id;
        $this->ligneJour = $this->lot->lignes->firstWhere('forfait_id', $jour->id)->id;
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'date_rapport' => now()->format('Y-m-d'),
            'montant_verse' => '117 000',
            'lignes' => [
                $this->ligneHeure => ['vendus' => 90, 'defectueux' => 4],  // 6 rendus
                $this->ligneJour => ['vendus' => 15, 'defectueux' => ''],  // 5 rendus
            ],
        ], $surcharge);
    }

    public function test_saisie_du_rapport_calcule_tout_et_termine_le_lot(): void
    {
        $this->actingAs($this->gerant)->get(route('rapports.create', $this->lot))->assertOk()->assertSee($this->lot->code);

        $this->actingAs($this->gerant)->post(route('rapports.store', $this->lot), $this->donnees())
            ->assertRedirect()->assertSessionHas('success');

        $rapport = Rapport::first();
        $this->assertSame('RAP-001', $rapport->code);
        $this->assertSame(105, $rapport->quantite_vendue);
        $this->assertSame(4, $rapport->quantite_defectueuse);
        $this->assertSame(11, $rapport->quantite_rendue);
        $this->assertSame(90 * 1000 + 15 * 5000, $rapport->montant_vendu); // 165 000
        $this->assertSame(16500, $rapport->commission_agent);
        $this->assertSame(8250, $rapport->commission_superviseur);
        $this->assertSame(148500, $rapport->montant_attendu);  // l'agent garde sa commission
        $this->assertSame(117000 - 148500, $rapport->ecart);   // manque 31 500
        $this->assertSame(140250, $rapport->net_gerant);
        $this->assertSame(Lot::TERMINE, $this->lot->fresh()->statut);

        // Un second rapport sur le même lot est refusé
        $this->actingAs($this->gerant)->get(route('rapports.create', $this->lot))->assertRedirect(route('rapports.show', $rapport));
        $this->actingAs($this->gerant)->post(route('rapports.store', $this->lot), $this->donnees())->assertSessionHas('error');
        $this->assertSame(1, Rapport::count());
    }

    public function test_commission_non_deduite(): void
    {
        config(['zonetools.commission_agent_deduite' => false]);

        $this->actingAs($this->gerant)->post(route('rapports.store', $this->lot), $this->donnees(['montant_verse' => 165000]));

        $rapport = Rapport::first();
        $this->assertSame(165000, $rapport->montant_attendu);
        $this->assertSame(0, $rapport->ecart);
        $this->assertSame('Compte juste', $rapport->ecart_label);
    }

    public function test_validation(): void
    {
        // Vendus + défectueux > remis, date avant la remise, montant manquant
        $this->actingAs($this->gerant)->post(route('rapports.store', $this->lot), $this->donnees([
            'date_rapport' => now()->subDays(30)->format('Y-m-d'),
            'montant_verse' => '',
            'lignes' => [
                $this->ligneHeure => ['vendus' => 99, 'defectueux' => 5],
                $this->ligneJour => ['vendus' => '', 'defectueux' => 0],
            ],
        ]))->assertSessionHasErrors(['date_rapport', 'montant_verse', "lignes.{$this->ligneHeure}.vendus", "lignes.{$this->ligneJour}.vendus"]);

        $this->assertSame(0, Rapport::count());
        $this->assertSame(Lot::EN_COURS, $this->lot->fresh()->statut);
    }

    public function test_un_lot_annule_ne_recoit_pas_de_rapport(): void
    {
        $this->lot->update(['statut' => Lot::ANNULE]);

        $this->actingAs($this->gerant)->get(route('rapports.create', $this->lot))->assertRedirect(route('lots.show', $this->lot));
        $this->actingAs($this->gerant)->post(route('rapports.store', $this->lot), $this->donnees())->assertSessionHas('error');
    }

    public function test_modification_et_suppression(): void
    {
        $this->actingAs($this->gerant)->post(route('rapports.store', $this->lot), $this->donnees());
        $rapport = Rapport::first();

        $this->actingAs($this->gerant)->get(route('rapports.edit', $rapport))->assertOk();
        $this->actingAs($this->gerant)->put(route('rapports.update', $rapport), $this->donnees([
            'montant_verse' => 148500,
        ]))->assertRedirect(route('rapports.show', $rapport));
        $this->assertSame(0, $rapport->fresh()->ecart);
        $this->assertSame(2, $rapport->lignes()->count()); // pas de doublons

        $this->actingAs($this->gerant)->delete(route('rapports.destroy', $rapport))->assertRedirect(route('lots.show', $this->lot));
        $this->assertSame(0, Rapport::count());
        $this->assertSame(Lot::EN_COURS, $this->lot->fresh()->statut);
    }

    public function test_les_pages_s_affichent(): void
    {
        $this->actingAs($this->gerant)->post(route('rapports.store', $this->lot), $this->donnees());
        $rapport = Rapport::first();

        $this->actingAs($this->gerant)->get(route('rapports.index'))->assertOk()->assertSee($rapport->code)->assertSee('165 000 GNF');
        $this->actingAs($this->gerant)->get(route('rapports.index', ['ecart' => 'manquant', 'site' => $this->lot->site_id, 'du' => '2020-01-01']))->assertSee($rapport->code);
        $this->actingAs($this->gerant)->get(route('rapports.show', $rapport))->assertOk()->assertSee('Manque 31 500 GNF');
        $this->actingAs($this->gerant)->get(route('lots.show', $this->lot))->assertOk()->assertSee($rapport->code);
        $this->actingAs($this->gerant)->get(route('dashboard'))->assertOk()->assertSee('165 000 GNF')->assertSee($rapport->code);
    }
}
