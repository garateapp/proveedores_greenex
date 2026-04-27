<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ControlAccessLog>
 */
class ControlAccessLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entrada = Carbon::instance(fake()->dateTimeBetween('-1 month', 'now'));

        return [
            'fecha' => $entrada->copy()->startOfDay(),
            'personal_id' => (string) fake()->unique()->numberBetween(10000000, 25000000),
            'nombre' => fake()->name(),
            'departamento' => fake()->company(),
            'primera_entrada' => $entrada,
            'ultima_salida' => $entrada->copy()->addHours(9),
            'pin' => fake()->optional()->numerify('PIN-####'),
        ];
    }
}
