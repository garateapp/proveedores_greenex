<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure home page renders the custom Inertia welcome screen.
     */
    public function test_welcome_page_renders_inertia_component(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->has('canRegister')
            );
    }
}
