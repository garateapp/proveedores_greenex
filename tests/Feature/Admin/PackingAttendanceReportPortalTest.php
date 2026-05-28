<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\ControlAccessLog;
use App\Models\MarcacionPacking;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
use App\Models\Trabajador;
use App\Models\Turno;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PackingAttendanceReportPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_packing_attendance_report(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $ubicacion = Ubicacion::factory()->principal()->create([
            'nombre' => 'Packing Central',
        ]);
        $turno = Turno::query()->create([
            'fecha' => '2026-05-26',
            'nombre' => 'Turno Dia',
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'activo' => true,
        ]);
        $turno->ubicaciones()->sync([$ubicacion->id]);
        $contratista = Contratista::factory()->create([
            'razon_social' => 'Contratista Portal',
        ]);

        $appControl = $this->createTrabajador('11111111', $contratista, 'Ana', 'Control');
        $appSinControl = $this->createTrabajador('22222222', $contratista, 'Beto', 'App');
        $controlSinApp = $this->createTrabajador('33333333', $contratista, 'Carla', 'Control');

        $this->createMarcacion($appControl, $ubicacion, '2026-05-26 08:15:00');
        $this->createControlAccessLog($appControl, '2026-05-26 08:00:00', '2026-05-26 17:30:00');
        $this->createMarcacion($appSinControl, $ubicacion, '2026-05-26 08:30:00');
        $this->createMarcacion($appSinControl, $ubicacion, '2026-05-26 12:15:00');
        $this->createControlAccessLog($controlSinApp, '2026-05-26 08:10:00', '2026-05-26 17:10:00');

        $this->actingAs($admin)
            ->get(route('admin.packing.asistencia-reporte.index', ['date' => '2026-05-26']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/packing/asistencia-reporte/index')
                ->where('filters.date', '2026-05-26')
                ->where('summary.total', 3)
                ->where('summary.app_control', 1)
                ->where('summary.app_sin_control', 1)
                ->where('summary.control_sin_app', 1)
                ->where('summary.marcaciones_multiples', 1)
                ->has('turnos', 1)
                ->has('rows', 3)
            );
    }

    public function test_admin_can_filter_report_by_multiple_marks(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $ubicacion = Ubicacion::factory()->principal()->create();
        $turno = Turno::query()->create([
            'fecha' => '2026-05-26',
            'nombre' => 'Turno Dia',
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'activo' => true,
        ]);
        $turno->ubicaciones()->sync([$ubicacion->id]);
        $contratista = Contratista::factory()->create();
        $trabajador = $this->createTrabajador('44444444', $contratista, 'Diego', 'Duplicado');
        $normal = $this->createTrabajador('55555555', $contratista, 'Elena', 'Normal');

        $this->createMarcacion($trabajador, $ubicacion, '2026-05-26 08:30:00');
        $this->createMarcacion($trabajador, $ubicacion, '2026-05-26 12:15:00');
        $this->createMarcacion($normal, $ubicacion, '2026-05-26 09:00:00');

        $this->actingAs($admin)
            ->get(route('admin.packing.asistencia-reporte.index', [
                'date' => '2026-05-26',
                'status' => 'multiple',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'multiple')
                ->where('summary.total', 1)
                ->where('summary.marcaciones_multiples', 1)
                ->where('rows.0.worker_id', '44444444')
            );
    }

    public function test_non_admin_cannot_view_packing_attendance_report(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
        ]);

        $this->actingAs($user)
            ->get(route('admin.packing.asistencia-reporte.index'))
            ->assertForbidden();
    }

    private function createTrabajador(
        string $id,
        Contratista $contratista,
        string $nombre,
        string $apellido,
    ): Trabajador {
        return Trabajador::query()->create([
            'id' => $id,
            'documento' => "{$id}-1",
            'nombre' => $nombre,
            'apellido' => $apellido,
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => '2026-01-01',
        ]);
    }

    private function createMarcacion(Trabajador $trabajador, Ubicacion $ubicacion, string $marcadoEn): MarcacionPacking
    {
        $tarjeta = TarjetaQr::factory()->create([
            'estado' => 'asignada',
        ]);
        $asignacion = TarjetaQrAsignacion::query()->create([
            'tarjeta_qr_id' => $tarjeta->id,
            'trabajador_id' => $trabajador->id,
            'asignada_por' => User::factory()->create()->id,
            'asignada_en' => Carbon::parse($marcadoEn)->subDay(),
        ]);

        return MarcacionPacking::query()->create([
            'uuid' => fake()->uuid(),
            'trabajador_id' => $trabajador->id,
            'tarjeta_qr_id' => $tarjeta->id,
            'tarjeta_qr_asignacion_id' => $asignacion->id,
            'numero_serie_snapshot' => $tarjeta->numero_serie,
            'codigo_qr_snapshot' => $tarjeta->codigo_qr,
            'marcado_en' => $marcadoEn,
            'registrado_por' => $asignacion->asignada_por,
            'device_id' => 'device-portal',
            'sync_batch_id' => 'batch-portal',
            'ubicacion_id' => $ubicacion->id,
            'sincronizado_at' => $marcadoEn,
        ]);
    }

    private function createControlAccessLog(Trabajador $trabajador, string $primeraEntrada, string $ultimaSalida): ControlAccessLog
    {
        return ControlAccessLog::query()->create([
            'fecha' => Carbon::parse($primeraEntrada)->startOfDay(),
            'personal_id' => $trabajador->id,
            'nombre' => $trabajador->nombre_completo,
            'departamento' => $trabajador->contratista?->razon_social,
            'primera_entrada' => $primeraEntrada,
            'ultima_salida' => $ultimaSalida,
            'pin' => 'PIN-'.$trabajador->id,
        ]);
    }
}
