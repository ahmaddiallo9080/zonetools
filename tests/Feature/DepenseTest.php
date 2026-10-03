<?php

namespace Tests\Feature;

use App\Models\Depense;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepenseTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->gerant = User::factory()->create();
        $this->site = Site::factory()->create(['nom' => 'Hotspot Kipe', 'statut' => 'actif']);
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'site_id' => $this->site->id,
            'date_depense' => now()->format('Y-m-d'),
            'categorie' => 'internet',
            'libelle' => 'Recharge fibre octobre',
            'montant' => '500 000',
            'mode' => 'orange_money',
            'fournisseur' => 'Orange Guinée',
        ], $surcharge);
    }

    public function test_creation_avec_justificatif(): void
    {
        $this->actingAs($this->gerant)->get(route('depenses.create', ['site' => $this->site->id]))->assertOk()->assertSee('DEP-001');

        $this->actingAs($this->gerant)->post(route('depenses.store'), $this->donnees([
            'fichier' => UploadedFile::fake()->image('facture.jpg'),
        ]))->assertRedirect()->assertSessionHas('success');

        $depense = Depense::first();
        $this->assertSame('DEP-001', $depense->code);
        $this->assertSame(500000, $depense->montant);
        $this->assertSame($this->site->id, $depense->site_id);
        $this->assertNotNull($depense->justificatif);
        Storage::disk('local')->assertExists($depense->justificatif);

        $this->actingAs($this->gerant)->get(route('depenses.justificatif', $depense))->assertOk();
        $this->actingAs($this->gerant)->get(route('depenses.show', $depense))->assertOk()->assertSee('500 000 GNF')->assertSee('Hotspot Kipe');
    }

    public function test_depense_generale_et_enregistrer_puis_ajouter(): void
    {
        $this->actingAs($this->gerant)->post(route('depenses.store'), $this->donnees(['site_id' => 'general', 'encore' => 1]))
            ->assertRedirect(route('depenses.create'));

        $this->assertNull(Depense::first()->site_id);
        $this->assertSame('Général (tous les sites)', Depense::first()->site_label);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->gerant)->post(route('depenses.store'), $this->donnees([
            'site_id' => '', 'categorie' => 'vacances', 'libelle' => '', 'montant' => '0',
            'date_depense' => now()->addDay()->format('Y-m-d'), 'fichier' => UploadedFile::fake()->create('virus.exe', 10),
        ]))->assertSessionHasErrors(['categorie', 'libelle', 'montant', 'date_depense', 'fichier']);

        $this->assertSame(0, Depense::count());
    }

    public function test_liste_filtres_et_totaux(): void
    {
        $autre = Site::factory()->create(['nom' => 'Hotspot Matam']);
        Depense::factory()->create(['site_id' => $this->site->id, 'categorie' => 'internet', 'libelle' => 'Fibre Kipe', 'montant' => 300000, 'date_depense' => now()]);
        Depense::factory()->create(['site_id' => $autre->id, 'categorie' => 'carburant', 'libelle' => 'Gasoil Matam', 'montant' => 200000, 'date_depense' => now()]);
        Depense::factory()->create(['site_id' => null, 'categorie' => 'transport', 'libelle' => 'Tournee', 'montant' => 50000, 'date_depense' => now()]);

        $this->actingAs($this->gerant)->get(route('depenses.index'))
            ->assertOk()->assertSee('550 000 GNF')->assertSee('Fibre Kipe')->assertSee('Gasoil Matam');

        $this->actingAs($this->gerant)->get(route('depenses.index', ['site' => $this->site->id]))
            ->assertSee('Fibre Kipe')->assertDontSee('Gasoil Matam')->assertSee('300 000 GNF');

        $this->actingAs($this->gerant)->get(route('depenses.index', ['site' => 'general']))
            ->assertSee('Tournee')->assertDontSee('Fibre Kipe');

        $this->actingAs($this->gerant)->get(route('depenses.index', ['categorie' => 'carburant', 'q' => 'Gasoil', 'du' => now()->subDay()->format('Y-m-d')]))
            ->assertSee('Gasoil Matam')->assertDontSee('Fibre Kipe');

        // Intégrations : fiche site, statistiques, tableau de bord
        $this->actingAs($this->gerant)->get(route('sites.show', $this->site))->assertSee('Fibre Kipe');
        $this->actingAs($this->gerant)->get(route('statistiques.index', ['periode' => 'mois']))->assertOk()->assertSee('Dépenses par catégorie')->assertSee('550 000 GNF');
        $this->actingAs($this->gerant)->get(route('dashboard'))->assertOk()->assertSee('Dépenses du mois')->assertSee('550 000 GNF');
    }

    public function test_modification_remplacement_du_justificatif_et_suppression(): void
    {
        $this->actingAs($this->gerant)->post(route('depenses.store'), $this->donnees(['fichier' => UploadedFile::fake()->image('a.jpg')]));
        $depense = Depense::first();
        $ancien = $depense->justificatif;

        $this->actingAs($this->gerant)->get(route('depenses.edit', $depense))->assertOk();
        $this->actingAs($this->gerant)->put(route('depenses.update', $depense), $this->donnees([
            'montant' => 450000, 'fichier' => UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf'),
        ]))->assertRedirect(route('depenses.show', $depense));

        $depense->refresh();
        $this->assertSame(450000, $depense->montant);
        Storage::disk('local')->assertMissing($ancien);
        Storage::disk('local')->assertExists($depense->justificatif);

        $this->actingAs($this->gerant)->put(route('depenses.update', $depense), $this->donnees(['supprimer_justificatif' => 1]));
        $this->assertNull($depense->fresh()->justificatif);

        $this->actingAs($this->gerant)->delete(route('depenses.destroy', $depense))->assertRedirect(route('depenses.index'));
        $this->assertSoftDeleted($depense);
    }
}
