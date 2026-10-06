<?php

namespace Database\Factories;

use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ValeAlmuerzo>
 */
class ValeAlmuerzoFactory extends Factory
{
    public function definition(): array
    {
        $trabajador = Trabajador::factory()->create();

        return [
            'token' => bin2hex(random_bytes(16)),
            'trabajador_id' => $trabajador->id,
            'tarjeta_qr_id' => null,
            'asignacion_id' => null,
            'contratista' => $trabajador->contratista?->razon_social,
            'centro_costo_id' => null,
            'emitido_en' => now(),
        ];
    }

    public function emitidoHace(int $horas): static
    {
        return $this->state(fn (): array => ['emitido_en' => now()->subHours($horas)]);
    }
}
