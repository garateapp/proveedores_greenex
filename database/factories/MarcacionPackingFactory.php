<?php

namespace Database\Factories;

use App\Models\TarjetaQrAsignacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarcacionPacking>
 */
class MarcacionPackingFactory extends Factory
{
    public function definition(): array
    {
        $asignacion = TarjetaQrAsignacion::factory()->create();

        return [
            'uuid' => (string) fake()->uuid(),
            'trabajador_id' => $asignacion->trabajador_id,
            'tarjeta_qr_id' => $asignacion->tarjeta_qr_id,
            'tarjeta_qr_asignacion_id' => $asignacion->id,
            'numero_serie_snapshot' => $asignacion->tarjetaQr->numero_serie,
            'codigo_qr_snapshot' => $asignacion->tarjetaQr->codigo_qr,
            'marcado_en' => Carbon::now(),
            'registrado_por' => User::factory(),
            'device_id' => 'device-'.fake()->bothify('??##'),
            'sync_batch_id' => 'batch-'.fake()->bothify('??##'),
            'latitud' => null,
            'longitud' => null,
            'ubicacion_texto' => fake()->optional()->city(),
            'metadata' => null,
            'sincronizado_at' => Carbon::now(),
        ];
    }
}
