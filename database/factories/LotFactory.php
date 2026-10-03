<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Forfait;
use App\Models\Lot;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lot>
 */
class LotFactory extends Factory
{
    protected $model = Lot::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'agent_id' => fn (array $attr) => Agent::factory()->create(['site_id' => $attr['site_id']])->id,
            'superviseur_id' => fn (array $attr) => Site::find($attr['site_id'])?->superviseur_id,
            'date_remise' => fake()->dateTimeBetween('-2 months', 'now'),
            'taux_agent' => 10,
            'taux_superviseur' => 5,
            'statut' => Lot::EN_COURS,
        ];
    }

    /** Ajoute des lignes (forfait => quantité) et recalcule les totaux. */
    public function avecLignes(array $lignes = []): static
    {
        return $this->afterCreating(function (Lot $lot) use ($lignes) {
            if (empty($lignes)) {
                $lignes = [Forfait::factory()->create()->id => fake()->numberBetween(20, 100)];
            }
            foreach ($lignes as $forfaitId => $quantite) {
                $forfait = Forfait::find($forfaitId);
                $lot->lignes()->create(['forfait_id' => $forfaitId, 'quantite' => $quantite, 'prix_unitaire' => $forfait->prixPour($lot->site_id)]);
            }
            $lot->recalculerTotaux();
        });
    }
}
