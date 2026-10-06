<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ValeLote>
 */
class ValeLoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => 'LOT-'.$this->faker->unique()->numerify('######'),
            'lote_id' => $this->faker->uuid(),
            'posicion' => 1,
            'administrador_id' => User::factory()->state(['role' => UserRole::Admin]),
            'centro_costo_id' => null,
            'emitido_en' => now(),
            'canjeado_en' => null,
        ];
    }

    public function canjeado(): static
    {
        return $this->state(fn (): array => [
            'canjeado_en' => now()->subMinutes(5),
            'canjeado_por' => User::factory()->state(['role' => UserRole::Admin])->create()->id,
        ]);
    }
}
