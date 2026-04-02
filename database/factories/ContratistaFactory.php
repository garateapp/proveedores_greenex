<?php

namespace Database\Factories;

use App\Models\Contratista;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contratista>
 */
class ContratistaFactory extends Factory
{
    protected $model = Contratista::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a valid Chilean RUT
        $rutNumber = fake()->numberBetween(10000000, 25000000);
        $dv = $this->calculateDV($rutNumber);
        $rut = $rutNumber.'-'.$dv;

        return [
            'rut' => $rut,
            'razon_social' => fake()->company().' Ltda.',
            'nombre_fantasia' => fake()->companySuffix().' '.fake()->word(),
            'direccion' => fake()->streetAddress(),
            'comuna' => fake()->city(),
            'region' => fake()->randomElement([
                'Región Metropolitana',
                'Región de Valparaíso',
                'Región del Biobío',
                'Región de La Araucanía',
                'Región del Maule',
                'Región de O\'Higgins',
            ]),
            'telefono' => '+569'.fake()->numberBetween(10000000, 99999999),
            'email' => fake()->unique()->safeEmail(),
            'estado' => fake()->randomElement(['activo', 'activo', 'activo', 'inactivo']), // 75% active
            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Calculate Chilean RUT verification digit.
     */
    private function calculateDV(int $rut): string
    {
        $suma = 0;
        $multiplo = 2;

        while ($rut > 0) {
            $suma += ($rut % 10) * $multiplo;
            $rut = intval($rut / 10);
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }

        $resto = $suma % 11;
        $dv = 11 - $resto;

        if ($dv == 11) {
            return '0';
        } elseif ($dv == 10) {
            return 'K';
        } else {
            return (string) $dv;
        }
    }

    /**
     * Indicate that the contratista is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'inactivo',
        ]);
    }

    /**
     * Indicate that the contratista is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'bloqueado',
        ]);
    }
}
