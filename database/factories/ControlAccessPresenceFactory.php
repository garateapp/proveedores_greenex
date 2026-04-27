<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ControlAccessPresence>
 */
class ControlAccessPresenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entryAt = Carbon::instance(fake()->dateTimeBetween('-1 week', 'now'));

        return [
            'personal_id' => (string) fake()->unique()->numberBetween(10000000, 25000000),
            'nombre' => fake()->name(),
            'departamento' => fake()->company(),
            'last_entry_at' => $entryAt,
            'last_exit_at' => fake()->boolean() ? $entryAt->copy()->addHours(9) : null,
            'last_event_id_pair' => fake()->optional()->uuid(),
            'pin' => fake()->optional()->numerify('PIN-####'),
        ];
    }
}
