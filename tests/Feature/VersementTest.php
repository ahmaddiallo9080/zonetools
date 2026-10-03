<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Forfait;
use App\Models\Lot;
use App\Models\Paiement;
use App\Models\Rapport;
use App\Models\Site;
use App\Models\Superviseur;
use App\Models\User;
use App\Services\Commissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersementTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;
    private Superviseur $sup;
    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gerant = User::factory()->create();
        $this->sup = Superviseur::factory()->create(['prenom' => 'Sekou', 'nom' => 'TOURE', 'statut' => 'actif']);
        $site = Site::factory()->create(['superviseur_id' => $this->sup->id]);
        $this->agent = Agent::factory()->create(['site_id' => $site->id, 'prenom' => 'Mariama', 'nom' => 'SYLLA', 'statut' => 'actif']);
    }

    /** Crée un lot + rapport : 100 tickets vendus à 1 000 GNF = 100 000 GNF. */
    private function rapport(bool $agentDeduit, int $verse): Rapport
    {
        $forfait = Forfait::factory()->create(['prix' => 1000]);
        $lot = Lot::factory()->avecLignes([$forfait->id => 100])->create([
            'site_id' => $this->agent->site_id, 'agent_id' => $this->agent->id, 'superviseur_id' => $this->sup->id,
            'taux_agent' => 10, 'taux_superviseur' => 5, 'statut' => Lot::TERMINE,
        ]);
        $rapport = Rapport::create(['lot_id' => $lot->id, 'date_rapport' => now(), 'montant_verse' => $verse]);
        $rapport->commission_agent_deduite = $agentDeduit;
        $ligne = $lot->lignes->first();
        $rapport->lignes()->create([
            'lot_ligne_id' => $ligne->id, 'forfait_id' => $forfait->id, 'quantite_remise' => 100,
            'prix_unitaire' => 1000, 'vendus' => 100, 'defectueux' => 0,
        ]);
        $rapport->recalculer();

        return $rapport;
    }

    public function test_calcul_des_soldes(): void
    {
        $this->rapport(agentDeduit: true, verse: 85000);   // agent garde 10 000, manque 5 000
        $this->rapport(agentDeduit: false, verse: 100000); // agent doit être payé 10 000

        Paiement::create(['beneficiaire_type' => 'superviseur', 'beneficiaire_id' => $this->sup->id, 'date_paiement' => now(), 'montant' => 4000, 'mode' => 'especes']);

        $c = app(Commissions::class);

        $sup = $c->pour($this->sup);
        $this->assertSame(10000, $sup->dues);   // 2 × 5 % de 100 000
        $this->assertSame(4000, $sup->payees);
        $this->assertSame(6000, $sup->solde);

        $agent = $c->pour($this->agent);
        $this->assertSame(20000, $agent->gagnees);
        $this->assertSame(10000, $agent->gardees);
        $this->assertSame(10000, $agent->dues);
        $this->assertSame(10000, $agent->solde);
        $this->assertSame(5000, $agent->manquants);
    }

    public function test_enregistrer_un_paiement(): void
    {
        $this->rapport(agentDeduit: true, verse: 90000);

        $this->actingAs($this->gerant)->get(route('paiements.create', ['beneficiaire' => "superviseur:{$this->sup->id}"]))
            ->assertOk()->assertSee('Sekou TOURE');

        $this->actingAs($this->gerant)->post(route('paiements.store'), [
            'beneficiaire' => "superviseur:{$this->sup->id}",
            'montant' => '5 000',
            'date_paiement' => now()->format('Y-m-d'),
            'mode' => 'orange_money',
            'reference' => 'OM123',
        ])->assertRedirect()->assertSessionHas('success');

        $paiement = Paiement::first();
        $this->assertSame('PAY-001', $paiement->code);
        $this->assertSame(5000, $paiement->montant);
        $this->assertTrue($paiement->beneficiaire->is($this->sup));
        $this->assertSame(0, app(Commissions::class)->pour($this->sup)->solde);

        $this->actingAs($this->gerant)->get(route('paiements.show', $paiement))->assertOk()->assertSee('Reçu')->assertSee('5 000 GNF')->assertSee('Orange Money');
    }

    public function test_validation(): void
    {
        $this->actingAs($this->gerant)->post(route('paiements.store'), [
            'beneficiaire' => 'agent:999', 'montant' => '0', 'date_paiement' => now()->addDay()->format('Y-m-d'), 'mode' => 'cheque',
            'periode_du' => '2026-09-30', 'periode_au' => '2026-09-01',
        ])->assertSessionHasErrors(['beneficiaire_id', 'montant', 'date_paiement', 'mode', 'periode_au']);

        $this->actingAs($this->gerant)->post(route('paiements.store'), ['beneficiaire' => 'client:1', 'montant' => 100, 'date_paiement' => now()->format('Y-m-d'), 'mode' => 'especes'])
            ->assertSessionHasErrors('beneficiaire_type');

        $this->assertSame(0, Paiement::count());
    }

    public function test_pages(): void
    {
        $this->rapport(agentDeduit: false, verse: 100000);
        $p = Paiement::create(['beneficiaire_type' => 'agent', 'beneficiaire_id' => $this->agent->id, 'date_paiement' => now(), 'montant' => 3000, 'mode' => 'especes']);

        $this->actingAs($this->gerant)->get(route('versements.index'))->assertOk()->assertSee('Sekou TOURE')->assertSee('5 000 GNF');
        $this->actingAs($this->gerant)->get(route('versements.index', ['onglet' => 'agents', 'filtre' => 'a_payer']))->assertOk()->assertSee('Mariama SYLLA')->assertSee('7 000 GNF');
        $this->actingAs($this->gerant)->get(route('paiements.index'))->assertOk()->assertSee($p->code);
        $this->actingAs($this->gerant)->get(route('paiements.index', ['beneficiaire' => "agent:{$this->agent->id}", 'mode' => 'especes']))->assertSee($p->code);
        $this->actingAs($this->gerant)->get(route('paiements.edit', $p))->assertOk();
        $this->actingAs($this->gerant)->get(route('agents.show', $this->agent))->assertOk()->assertSee('Reste à payer');
        $this->actingAs($this->gerant)->get(route('superviseurs.show', $this->sup))->assertOk()->assertSee('Reste à payer');
        $this->actingAs($this->gerant)->get(route('dashboard'))->assertOk()->assertSee('restent à payer');
    }

    public function test_modification_et_suppression(): void
    {
        $p = Paiement::create(['beneficiaire_type' => 'agent', 'beneficiaire_id' => $this->agent->id, 'date_paiement' => now(), 'montant' => 3000, 'mode' => 'especes']);

        $this->actingAs($this->gerant)->put(route('paiements.update', $p), [
            'beneficiaire' => "superviseur:{$this->sup->id}", 'montant' => 4500, 'date_paiement' => now()->format('Y-m-d'), 'mode' => 'virement',
        ])->assertRedirect(route('paiements.show', $p));

        $p->refresh();
        $this->assertSame('superviseur', $p->beneficiaire_type);
        $this->assertSame(4500, $p->montant);

        $this->actingAs($this->gerant)->delete(route('paiements.destroy', $p))->assertRedirect(route('paiements.index'));
        $this->assertSame(0, Paiement::count());
    }
}
