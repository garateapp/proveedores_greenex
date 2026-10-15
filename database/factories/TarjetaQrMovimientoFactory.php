<?php

namespace Database\Factories;

use App\Enums\TipoMovimientoTarjetaQr;
use App\Models\TarjetaQr;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TarjetaQrMovimiento>
 */
class TarjetaQrMovimientoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tarjeta = TarjetaQr::factory()->create();

        return [
            'tarjeta_qr_id' => $tarjeta->id,
            'codigo_qr' => $tarjeta->codigo_qr,
            'tipo' => fake()->randomElement(TipoMovimientoTarjetaQr::cases()),
            'filtro' => 'FILTRO-'.fake()->numerify('###'),
            'filtro_nombre' => 'Filtro '.fake()->numberBetween(1, 10),
            'fecha' => now(),
        ];
    }
}
