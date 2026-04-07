<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TarjetaQr>
 */
class TarjetaQrFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero_serie' => 'PACK-'.$this->faker->unique()->numerify('####'),
            'codigo_qr' => 'QR-PACK-'.$this->faker->unique()->numerify('####'),
            'estado' => 'disponible',
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
