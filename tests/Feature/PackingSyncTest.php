<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\MarcacionPacking;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PackingSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sync_can_store_offline_markings_for_assigned_cards(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $contratista = Contratista::factory()->create();
        $trabajador = Trabajador::create([
            'id' => '33333333',
            'documento' => '33333333-3',
            'nombre' => 'Julia',
            'apellido' => 'Packing',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);
        $tarjeta = TarjetaQr::create([
            'numero_serie' => 'PACK-2001',
            'codigo_qr' => 'QR-PACK-2001',
            'estado' => 'asignada',
        ]);

        TarjetaQrAsignacion::create([
            'tarjeta_qr_id' => $tarjeta->id,
            'trabajador_id' => $trabajador->id,
            'asignada_en' => '2026-04-06 06:00:00',
            'asignada_por' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/packing/sync', [
                'sync_batch_id' => 'batch-001',
                'marcaciones' => [
                    [
                        'uuid' => 'mark-001',
                        'codigo_qr' => 'QR-PACK-2001',
                        'marcado_en' => '2026-04-06 08:00:00',
                        'device_id' => 'device-a',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'created' => 1,
                'ignored' => 0,
                'rejected' => 0,
            ]);

        $this->assertDatabaseHas('marcaciones_packing', [
            'uuid' => 'mark-001',
            'trabajador_id' => $trabajador->id,
            'tarjeta_qr_id' => $tarjeta->id,
            'numero_serie_snapshot' => 'PACK-2001',
            'codigo_qr_snapshot' => 'QR-PACK-2001',
            'device_id' => 'device-a',
            'sync_batch_id' => 'batch-001',
        ]);
    }

    public function test_sync_ignores_duplicate_markings_for_the_same_worker_within_120_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 10:00:00'));

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $contratista = Contratista::factory()->create();
        $trabajador = Trabajador::create([
            'id' => '44444444',
            'documento' => '44444444-4',
            'nombre' => 'Mario',
            'apellido' => 'Packing',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);
        $tarjeta = TarjetaQr::create([
            'numero_serie' => 'PACK-3001',
            'codigo_qr' => 'QR-PACK-3001',
            'estado' => 'asignada',
        ]);

        $asignacion = TarjetaQrAsignacion::create([
            'tarjeta_qr_id' => $tarjeta->id,
            'trabajador_id' => $trabajador->id,
            'asignada_en' => '2026-04-06 06:00:00',
            'asignada_por' => $admin->id,
        ]);

        MarcacionPacking::create([
            'uuid' => 'mark-existing',
            'trabajador_id' => $trabajador->id,
            'tarjeta_qr_id' => $tarjeta->id,
            'tarjeta_qr_asignacion_id' => $asignacion->id,
            'numero_serie_snapshot' => 'PACK-3001',
            'codigo_qr_snapshot' => 'QR-PACK-3001',
            'marcado_en' => '2026-04-06 08:00:00',
            'registrado_por' => $admin->id,
            'device_id' => 'device-a',
            'sync_batch_id' => 'batch-existing',
            'sincronizado_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/packing/sync', [
                'sync_batch_id' => 'batch-002',
                'marcaciones' => [
                    [
                        'uuid' => 'mark-duplicate',
                        'codigo_qr' => 'QR-PACK-3001',
                        'marcado_en' => '2026-04-06 09:30:00',
                        'device_id' => 'device-a',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'created' => 0,
                'ignored' => 1,
                'rejected' => 0,
            ]);

        $this->assertDatabaseMissing('marcaciones_packing', [
            'uuid' => 'mark-duplicate',
        ]);

        $this->assertSame(1, MarcacionPacking::count());

        Carbon::setTestNow();
    }

    public function test_sync_rejects_cards_without_an_active_assignment_at_the_marking_time(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $tarjeta = TarjetaQr::create([
            'numero_serie' => 'PACK-4001',
            'codigo_qr' => 'QR-PACK-4001',
            'estado' => 'disponible',
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/packing/sync', [
                'sync_batch_id' => 'batch-003',
                'marcaciones' => [
                    [
                        'uuid' => 'mark-rejected',
                        'codigo_qr' => $tarjeta->codigo_qr,
                        'marcado_en' => '2026-04-06 08:30:00',
                        'device_id' => 'device-z',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'created' => 0,
                'ignored' => 0,
                'rejected' => 1,
            ]);

        $this->assertDatabaseMissing('marcaciones_packing', [
            'uuid' => 'mark-rejected',
        ]);
    }
}
