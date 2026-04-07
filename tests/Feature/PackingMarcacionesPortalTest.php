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

class PackingMarcacionesPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_contratista_can_view_only_own_packing_markings(): void
    {
        $contratistaA = Contratista::factory()->create();
        $contratistaB = Contratista::factory()->create();

        $userContratista = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratistaA->id,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->createMarcacion('11111111', $contratistaA->id, $admin->id, 'QR-A-001', 'MARK-A-001');
        $this->createMarcacion('22222222', $contratistaB->id, $admin->id, 'QR-B-001', 'MARK-B-001');

        $response = $this->actingAs($userContratista)
            ->get('/packing/marcaciones');

        $response->assertOk()
            ->assertViewHas('page', function (array $page): bool {
                $props = $page['props'] ?? [];
                $marcaciones = $props['marcaciones'] ?? [];

                return ($page['component'] ?? null) === 'admin/packing/marcaciones/index'
                    && count($marcaciones) === 1
                    && ($marcaciones[0]['uuid'] ?? null) === 'MARK-A-001'
                    && ($props['canManageCards'] ?? true) === false;
            });
    }

    public function test_admin_can_view_all_markings_from_portal_route(): void
    {
        $contratistaA = Contratista::factory()->create();
        $contratistaB = Contratista::factory()->create();

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->createMarcacion('33333333', $contratistaA->id, $admin->id, 'QR-A-002', 'MARK-A-002');
        $this->createMarcacion('44444444', $contratistaB->id, $admin->id, 'QR-B-002', 'MARK-B-002');

        $response = $this->actingAs($admin)
            ->get('/packing/marcaciones');

        $response->assertOk()
            ->assertViewHas('page', function (array $page): bool {
                $props = $page['props'] ?? [];
                $marcaciones = $props['marcaciones'] ?? [];

                return ($page['component'] ?? null) === 'admin/packing/marcaciones/index'
                    && count($marcaciones) === 2
                    && ($props['canManageCards'] ?? false) === true;
            });
    }

    private function createMarcacion(
        string $trabajadorId,
        int $contratistaId,
        int $registradoPor,
        string $codigoQr,
        string $uuid,
    ): void {
        $trabajador = Trabajador::create([
            'id' => $trabajadorId,
            'documento' => $trabajadorId.'-1',
            'nombre' => 'Trabajador',
            'apellido' => $trabajadorId,
            'contratista_id' => $contratistaId,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $tarjeta = TarjetaQr::create([
            'numero_serie' => 'SERIE-'.$codigoQr,
            'codigo_qr' => $codigoQr,
            'estado' => 'asignada',
        ]);

        $asignacion = TarjetaQrAsignacion::create([
            'tarjeta_qr_id' => $tarjeta->id,
            'trabajador_id' => $trabajador->id,
            'asignada_por' => $registradoPor,
            'asignada_en' => now()->subHour(),
        ]);

        $ubicacion = Ubicacion::factory()->principal()->create();

        MarcacionPacking::create([
            'uuid' => $uuid,
            'trabajador_id' => $trabajador->id,
            'tarjeta_qr_id' => $tarjeta->id,
            'tarjeta_qr_asignacion_id' => $asignacion->id,
            'numero_serie_snapshot' => $tarjeta->numero_serie,
            'codigo_qr_snapshot' => $tarjeta->codigo_qr,
            'marcado_en' => now(),
            'registrado_por' => $registradoPor,
            'ubicacion_id' => $ubicacion->id,
            'sincronizado_at' => now(),
        ]);
    }
}
