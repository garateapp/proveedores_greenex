<?php

namespace Database\Factories;

use App\Models\Contratista;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trabajador>
 */
class TrabajadorFactory extends Factory
{
    public function definition(): array
    {
        $contratista = Contratista::factory()->create();
        $documento = $this->faker->unique()->numerify('########');

        return [
            'id' => $this->faker->unique()->numerify('########'),
            'documento' => $documento.'-'.$this->faker->randomDigit(),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['estado' => 'inactivo']);
    }
}
