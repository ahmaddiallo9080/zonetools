<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'nom' => mb_strtoupper(fake()->lastName()),
            'prenom' => fake()->firstName(),
            'telephone' => '+224 66' . fake()->unique()->numerify('# ## ## ##'),
            'adresse' => fake()->optional()->streetAddress(),
            'date_embauche' => fake()->dateTimeBetween('-2 years', 'now'),
            'taux_commission' => fake()->randomElement([5, 10, 15]),
            'statut' => fake()->randomElement(['actif', 'actif', 'actif', 'inactif']),
        ];
    }
}
