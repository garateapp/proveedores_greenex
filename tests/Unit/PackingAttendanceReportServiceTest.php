<?php

namespace Tests\Unit;

use App\Mail\PackingAttendanceReportMail;
use App\Models\Contratista;
use App\Models\ControlAccessLog;
use App\Models\MarcacionPacking;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
use App\Models\Trabajador;
use App\Models\Turno;
use App\Models\Ubicacion;
use App\Models\User;
use App\Services\PackingAttendanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PackingAttendanceReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_for_date_classifies_app_and_control_access_states(): void
    {
        $ubicacion = Ubicacion::factory()->principal()->create([
            'nombre' => 'Packing Central',
        ]);
        $turno = $this->createTurno('2026-05-26', 'Turno Dia', '08:00', '18:00', [$ubicacion->id]);

        $contratista = Contratista::factory()->create([
            'razon_social' => 'Contratista Uno',
        ]);

        $appControl = $this->createTrabajador('11111111', $contratista, 'Ana', 'Control');
        $appSinControl = $this->createTrabajador('22222222', $contratista, 'Beto', 'Solo App');
        $controlSinApp = $this->createTrabajador('33333333', $contratista, 'Carla', 'Solo Control');

        $this->createMarcacion($appControl, $ubicacion, '2026-05-26 08:15:00');
        $this->createControlAccessLog($appControl, '2026-05-26 08:00:00', '2026-05-26 17:30:00');

        $this->createMarcacion($appSinControl, $ubicacion, '2026-05-26 09:00:00');

        $this->createControlAccessLog($controlSinApp, '2026-05-26 08:20:00', '2026-05-26 17:40:00');

        $report = app(PackingAttendanceReportService::class)->buildForDate('2026-05-26');
        $rows = $report['rows'];

        $this->assertSame(3, $report['summary']['total']);
        $this->assertSame(1, $report['summary'][PackingAttendanceReportService::STATUS_APP_CONTROL]);
        $this->assertSame(1, $report['summary'][PackingAttendanceReportService::STATUS_APP_SIN_CONTROL]);
        $this->assertSame(1, $report['summary'][PackingAttendanceReportService::STATUS_CONTROL_SIN_APP]);

        $this->assertSame(PackingAttendanceReportService::STATUS_APP_CONTROL, $rows->firstWhere('worker_id', '11111111')['status']);
        $this->assertSame(PackingAttendanceReportService::STATUS_APP_SIN_CONTROL, $rows->firstWhere('worker_id', '22222222')['status']);
        $this->assertSame(PackingAttendanceReportService::STATUS_CONTROL_SIN_APP, $rows->firstWhere('worker_id', '33333333')['status']);
        $this->assertSame($turno->id, $rows->firstWhere('worker_id', '11111111')['turno_id']);
        $this->assertStringContainsString(
            'Resumen ejecutivo',
            (new PackingAttendanceReportMail($report, 'Reporte Test'))->render(),
        );
    }

    public function test_it_reports_multiple_app_marks_inside_the_turno_window(): void
    {
        $ubicacion = Ubicacion::factory()->principal()->create();
        $this->createTurno('2026-05-26', 'Turno Dia', '08:00', '18:00', [$ubicacion->id]);
        $contratista = Contratista::factory()->create();
        $trabajador = $this->createTrabajador('44444444', $contratista, 'Diego', 'Duplicado');

        $this->createMarcacion($trabajador, $ubicacion, '2026-05-26 08:30:00');
        $this->createMarcacion($trabajador, $ubicacion, '2026-05-26 12:45:00');
        $this->createMarcacion($trabajador, $ubicacion, '2026-05-26 18:00:00');

        $report = app(PackingAttendanceReportService::class)->buildForDate('2026-05-26');
        $row = $report['rows']->firstWhere('worker_id', '44444444');

        $this->assertNotNull($row);
        $this->assertTrue($row['has_multiple_marks']);
        $this->assertSame(2, $row['marcaciones_count']);
        $this->assertSame(1, $report['summary']['marcaciones_multiples']);
    }

    public function test_it_handles_turnos_that_cross_midnight(): void
    {
        $ubicacion = Ubicacion::factory()->principal()->create();
        $this->createTurno('2026-05-26', 'Turno Noche', '18:30', '08:00', [$ubicacion->id]);
        $contratista = Contratista::factory()->create();
        $trabajador = $this->createTrabajador('55555555', $contratista, 'Elena', 'Noche');

        $this->createMarcacion($trabajador, $ubicacion, '2026-05-27 02:15:00');
        $this->createControlAccessLog($trabajador, '2026-05-26 18:45:00', '2026-05-27 07:40:00');

        $report = app(PackingAttendanceReportService::class)->buildForDate('2026-05-26');
        $row = $report['rows']->firstWhere('worker_id', '55555555');

        $this->assertNotNull($row);
        $this->assertSame(PackingAttendanceReportService::STATUS_APP_CONTROL, $row['status']);
        $this->assertSame('2026-05-27 08:00:00', $row['turno_fin']->format('Y-m-d H:i:s'));
    }

    /**
     * @param  list<int>  $ubicacionIds
     */
    private function createTurno(string $fecha, string $nombre, string $horaInicio, string $horaFin, array $ubicacionIds): Turno
    {
        $turno = Turno::query()->create([
            'fecha' => $fecha,
            'nombre' => $nombre,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'activo' => true,
        ]);

        $turno->ubicaciones()->sync($ubicacionIds);

        return $turno;
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
            'device_id' => 'device-test',
            'sync_batch_id' => 'batch-test',
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
