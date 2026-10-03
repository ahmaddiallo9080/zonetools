<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Site;
use App\Models\Superviseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gerant = User::factory()->create();
    }

    public function test_les_pages_s_affichent(): void
    {
        $sup = Superviseur::factory()->create(['prenom' => 'Alpha', 'nom' => 'CONDE']);
        $site = Site::factory()->create(['superviseur_id' => $sup->id]);
        $agent = Agent::factory()->create(['site_id' => $site->id]);
        Agent::factory()->create(['site_id' => null]);

        $this->actingAs($this->gerant)->get(route('agents.index'))->assertOk()->assertSee($agent->nom_complet)->assertSee('Alpha CONDE');
        $this->actingAs($this->gerant)->get(route('agents.create', ['site' => $site->id]))->assertOk()->assertSee('AGT-003');
        $this->actingAs($this->gerant)->get(route('agents.show', $agent))->assertOk()->assertSee($site->nom)->assertSee('Alpha CONDE');
        $this->actingAs($this->gerant)->get(route('agents.edit', $agent))->assertOk();
        $this->actingAs($this->gerant)->get(route('sites.show', $site))->assertOk()->assertSee($agent->nom_complet)->assertSee('Alpha CONDE');
    }

    public function test_filtres_par_site_superviseur_et_sans_site(): void
    {
        $sup = Superviseur::factory()->create();
        $siteA = Site::factory()->create(['superviseur_id' => $sup->id]);
        $siteB = Site::factory()->create();
        $a = Agent::factory()->create(['site_id' => $siteA->id, 'prenom' => 'Agent', 'nom' => 'AAA']);
        $b = Agent::factory()->create(['site_id' => $siteB->id, 'prenom' => 'Agent', 'nom' => 'BBB']);
        $c = Agent::factory()->create(['site_id' => null, 'prenom' => 'Agent', 'nom' => 'CCC']);

        $this->actingAs($this->gerant)->get(route('agents.index', ['site' => $siteB->id]))
            ->assertSee('Agent BBB')->assertDontSee('Agent AAA')->assertDontSee('Agent CCC');

        $this->actingAs($this->gerant)->get(route('agents.index', ['superviseur' => $sup->id]))
            ->assertSee('Agent AAA')->assertDontSee('Agent BBB');

        $this->actingAs($this->gerant)->get(route('agents.index', ['site' => 'aucun']))
            ->assertSee('Agent CCC')->assertDontSee('Agent AAA');
    }

    public function test_creation_modification_suppression(): void
    {
        $site = Site::factory()->create();

        $this->actingAs($this->gerant)->post(route('agents.store'), [
            'prenom' => 'kadiatou', 'nom' => 'camara', 'telephone' => '+224 660 11 22 33',
            'site_id' => $site->id, 'taux_commission' => 10, 'statut' => 'actif',
        ])->assertRedirect()->assertSessionHas('success');

        $agent = Agent::first();
        $this->assertSame('AGT-001', $agent->code);
        $this->assertSame($site->id, $agent->site_id);
        $this->assertSame('Kadiatou CAMARA', $agent->nom_complet);

        $this->actingAs($this->gerant)->put(route('agents.update', $agent), [
            'prenom' => 'Kadiatou', 'nom' => 'CAMARA', 'telephone' => '+224 660 11 22 33',
            'site_id' => '', 'taux_commission' => 12, 'statut' => 'inactif',
        ])->assertRedirect(route('agents.show', $agent));

        $this->assertNull($agent->fresh()->site_id);
        $this->assertSame('inactif', $agent->fresh()->statut);

        $this->actingAs($this->gerant)->delete(route('agents.destroy', $agent))->assertRedirect(route('agents.index'));
        $this->assertSoftDeleted($agent);
    }

    public function test_un_site_supprime_ne_peut_pas_etre_choisi(): void
    {
        $site = Site::factory()->create();
        $site->delete();

        $this->actingAs($this->gerant)->post(route('agents.store'), [
            'prenom' => 'Test', 'nom' => 'TEST', 'telephone' => '620000000',
            'site_id' => $site->id, 'taux_commission' => 0, 'statut' => 'actif',
        ])->assertSessionHasErrors('site_id');
    }

    public function test_suppression_d_un_site_libere_ses_agents(): void
    {
        $site = Site::factory()->create();
        $agent = Agent::factory()->create(['site_id' => $site->id]);

        $this->actingAs($this->gerant)->delete(route('sites.destroy', $site));

        $this->assertNull($agent->fresh()->site_id);
    }
}
