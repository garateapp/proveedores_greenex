<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\MarcacionPacking;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
use App\Models\Trabajador;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarcacionQrApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_qr_marking_with_active_assignment(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $contratista = Contratista::factory()->create();

        $trabajador = Trabajador::create([
            'id' => '55555555',
            'documento' => '55555555-5',
            'nombre' => 'Ana',
            'apellido' => 'Cardenal',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $tarjeta = TarjetaQr::create([
            'numero_serie' => 'PACK-5001',
            'codigo_qr' => 'QR-PACK-5001',
            'estado' => 'asignada',
        ]);

        TarjetaQrAsignacion::create([
            'tarjeta_qr_id' => $tarjeta->id,
            'trabajador_id' => $trabajador->id,
            'asignada_en' => '2026-04-06 06:00:00',
            'asignada_por' => $admin->id,
        ]);

        $ubicacion = Ubicacion::factory()->principal()->create();

        $response = $this->postJson('/api/v1/asistencias/qr', [
            'codigo_qr' => 'QR-PACK-5001',
            'marcado_en' => '2026-04-06 08:10:00',
            'ubicacion_id' => $ubicacion->id,
            'device_id' => 'mobile-test-1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'created')
            ->assertJsonPath('data.trabajador.id', $trabajador->id)
            ->assertJsonPath('data.tarjeta.codigo_qr', 'QR-PACK-5001')
            ->assertJsonPath('data.ubicacion.id', $ubicacion->id);

        $this->assertDatabaseHas('marcaciones_packing', [
            'codigo_qr_snapshot' => 'QR-PACK-5001',
            'trabajador_id' => $trabajador->id,
            'ubicacion_id' => $ubicacion->id,
            'device_id' => 'mobile-test-1',
        ]);
    }

    public function test_it_ignores_duplicate_markings_within_120_minutes(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $contratista = Contratista::factory()->create();

        $trabajador = Trabajador::create([
            'id' => '66666666',
            'documento' => '66666666-6',
            'nombre' => 'Luis',
            'apellido' => 'Dup',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $tarjeta = TarjetaQr::create([
            'numero_serie' => 'PACK-6001',
            'codigo_qr' => 'QR-PACK-6001',
            'estado' => 'asignada',
        ]);

        $asignacion = TarjetaQrAsignacion::create([
            'tarjeta_qr_id' => $tarjeta->id,
            'trabajador_id' => $trabajador->id,
            'asignada_en' => '2026-04-06 06:00:00',
            'asignada_por' => $admin->id,
        ]);

        MarcacionPacking::create([
            'uuid' => 'existing-dup-1',
            'trabajador_id' => $trabajador->id,
            'tarjeta_qr_id' => $tarjeta->id,
            'tarjeta_qr_asignacion_id' => $asignacion->id,
            'numero_serie_snapshot' => $tarjeta->numero_serie,
            'codigo_qr_snapshot' => $tarjeta->codigo_qr,
            'marcado_en' => '2026-04-06 08:00:00',
            'registrado_por' => $admin->id,
            'sincronizado_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/asistencias/qr', [
            'codigo_qr' => 'QR-PACK-6001',
            'marcado_en' => '2026-04-06 09:00:00',
            'device_id' => 'mobile-test-2',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ignored');

        $this->assertSame(1, MarcacionPacking::query()->count());
    }

    public function test_it_rejects_marking_when_card_has_no_active_assignment(): void
    {
        TarjetaQr::create([
            'numero_serie' => 'PACK-7001',
            'codigo_qr' => 'QR-PACK-7001',
            'estado' => 'disponible',
        ]);

        $response = $this->postJson('/api/v1/asistencias/qr', [
            'codigo_qr' => 'QR-PACK-7001',
            'marcado_en' => '2026-04-06 08:00:00',
            'device_id' => 'mobile-test-3',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'rejected');

        $this->assertSame(0, MarcacionPacking::query()->count());
    }
}
