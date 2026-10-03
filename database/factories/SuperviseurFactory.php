<?php

namespace Database\Factories;

use App\Models\Superviseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Superviseur>
 */
class SuperviseurFactory extends Factory
{
    protected $model = Superviseur::class;

    public function definition(): array
    {
        return [
            'nom' => mb_strtoupper(fake()->lastName()),
            'prenom' => fake()->firstName(),
            'telephone' => '+224 62' . fake()->unique()->numerify('# ## ## ##'),
            'email' => fake()->optional()->safeEmail(),
            'adresse' => fake()->optional()->streetAddress(),
            'date_embauche' => fake()->dateTimeBetween('-3 years', 'now'),
            'taux_commission' => fake()->randomElement([3, 5, 7.5, 10]),
            'statut' => fake()->randomElement(['actif', 'actif', 'actif', 'inactif']),
        ];
    }
}
