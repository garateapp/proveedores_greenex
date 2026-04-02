<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContratistaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_contratista_estado_to_bloqueado(): void
    {
        $contratista = Contratista::factory()->create([
            'rut' => '12345678-9',
            'razon_social' => 'Proveedor Uno',
            'direccion' => 'Camino 123',
            'comuna' => 'Santiago',
            'region' => 'Metropolitana',
            'telefono' => '+56912345678',
            'email' => 'proveedor@example.com',
            'estado' => 'activo',
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.contratistas.update', $contratista),
            [
                'rut' => '12345678-9',
                'razon_social' => 'Proveedor Uno',
                'nombre_fantasia' => 'Proveedor Uno SPA',
                'direccion' => 'Camino 123',
                'comuna' => 'Santiago',
                'region' => 'Metropolitana',
                'telefono' => '+56912345678',
                'email' => 'proveedor@example.com',
                'estado' => 'bloqueado',
                'observaciones' => 'Bloqueado por incumplimiento.',
            ],
        );

        $response->assertRedirect(route('admin.contratistas.index'));

        $contratista->refresh();

        $this->assertSame('bloqueado', $contratista->estado);
    }
}
