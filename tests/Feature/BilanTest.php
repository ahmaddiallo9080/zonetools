<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Bilan;
use App\Models\Depense;
use App\Models\Forfait;
use App\Models\Lot;
use App\Models\Rapport;
use App\Models\Site;
use App\Models\Superviseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BilanTest extends TestCase
{
    use RefreshDatabase;

    private User $gerant;
    private Site $site;
    private Carbon $moisDernier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gerant = User::factory()->create();
        $sup = Superviseur::factory()->create();
        $this->site = Site::factory()->create(['nom' => 'Hotspot Kipe', 'superviseur_id' => $sup->id, 'statut' => 'actif']);
        $this->moisDernier = now()->subMonthNoOverflow()->startOfMonth();

        // Rapport du mois dernier : 100 tickets vendus à 1 000 = 100 000 GNF (10 % agent, 5 % sup), il manque 5 000
        $this->rapport($this->moisDernier->copy()->addDays(10), vendus: 100, defectueux: 4, verse: 85000);
        // Rapport du mois en cours (ne doit pas compter dans le mois dernier)
        $this->rapport(now()->startOfMonth(), vendus: 50, defectueux: 0, verse: 45000);

        Depense::factory()->create(['site_id' => $this->site->id, 'montant' => 20000, 'date_depense' => $this->moisDernier->copy()->addDays(3), 'libelle' => 'Fibre']);
        Depense::factory()->create(['site_id' => null, 'montant' => 7000, 'date_depense' => $this->moisDernier->copy()->addDays(5), 'libelle' => 'Tournee']);
    }

    private function rapport(Carbon $date, int $vendus, int $defectueux, int $verse): Rapport
    {
        $agent = Agent::factory()->create(['site_id' => $this->site->id]);
        $forfait = Forfait::factory()->create(['prix' => 1000]);
        $lot = Lot::factory()->avecLignes([$forfait->id => 110])->create([
            'site_id' => $this->site->id, 'agent_id' => $agent->id, 'superviseur_id' => $this->site->superviseur_id,
            'taux_agent' => 10, 'taux_superviseur' => 5, 'statut' => Lot::TERMINE, 'date_remise' => $date->copy()->subDays(5),
        ]);
        $rapport = Rapport::create(['lot_id' => $lot->id, 'date_rapport' => $date, 'montant_verse' => $verse]);
        $rapport->lignes()->create([
            'lot_ligne_id' => $lot->lignes->first()->id, 'forfait_id' => $forfait->id, 'quantite_remise' => 110,
            'prix_unitaire' => 1000, 'vendus' => $vendus, 'defectueux' => $defectueux,
        ]);
        $rapport->recalculer();

        return $rapport;
    }

    public function test_bilan_du_site_calcule_en_direct(): void
    {
        $this->actingAs($this->gerant)->get(route('bilans.show', [$this->moisDernier->format('Y-m'), $this->site]))
            ->assertOk()
            ->assertSee('Hotspot Kipe')
            ->assertSee('100 000 GNF')    // ventes
            ->assertSee('20 000 GNF')     // dépenses
            ->assertSee('65 000 GNF')     // bénéfice = 100 000 − 10 000 − 5 000 − 20 000
            ->assertSee('5 000 GNF')      // manquants
            ->assertSee('Clôturer le bilan');
    }

    public function test_cloture_fige_les_chiffres(): void
    {
        $mois = $this->moisDernier->format('Y-m');

        $this->actingAs($this->gerant)->post(route('bilans.cloturer', [$mois, $this->site]), ['observations' => 'Bon mois malgré une coupure.'])
            ->assertRedirect(route('bilans.show', [$mois, $this->site]));

        $bilan = Bilan::first();
        $this->assertSame(100000, (int) $bilan->ventes);
        $this->assertSame(65000, (int) $bilan->benefice);
        $this->assertSame(4, (int) $bilan->defectueux);
        $this->assertSame('Bon mois malgré une coupure.', $bilan->observations);

        // Une nouvelle dépense après clôture ne change pas le bilan figé, mais un avertissement s'affiche
        Depense::factory()->create(['site_id' => $this->site->id, 'montant' => 10000, 'date_depense' => $this->moisDernier->copy()->addDays(20)]);
        $this->actingAs($this->gerant)->get(route('bilans.show', [$mois, $this->site]))
            ->assertSee('65 000 GNF')->assertSee('ont été modifiés depuis la clôture');

        // Mettre à jour le bilan (re-clôture) : les nouveaux chiffres sont pris en compte
        $this->actingAs($this->gerant)->post(route('bilans.cloturer', [$mois, $this->site]), ['observations' => 'Bon mois malgré une coupure.']);
        $this->assertSame(1, Bilan::count());
        $this->assertSame(55000, (int) $bilan->fresh()->benefice);

        // Modifier les observations
        $this->actingAs($this->gerant)->patch(route('bilans.observations', [$mois, $this->site]), ['observations' => 'Corrigé']);
        $this->assertSame('Corrigé', $bilan->fresh()->observations);

        // Rouvrir
        $this->actingAs($this->gerant)->delete(route('bilans.rouvrir', [$mois, $this->site]));
        $this->assertSame(0, Bilan::count());
    }

    public function test_on_ne_cloture_pas_le_mois_en_cours(): void
    {
        $this->actingAs($this->gerant)->post(route('bilans.cloturer', [now()->format('Y-m'), $this->site]))
            ->assertSessionHas('error');
        $this->assertSame(0, Bilan::count());
    }

    public function test_bilan_du_reseau_impression_et_export(): void
    {
        $mois = $this->moisDernier->format('Y-m');

        $this->actingAs($this->gerant)->get(route('bilans.index', ['mois' => $mois]))
            ->assertOk()->assertSee('Hotspot Kipe')->assertSee('Dépenses générales')
            ->assertSee('58 000 GNF'); // bénéfice réseau = 65 000 − 7 000

        $this->actingAs($this->gerant)->get(route('bilans.index'))->assertOk(); // mois précédent par défaut
        $this->actingAs($this->gerant)->get(route('bilans.index', ['mois' => 'nimporte']))->assertOk();

        $this->actingAs($this->gerant)->get(route('bilans.global', $mois))->assertOk()->assertSee('Bilan global du réseau');

        $reponse = $this->actingAs($this->gerant)->get(route('bilans.export', $mois));
        $reponse->assertOk()->assertDownload("bilan-zonetools-{$mois}.csv");
        $csv = $reponse->streamedContent();
        $this->assertStringContainsString('Hotspot Kipe', $csv);
        $this->assertStringContainsString('TOTAL RÉSEAU', $csv);
        $this->assertStringContainsString('58000', $csv);
    }
}
