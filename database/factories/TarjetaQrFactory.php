<?php

namespace Database\Factories;

use App\Enums\PerfilTarjetaQr;
use App\Models\User;
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
            'perfil' => PerfilTarjetaQr::Comensal,
            'admin_user_id' => null,
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function asignada(): static
    {
        return $this->state(fn (): array => ['estado' => 'asignada']);
    }

    public function bloqueada(): static
    {
        return $this->state(fn (): array => ['estado' => 'bloqueada']);
    }

    public function baja(): static
    {
        return $this->state(fn (): array => ['estado' => 'baja']);
    }

    /**
     * Solo cambia el perfil, para probar transiciones de perfil sobre una
     * tarjeta que el test deja en el estado que necesite.
     */
    public function administradorCasino(): static
    {
        return $this->state(fn (): array => ['perfil' => PerfilTarjetaQr::AdminCasino]);
    }

    public function deAdminCasino(?User $admin = null): static
    {
        return $this->state(fn (): array => [
            'estado' => 'asignada',
            'perfil' => PerfilTarjetaQr::AdminCasino,
            'admin_user_id' => $admin?->id ?? User::factory()->state([
                'role' => \App\Enums\UserRole::Admin,
            ]),
        ]);
    }
}
