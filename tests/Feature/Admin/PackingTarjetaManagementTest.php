<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\TarjetaQr;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PackingTarjetaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_qr_card_and_view_the_cards_index(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->get('/admin/packing/tarjetas')
            ->assertOk()
            ->assertViewHas('page', function (array $page): bool {
                return ($page['component'] ?? null) === 'admin/packing/tarjetas/index';
            });

        $this->actingAs($admin)
            ->post('/admin/packing/tarjetas', [
                'numero_serie' => 'PACK-0001',
                'codigo_qr' => 'PACK-QR-0001',
                'estado' => 'disponible',
                'observaciones' => 'Primera tarjeta de packing',
            ])
            ->assertRedirect('/admin/packing/tarjetas');

        $this->assertDatabaseHas('tarjetas_qr', [
            'numero_serie' => 'PACK-0001',
            'codigo_qr' => 'PACK-QR-0001',
            'estado' => 'disponible',
        ]);
    }

    public function test_admin_can_assign_and_reassign_cards_keeping_assignment_history_consistent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 09:00:00'));

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $contratista = Contratista::factory()->create();

        $trabajadorUno = Trabajador::create([
            'id' => '11111111',
            'documento' => '11111111-1',
            'nombre' => 'Ana',
            'apellido' => 'Packing',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $trabajadorDos = Trabajador::create([
            'id' => '22222222',
            'documento' => '22222222-2',
            'nombre' => 'Luis',
            'apellido' => 'Packing',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $tarjetaUno = TarjetaQr::create([
            'numero_serie' => 'PACK-1001',
            'codigo_qr' => 'QR-PACK-1001',
            'estado' => 'disponible',
        ]);

        $tarjetaDos = TarjetaQr::create([
            'numero_serie' => 'PACK-1002',
            'codigo_qr' => 'QR-PACK-1002',
            'estado' => 'disponible',
        ]);

        $this->actingAs($admin)
            ->post("/admin/packing/tarjetas/{$tarjetaUno->id}/asignaciones", [
                'trabajador_id' => $trabajadorUno->id,
                'asignada_en' => '2026-04-06 09:00:00',
            ])
            ->assertRedirect('/admin/packing/tarjetas');

        $this->actingAs($admin)
            ->post("/admin/packing/tarjetas/{$tarjetaDos->id}/asignaciones", [
                'trabajador_id' => $trabajadorUno->id,
                'asignada_en' => '2026-04-06 10:00:00',
            ])
            ->assertRedirect('/admin/packing/tarjetas');

        $this->actingAs($admin)
            ->post("/admin/packing/tarjetas/{$tarjetaDos->id}/asignaciones", [
                'trabajador_id' => $trabajadorDos->id,
                'asignada_en' => '2026-04-06 11:00:00',
            ])
            ->assertRedirect('/admin/packing/tarjetas');

        $this->assertDatabaseHas('tarjeta_qr_asignaciones', [
            'tarjeta_qr_id' => $tarjetaUno->id,
            'trabajador_id' => $trabajadorUno->id,
            'asignada_por' => $admin->id,
            'desasignada_por' => $admin->id,
        ]);

        $this->assertDatabaseHas('tarjeta_qr_asignaciones', [
            'tarjeta_qr_id' => $tarjetaDos->id,
            'trabajador_id' => $trabajadorUno->id,
            'asignada_por' => $admin->id,
            'desasignada_por' => $admin->id,
        ]);

        $this->assertDatabaseHas('tarjeta_qr_asignaciones', [
            'tarjeta_qr_id' => $tarjetaDos->id,
            'trabajador_id' => $trabajadorDos->id,
            'asignada_por' => $admin->id,
            'desasignada_por' => null,
        ]);

        $this->assertDatabaseHas('tarjetas_qr', [
            'id' => $tarjetaUno->id,
            'estado' => 'disponible',
        ]);

        $this->assertDatabaseHas('tarjetas_qr', [
            'id' => $tarjetaDos->id,
            'estado' => 'asignada',
        ]);

        Carbon::setTestNow();
    }

    public function test_non_admin_users_cannot_access_the_packing_admin_module(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $this->actingAs($user)
            ->get('/admin/packing/tarjetas')
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/admin/packing/tarjetas', [
                'numero_serie' => 'PACK-9999',
                'codigo_qr' => 'PACK-QR-9999',
                'estado' => 'disponible',
            ])
            ->assertForbidden();
    }
}
