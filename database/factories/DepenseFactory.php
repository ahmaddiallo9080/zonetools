<?php

namespace Database\Factories;

use App\Models\Depense;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Depense>
 */
class DepenseFactory extends Factory
{
    protected $model = Depense::class;

    public function definition(): array
    {
        $exemples = [
            'internet' => ['Recharge abonnement fibre', 'Orange Guinée', [300000, 500000, 800000]],
            'electricite' => ['Facture électricité', 'EDG', [50000, 100000, 150000]],
            'carburant' => ['Gasoil groupe électrogène', 'Station Total', [100000, 200000]],
            'loyer' => ["Loyer de l'emplacement", null, [250000, 400000]],
            'maintenance' => ['Remplacement routeur', 'Boutique informatique', [150000, 350000]],
            'impression' => ['Impression des tickets', 'Imprimerie', [50000, 80000]],
        ];
        $categorie = fake()->randomElement(array_keys($exemples));
        [$libelle, $fournisseur, $montants] = $exemples[$categorie];

        return [
            'site_id' => Site::factory(),
            'date_depense' => fake()->dateTimeBetween('-3 months', 'now'),
            'categorie' => $categorie,
            'libelle' => $libelle,
            'montant' => fake()->randomElement($montants),
            'mode' => fake()->randomElement(['especes', 'orange_money']),
            'fournisseur' => $fournisseur,
        ];
    }
}
