<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CentroCosto>
 */
class CentroCostoFactory extends Factory
{
    public function definition(): array
    {
        $numero = $this->faker->unique()->numberBetween(1000, 99999);

        return [
            'codigo' => 'CC-'.$numero,
            'nombre' => $this->faker->words(3, true),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
