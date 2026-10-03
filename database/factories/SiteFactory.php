<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $villes = ['Conakry', 'Kindia', 'Boké', 'Labé', 'Kankan', 'Mamou', 'Nzérékoré'];
        $quartiers = ['Kaloum', 'Dixinn', 'Matam', 'Ratoma', 'Matoto', 'Kipé', 'Lambanyi', 'Hamdallaye'];

        return [
            'nom' => 'Hotspot ' . fake()->unique()->lastName(),
            'ville' => fake()->randomElement($villes),
            'quartier' => fake()->randomElement($quartiers),
            'adresse' => fake()->streetAddress(),
            'telephone' => '+224 6' . fake()->numerify('## ## ## ##'),
            'date_ouverture' => fake()->dateTimeBetween('-3 years', 'now'),
            'statut' => fake()->randomElement(array_keys(Site::STATUTS)),
            'notes' => null,
        ];
    }
}
