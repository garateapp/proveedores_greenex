<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ubicacion>
 */
class UbicacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'codigo' => 'UB-'.fake()->unique()->randomNumber(4, true),
            'descripcion' => fake()->optional()->sentence(),
            'tipo' => 'principal',
            'padre_id' => null,
            'orden' => fake()->numberBetween(0, 100),
            'activa' => true,
        ];
    }

    public function principal(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'principal',
            'padre_id' => null,
        ]);
    }

    public function secundaria(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'secundaria',
            'padre_id' => null, // Will be set by the test
        ]);
    }

    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }
}
