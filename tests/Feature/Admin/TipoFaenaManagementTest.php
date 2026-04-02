<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\TipoFaena;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipoFaenaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_tipo_faena(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->post(route('tipo-faenas.store'), [
            'nombre' => 'Terreno',
            'codigo' => 'TERRENO',
            'descripcion' => 'Operaciones en terreno',
            'activo' => true,
        ]);

        $response->assertRedirect(route('tipo-faenas.index'));

        $this->assertDatabaseHas(TipoFaena::class, [
            'nombre' => 'Terreno',
            'codigo' => 'TERRENO',
            'activo' => true,
        ]);
    }
}
