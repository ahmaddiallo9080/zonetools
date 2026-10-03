<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Site;
use App\Models\Superviseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperviseurTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gerant = User::factory()->create();
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'prenom' => 'mamadou',
            'nom' => 'diallo',
            'telephone' => '+224 620 00 00 01',
            'taux_commission' => '5,5',
            'statut' => 'actif',
        ], $surcharge);
    }

    public function test_les_pages_s_affichent(): void
    {
        $sup = Superviseur::factory()->create();
        $site = Site::factory()->create(['superviseur_id' => $sup->id]);
        Agent::factory()->create(['site_id' => $site->id, 'prenom' => 'Fatou']);

        $this->actingAs($this->gerant)->get(route('superviseurs.index'))->assertOk()->assertSee($sup->nom_complet);
        $this->actingAs($this->gerant)->get(route('superviseurs.create'))->assertOk()->assertSee('SUP-002');
        $this->actingAs($this->gerant)->get(route('superviseurs.show', $sup))->assertOk()->assertSee($site->nom)->assertSee('Fatou');
        $this->actingAs($this->gerant)->get(route('superviseurs.edit', $sup))->assertOk();
    }

    public function test_recherche_par_nom_complet_et_filtre_statut(): void
    {
        Superviseur::factory()->create(['prenom' => 'Ibrahima', 'nom' => 'SOW', 'statut' => 'actif']);
        Superviseur::factory()->create(['prenom' => 'Aissatou', 'nom' => 'BAH', 'statut' => 'inactif']);

        $this->actingAs($this->gerant)->get(route('superviseurs.index', ['q' => 'Ibrahima SOW']))
            ->assertSee('Ibrahima SOW')->assertDontSee('Aissatou BAH');

        $this->actingAs($this->gerant)->get(route('superviseurs.index', ['statut' => 'inactif', 'tri' => 'sites_count']))
            ->assertSee('Aissatou BAH')->assertDontSee('Ibrahima SOW');
    }

    public function test_creation_avec_plusieurs_sites(): void
    {
        [$s1, $s2, $s3] = Site::factory()->count(3)->create()->all();

        $this->actingAs($this->gerant)
            ->post(route('superviseurs.store'), $this->donnees(['sites' => [$s1->id, $s2->id]]))
            ->assertRedirect()->assertSessionHas('success');

        $sup = Superviseur::first();
        $this->assertSame('SUP-001', $sup->code);
        $this->assertSame('DIALLO', $sup->nom);      // nom en majuscules
        $this->assertSame('Mamadou', $sup->prenom);  // prénom capitalisé
        $this->assertEquals(5.5, $sup->taux_commission); // virgule acceptée
        $this->assertEqualsCanonicalizing([$s1->id, $s2->id], $sup->sites()->pluck('id')->all());
        $this->assertNull($s3->fresh()->superviseur_id);
    }

    public function test_modification_resynchronise_les_sites(): void
    {
        $sup = Superviseur::factory()->create();
        [$s1, $s2] = Site::factory()->count(2)->create(['superviseur_id' => $sup->id])->all();
        $s3 = Site::factory()->create();

        $this->actingAs($this->gerant)
            ->put(route('superviseurs.update', $sup), $this->donnees(['telephone' => $sup->telephone, 'sites' => [$s2->id, $s3->id]]))
            ->assertRedirect(route('superviseurs.show', $sup));

        $this->assertNull($s1->fresh()->superviseur_id);
        $this->assertSame($sup->id, $s2->fresh()->superviseur_id);
        $this->assertSame($sup->id, $s3->fresh()->superviseur_id);
    }

    public function test_validation(): void
    {
        Superviseur::factory()->create(['telephone' => '+224 620 00 00 01']);

        $this->actingAs($this->gerant)->post(route('superviseurs.store'), $this->donnees())
            ->assertSessionHasErrors('telephone');

        $this->actingAs($this->gerant)->post(route('superviseurs.store'), $this->donnees([
            'telephone' => '+224 620 99 99 99', 'nom' => '', 'taux_commission' => 150, 'sites' => [999],
        ]))->assertSessionHasErrors(['nom', 'taux_commission', 'sites.0']);
    }

    public function test_suppression_libere_les_sites(): void
    {
        $sup = Superviseur::factory()->create();
        $site = Site::factory()->create(['superviseur_id' => $sup->id]);

        $this->actingAs($this->gerant)->delete(route('superviseurs.destroy', $sup))
            ->assertRedirect(route('superviseurs.index'));

        $this->assertSoftDeleted($sup);
        $this->assertNull($site->fresh()->superviseur_id);
    }
}
