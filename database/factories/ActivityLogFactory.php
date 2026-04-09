<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        $events = ['login', 'logout', 'page_view', 'password_change', 'profile_update'];
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge'];
        $platforms = ['Windows', 'Mac OS', 'Linux', 'Android', 'iOS'];
        $deviceTypes = ['desktop', 'mobile', 'tablet'];

        return [
            'user_id' => User::factory(),
            'event' => $this->faker->randomElement($events),
            'url' => $this->faker->url,
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'device_type' => $this->faker->randomElement($deviceTypes),
            'browser' => $this->faker->randomElement($browsers),
            'platform' => $this->faker->randomElement($platforms),
            'metadata' => [
                'route' => $this->faker->slug,
                'referer' => $this->faker->url,
            ],
            'created_at' => $this->faker->dateTimeBetween('-30 days'),
        ];
    }

    public function login(): self
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'login',
        ]);
    }

    public function logout(): self
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'logout',
        ]);
    }

    public function pageView(): self
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'page_view',
        ]);
    }
}
