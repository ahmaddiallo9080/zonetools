<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Forfait;
use App\Models\Lot;
use App\Models\Site;
use App\Models\Superviseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;
    private Superviseur $sup;
    private Site $site;
    private Agent $agent;
    private Forfait $heure;
    private Forfait $jour;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gerant = User::factory()->create();
        $this->sup = Superviseur::factory()->create(['taux_commission' => 5]);
        $this->site = Site::factory()->create(['superviseur_id' => $this->sup->id, 'statut' => 'actif']);
        $this->agent = Agent::factory()->create(['site_id' => $this->site->id, 'statut' => 'actif', 'taux_commission' => 10]);
        $this->heure = Forfait::factory()->create(['nom' => 'Pass 1 heure', 'prix' => 1000]);
        $this->jour = Forfait::factory()->create(['nom' => 'Pass 1 jour', 'prix' => 5000]);
        // Prix particulier du forfait 1 jour sur ce site
        $this->jour->sites()->attach($this->site->id, ['prix' => 4000]);
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'site_id' => $this->site->id,
            'agent_id' => $this->agent->id,
            'date_remise' => now()->format('Y-m-d'),
            'taux_agent' => '10',
            'taux_superviseur' => '5',
            'lignes' => [
                ['forfait_id' => $this->heure->id, 'quantite' => '100'],
                ['forfait_id' => $this->jour->id, 'quantite' => '20'],
            ],
        ], $surcharge);
    }

    public function test_creation_d_un_lot_avec_plusieurs_forfaits(): void
    {
        $this->actingAs($this->gerant)->post(route('lots.store'), $this->donnees())
            ->assertRedirect()->assertSessionHas('success');

        $lot = Lot::first();
        $this->assertSame('LOT-001', $lot->code);
        $this->assertSame(Lot::EN_COURS, $lot->statut);
        $this->assertSame($this->sup->id, $lot->superviseur_id);   // superviseur du site
        $this->assertSame(120, $lot->quantite_totale);
        $this->assertSame(100 * 1000 + 20 * 4000, $lot->montant_total); // prix particulier appliqué
        $this->assertSame(18000, $lot->commission_agent_prevue);       // 10 % de 180 000
        $this->assertSame(9000, $lot->commission_superviseur_prevue);  // 5 %
        $this->assertSame(153000, $lot->net_gerant_prevu);
    }

    public function test_le_prix_reste_fige_apres_un_changement_de_tarif(): void
    {
        $this->actingAs($this->gerant)->post(route('lots.store'), $this->donnees());
        $lot = Lot::first();

        $this->heure->update(['prix' => 1500]); // hausse du prix après la remise

        $this->actingAs($this->gerant)->put(route('lots.update', $lot), $this->donnees([
            'lignes' => [
                ['forfait_id' => $this->heure->id, 'quantite' => '50'],  // quantité modifiée
            ],
        ]))->assertRedirect(route('lots.show', $lot));

        $lot->refresh()->load('lignes');
        $this->assertCount(1, $lot->lignes);
        $this->assertSame(1000, $lot->lignes->first()->prix_unitaire); // ancien prix conservé
        $this->assertSame(50000, $lot->montant_total);
    }

    public function test_validation(): void
    {
        $autreSite = Site::factory()->create();
        $autreAgent = Agent::factory()->create(['site_id' => $autreSite->id]);

        // Agent d'un autre site
        $this->actingAs($this->gerant)->post(route('lots.store'), $this->donnees(['agent_id' => $autreAgent->id]))
            ->assertSessionHasErrors('agent_id');

        // Aucune ligne, taux trop élevés
        $this->actingAs($this->gerant)->post(route('lots.store'), $this->donnees([
            'lignes' => [['forfait_id' => '', 'quantite' => '']], 'taux_agent' => 80, 'taux_superviseur' => 30,
        ]))->assertSessionHasErrors(['lignes', 'taux_superviseur']);

        // Forfait en double, quantité nulle
        $this->actingAs($this->gerant)->post(route('lots.store'), $this->donnees([
            'lignes' => [
                ['forfait_id' => $this->heure->id, 'quantite' => 10],
                ['forfait_id' => $this->heure->id, 'quantite' => 0],
            ],
        ]))->assertSessionHasErrors(['lignes.0.forfait_id', 'lignes.1.quantite']);

        // Fin prévue avant la remise
        $this->actingAs($this->gerant)->post(route('lots.store'), $this->donnees([
            'date_remise' => '2026-10-03', 'date_fin_prevue' => '2026-10-01',
        ]))->assertSessionHasErrors('date_fin_prevue');

        $this->assertSame(0, Lot::count());
    }

    public function test_les_pages_s_affichent_et_filtrent(): void
    {
        $lot = Lot::factory()->avecLignes([$this->heure->id => 50])->create([
            'site_id' => $this->site->id, 'agent_id' => $this->agent->id, 'superviseur_id' => $this->sup->id,
        ]);
        $autre = Lot::factory()->avecLignes([$this->jour->id => 10])->create();

        $this->actingAs($this->gerant)->get(route('lots.index'))->assertOk()->assertSee($lot->code)->assertSee($autre->code)->assertSee('50 000 GNF');
        $this->actingAs($this->gerant)->get(route('lots.index', ['site' => $this->site->id]))->assertSee($lot->code)->assertDontSee($autre->code);
        $this->actingAs($this->gerant)->get(route('lots.index', ['agent' => $this->agent->id, 'statut' => 'en_cours', 'du' => '2020-01-01']))->assertSee($lot->code);
        $this->actingAs($this->gerant)->get(route('lots.create', ['agent' => $this->agent->id]))->assertOk()->assertSee('Pass 1 heure');
        $this->actingAs($this->gerant)->get(route('lots.show', $lot))->assertOk()->assertSee('Pass 1 heure')->assertSee('Net gérant');
        $this->actingAs($this->gerant)->get(route('lots.edit', $lot))->assertOk();

        // Les fiches liées affichent le lot
        $this->actingAs($this->gerant)->get(route('sites.show', $this->site))->assertSee($lot->code);
        $this->actingAs($this->gerant)->get(route('agents.show', $this->agent))->assertSee($lot->code);
        $this->actingAs($this->gerant)->get(route('superviseurs.show', $this->sup))->assertOk();
        $this->actingAs($this->gerant)->get(route('forfaits.show', $this->heure))->assertOk();
        $this->actingAs($this->gerant)->get(route('dashboard'))->assertSee('Lots en cours');
    }

    public function test_annulation_reactivation_et_suppression(): void
    {
        $lot = Lot::factory()->avecLignes([$this->heure->id => 10])->create(['site_id' => $this->site->id, 'agent_id' => $this->agent->id]);

        $this->actingAs($this->gerant)->patch(route('lots.annulation', $lot));
        $this->assertSame(Lot::ANNULE, $lot->fresh()->statut);

        // Un lot annulé ne peut pas être modifié
        $this->actingAs($this->gerant)->get(route('lots.edit', $lot))->assertRedirect(route('lots.show', $lot));

        $this->actingAs($this->gerant)->patch(route('lots.annulation', $lot));
        $this->assertSame(Lot::EN_COURS, $lot->fresh()->statut);

        $this->actingAs($this->gerant)->delete(route('lots.destroy', $lot))->assertRedirect(route('lots.index'));
        $this->assertSoftDeleted($lot);
    }

    public function test_un_lot_termine_est_protege(): void
    {
        $lot = Lot::factory()->avecLignes([$this->heure->id => 10])->create(['statut' => Lot::TERMINE]);

        $this->actingAs($this->gerant)->delete(route('lots.destroy', $lot))->assertSessionHas('error');
        $this->actingAs($this->gerant)->put(route('lots.update', $lot), $this->donnees())->assertSessionHas('error');
        $this->assertNotSoftDeleted($lot);
    }

    public function test_suppression_bloquee_si_lots_en_cours(): void
    {
        Lot::factory()->avecLignes([$this->heure->id => 10])->create([
            'site_id' => $this->site->id, 'agent_id' => $this->agent->id, 'superviseur_id' => $this->sup->id,
        ]);

        $this->actingAs($this->gerant)->delete(route('sites.destroy', $this->site))->assertSessionHas('error');
        $this->actingAs($this->gerant)->delete(route('agents.destroy', $this->agent))->assertSessionHas('error');
        $this->actingAs($this->gerant)->delete(route('superviseurs.destroy', $this->sup))->assertSessionHas('error');
        $this->actingAs($this->gerant)->delete(route('forfaits.destroy', $this->heure))->assertSessionHas('error');

        $this->assertNotSoftDeleted($this->site);
        $this->assertNotSoftDeleted($this->agent);
        $this->assertNotSoftDeleted($this->sup);
        $this->assertNotSoftDeleted($this->heure);
    }
}
