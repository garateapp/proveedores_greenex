<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\EstadoPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadoPagoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_estado_pago(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $estadoPago = $this->createEstadoPago();

        $response = $this->actingAs($admin)->delete(route('estados-pago.destroy', $estadoPago));

        $response->assertRedirect(route('estados-pago.index'));
        $this->assertSoftDeleted('estados_pago', [
            'id' => $estadoPago->id,
        ]);
    }

    public function test_non_admin_cannot_delete_estado_pago(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);
        $estadoPago = $this->createEstadoPago();

        $response = $this->actingAs($user)->delete(route('estados-pago.destroy', $estadoPago));

        $response->assertForbidden();
        $this->assertDatabaseHas('estados_pago', [
            'id' => $estadoPago->id,
            'deleted_at' => null,
        ]);
    }

    private function createEstadoPago(): EstadoPago
    {
        $contratista = Contratista::factory()->create();
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        return EstadoPago::create([
            'contratista_id' => $contratista->id,
            'numero_documento' => fake()->numerify('EP-####'),
            'fecha_documento' => now()->toDateString(),
            'monto' => 125000.50,
            'estado' => 'recibido',
            'actualizado_por' => $admin->id,
        ]);
    }
}
