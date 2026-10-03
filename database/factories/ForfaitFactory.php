<?php

namespace Database\Factories;

use App\Models\Forfait;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Forfait>
 */
class ForfaitFactory extends Factory
{
    protected $model = Forfait::class;

    public function definition(): array
    {
        $unite = fake()->randomElement(['heure', 'jour', 'semaine']);
        $valeur = fake()->numberBetween(1, 5);

        return [
            'nom' => 'Pass ' . $valeur . ' ' . $unite . ' ' . fake()->unique()->numberBetween(1, 9999),
            'duree_valeur' => $valeur,
            'duree_unite' => $unite,
            'nb_appareils' => fake()->numberBetween(1, 3),
            'prix' => fake()->randomElement([1000, 2000, 5000, 10000, 25000]),
            'couleur' => fake()->randomElement(array_keys(Forfait::COULEURS)),
            'statut' => 'actif',
        ];
    }
}
