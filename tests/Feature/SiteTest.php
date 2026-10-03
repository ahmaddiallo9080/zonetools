<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gerant = User::factory()->create();
    }

    public function test_un_invite_est_redirige_vers_la_connexion(): void
    {
        $this->get(route('sites.index'))->assertRedirect(route('login'));
    }

    public function test_la_liste_des_sites_s_affiche_et_se_filtre(): void
    {
        Site::factory()->create(['nom' => 'Hotspot Kipé', 'quartier' => 'Kipé', 'statut' => 'actif']);
        Site::factory()->create(['nom' => 'Hotspot Matam', 'quartier' => 'Matam', 'statut' => 'inactif']);

        $this->actingAs($this->gerant)->get(route('sites.index'))
            ->assertOk()->assertSee('Hotspot Kipé')->assertSee('Hotspot Matam');

        $this->actingAs($this->gerant)->get(route('sites.index', ['q' => 'Kipé']))
            ->assertOk()->assertSee('Hotspot Kipé')->assertDontSee('Hotspot Matam');

        $this->actingAs($this->gerant)->get(route('sites.index', ['statut' => 'inactif', 'tri' => 'ville', 'sens' => 'desc']))
            ->assertOk()->assertSee('Hotspot Matam')->assertDontSee('Hotspot Kipé');
    }

    public function test_les_pages_creation_fiche_et_modification_s_affichent(): void
    {
        $site = Site::factory()->create();

        $this->actingAs($this->gerant)->get(route('sites.create'))->assertOk()->assertSee('SITE-002');
        $this->actingAs($this->gerant)->get(route('sites.show', $site))->assertOk()->assertSee($site->nom);
        $this->actingAs($this->gerant)->get(route('sites.edit', $site))->assertOk()->assertSee($site->nom);
    }

    public function test_creation_d_un_site_avec_code_automatique(): void
    {
        $this->actingAs($this->gerant)->post(route('sites.store'), [
            'nom' => 'Hotspot Lambanyi',
            'ville' => 'Conakry',
            'statut' => 'actif',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('sites', ['nom' => 'Hotspot Lambanyi', 'code' => 'SITE-001']);
    }

    public function test_validation_nom_obligatoire_et_unique(): void
    {
        Site::factory()->create(['nom' => 'Hotspot Kaloum']);

        $this->actingAs($this->gerant)->post(route('sites.store'), ['nom' => '', 'statut' => 'actif'])
            ->assertSessionHasErrors('nom');

        $this->actingAs($this->gerant)->post(route('sites.store'), ['nom' => 'Hotspot Kaloum', 'statut' => 'actif'])
            ->assertSessionHasErrors('nom');

        $this->actingAs($this->gerant)->post(route('sites.store'), ['nom' => 'Autre', 'statut' => 'nimporte'])
            ->assertSessionHasErrors('statut');
    }

    public function test_modification_d_un_site(): void
    {
        $site = Site::factory()->create(['statut' => 'actif']);

        $this->actingAs($this->gerant)->put(route('sites.update', $site), [
            'nom' => $site->nom,
            'code' => 'kip-01',
            'statut' => 'maintenance',
        ])->assertRedirect(route('sites.show', $site));

        $this->assertDatabaseHas('sites', ['id' => $site->id, 'statut' => 'maintenance', 'code' => 'KIP-01']);
    }

    public function test_suppression_d_un_site(): void
    {
        $site = Site::factory()->create();

        $this->actingAs($this->gerant)->delete(route('sites.destroy', $site))
            ->assertRedirect(route('sites.index'));

        $this->assertSoftDeleted($site);
    }

    public function test_vider_le_code_conserve_l_ancien_code(): void
    {
        $site = Site::factory()->create(['code' => 'KIPE']);

        $this->actingAs($this->gerant)->put(route('sites.update', $site), [
            'nom' => $site->nom, 'code' => '', 'statut' => 'actif',
        ])->assertRedirect(route('sites.show', $site));

        $this->assertSame('KIPE', $site->fresh()->code);
    }
}
