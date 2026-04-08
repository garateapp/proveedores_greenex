<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UbicacionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_existing_ubicacion(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $ubicacion = Ubicacion::factory()->create([
            'nombre' => 'UNITEC 1',
            'codigo' => 'UNI1',
            'descripcion' => 'Área principal',
            'tipo' => 'principal',
            'orden' => 1,
            'activa' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.ubicaciones.index'))
            ->put(route('admin.ubicaciones.update', $ubicacion), [
                'padre_id' => null,
                'nombre' => 'UNITEC 1 ACTUALIZADA',
                'codigo' => 'UNI1-ACT',
                'descripcion' => 'Área principal actualizada',
                'tipo' => 'principal',
                'orden' => 3,
                'activa' => true,
            ]);

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ubicaciones', [
            'id' => $ubicacion->id,
            'nombre' => 'UNITEC 1 ACTUALIZADA',
            'codigo' => 'UNI1-ACT',
            'descripcion' => 'Área principal actualizada',
            'tipo' => 'principal',
            'orden' => 3,
            'activa' => true,
        ]);
    }
}
