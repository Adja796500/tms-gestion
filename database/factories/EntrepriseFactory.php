<?php

namespace Database\Factories;

use App\Models\Entreprise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entreprise>
 */
class EntrepriseFactory extends Factory
{
    protected $model = Entreprise::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->unique()->company(),
            'actif' => true,
        ];
    }
}
