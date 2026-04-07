<?php

namespace Database\Factories;

use App\Models\Contratista;
use App\Models\TarjetaQr;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TarjetaQrAsignacion>
 */
class TarjetaQrAsignacionFactory extends Factory
{
    public function definition(): array
    {
        $contratista = Contratista::factory()->create();
        $trabajadorId = $this->faker->unique()->numerify('########');
        $trabajador = Trabajador::create([
            'id' => $trabajadorId,
            'documento' => $trabajadorId.'-'.$this->faker->randomDigit(),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        return [
            'tarjeta_qr_id' => TarjetaQr::factory(),
            'trabajador_id' => $trabajador->id,
            'asignada_por' => User::factory(),
            'asignada_en' => Carbon::now(),
            'desasignada_por' => null,
            'desasignada_en' => null,
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
