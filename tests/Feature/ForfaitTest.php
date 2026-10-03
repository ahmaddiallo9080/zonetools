<?php

namespace Tests\Feature;

use App\Models\Forfait;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForfaitTest extends TestCase
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
            'nom' => 'Pass 1 jour',
            'duree_valeur' => 1,
            'duree_unite' => 'jour',
            'nb_appareils' => 2,
            'prix' => '5 000',
            'couleur' => 'green',
            'statut' => 'actif',
        ], $surcharge);
    }

    public function test_les_pages_s_affichent(): void
    {
        $forfait = Forfait::factory()->create(['nom' => 'Pass test', 'prix' => 2000]);
        $site = Site::factory()->create();

        $this->actingAs($this->gerant)->get(route('forfaits.index'))->assertOk()->assertSee('Pass test')->assertSee('2 000 GNF');
        $this->actingAs($this->gerant)->get(route('forfaits.create'))->assertOk()->assertSee('FOR-002');
        $this->actingAs($this->gerant)->get(route('forfaits.show', $forfait))->assertOk()->assertSee($site->nom);
        $this->actingAs($this->gerant)->get(route('forfaits.edit', $forfait))->assertOk();
        $this->actingAs($this->gerant)->get(route('sites.show', $site))->assertOk()->assertSee('Pass test')->assertSee('2 000 GNF');
    }

    public function test_creation_avec_prix_particuliers(): void
    {
        [$kipe, $matam] = Site::factory()->count(2)->create()->all();

        $this->actingAs($this->gerant)->post(route('forfaits.store'), $this->donnees([
            'prix_sites' => [
                ['site_id' => $kipe->id, 'prix' => '6 000'],
                ['site_id' => '', 'prix' => ''], // ligne vide ignorée
            ],
        ]))->assertRedirect()->assertSessionHas('success');

        $forfait = Forfait::first();
        $this->assertSame('FOR-001', $forfait->code);
        $this->assertSame(5000, $forfait->prix);
        $this->assertSame(1440, $forfait->duree_minutes);
        $this->assertSame('1 jour', $forfait->duree_label);
        $this->assertSame(6000, $forfait->prixPour($kipe));
        $this->assertSame(5000, $forfait->prixPour($matam)); // prix de base
        $this->assertSame(5000, $forfait->prixPour(null));
    }

    public function test_modification_resynchronise_les_prix_particuliers(): void
    {
        [$s1, $s2] = Site::factory()->count(2)->create()->all();
        $forfait = Forfait::factory()->create(['nom' => 'Pass 1 jour']);
        $forfait->sites()->sync([$s1->id => ['prix' => 900]]);

        $this->actingAs($this->gerant)->put(route('forfaits.update', $forfait), $this->donnees([
            'duree_valeur' => 2, 'duree_unite' => 'semaine',
            'prix_sites' => [['site_id' => $s2->id, 'prix' => 4500]],
        ]))->assertRedirect(route('forfaits.show', $forfait));

        $forfait->refresh();
        $this->assertSame(20160, $forfait->duree_minutes);
        $this->assertSame('2 semaines', $forfait->duree_label);
        $this->assertSame(5000, $forfait->prixPour($s1));
        $this->assertSame(4500, $forfait->prixPour($s2));
    }

    public function test_validation(): void
    {
        $site = Site::factory()->create();
        Forfait::factory()->create(['nom' => 'Pass 1 jour']);

        $this->actingAs($this->gerant)->post(route('forfaits.store'), $this->donnees())
            ->assertSessionHasErrors('nom');

        $this->actingAs($this->gerant)->post(route('forfaits.store'), $this->donnees([
            'nom' => 'Autre', 'duree_valeur' => 0, 'duree_unite' => 'annee', 'nb_appareils' => 0, 'prix' => '',
            'prix_sites' => [['site_id' => $site->id, 'prix' => 100], ['site_id' => $site->id, 'prix' => 200], ['site_id' => 999, 'prix' => '']],
        ]))->assertSessionHasErrors([
            'duree_valeur', 'duree_unite', 'nb_appareils', 'prix',
            'prix_sites.0.site_id', 'prix_sites.2.site_id', 'prix_sites.2.prix',
        ]);
    }

    public function test_tri_par_duree_et_filtre(): void
    {
        Forfait::factory()->create(['nom' => 'Long', 'duree_valeur' => 1, 'duree_unite' => 'mois']);
        Forfait::factory()->create(['nom' => 'Court', 'duree_valeur' => 30, 'duree_unite' => 'minute', 'statut' => 'inactif']);

        $this->actingAs($this->gerant)->get(route('forfaits.index'))->assertSeeInOrder(['Court', 'Long']);
        $this->actingAs($this->gerant)->get(route('forfaits.index', ['tri' => 'duree_minutes', 'sens' => 'desc']))->assertSeeInOrder(['Long', 'Court']);
        $this->actingAs($this->gerant)->get(route('forfaits.index', ['statut' => 'inactif']))->assertSee('Court')->assertDontSee('>Long<', false);
    }

    public function test_suppression(): void
    {
        $forfait = Forfait::factory()->create();

        $this->actingAs($this->gerant)->delete(route('forfaits.destroy', $forfait))->assertRedirect(route('forfaits.index'));
        $this->assertSoftDeleted($forfait);
    }
}
