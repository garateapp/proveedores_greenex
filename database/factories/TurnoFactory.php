<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Turno>
 */
class TurnoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->date(),
            'nombre' => fake()->unique()->randomElement(['Turno Dia', 'Turno Tarde', 'Turno Noche']).' '.fake()->unique()->numberBetween(1, 999),
            'hora_inicio' => fake()->time('H:i'),
            'hora_fin' => fake()->time('H:i'),
            'descripcion' => fake()->optional()->sentence(),
            'activo' => true,
        ];
    }
}
