<?php

namespace App\Services;

use App\Models\ControlAccessLog;
use App\Models\MarcacionPacking;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PackingAttendanceReportService
{
    public const STATUS_APP_CONTROL = 'app_control';

    public const STATUS_APP_SIN_CONTROL = 'app_sin_control';

    public const STATUS_CONTROL_SIN_APP = 'control_sin_app';

    /**
     * @return array{
     *     date: Carbon,
     *     turnos: Collection<int, array<string, mixed>>,
     *     rows: Collection<int, array<string, mixed>>,
     *     summary: array<string, int>,
     *     totals_by_turno: Collection<int, array<string, mixed>>,
     *     totals_by_group: Collection<int, array<string, mixed>>
     * }
     */
    public function buildForDate(Carbon|string $date): array
    {
        $timezone = config('app.timezone', 'America/Santiago');
        $reportDate = $date instanceof Carbon
            ? $date->copy()->timezone($timezone)->startOfDay()
            : Carbon::parse($date, $timezone)->startOfDay();

        $turnos = Turno::query()
            ->with(['ubicaciones.padre'])
            ->whereDate('fecha', $reportDate->toDateString())
            ->where('activo', true)
            ->orderBy('hora_inicio')
            ->orderBy('nombre')
            ->get();

        $rows = $turnos
            ->flatMap(fn (Turno $turno): Collection => $this->buildRowsForTurno($turno))
            ->sortBy([
                ['turno_inicio', 'asc'],
                ['status_priority', 'asc'],
                ['group_label', 'asc'],
                ['nombre', 'asc'],
            ])
            ->values();

        return [
            'date' => $reportDate,
            'turnos' => $turnos->map(fn (Turno $turno): array => $this->formatTurno($turno))->values(),
            'rows' => $rows,
            'summary' => $this->buildSummary($rows),
            'totals_by_turno' => $this->buildTotalsByTurno($rows),
            'totals_by_group' => $this->buildTotalsByGroup($rows),
        ];
    }

    public function buildSummary(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            self::STATUS_APP_CONTROL => $rows->where('status', self::STATUS_APP_CONTROL)->count(),
            self::STATUS_APP_SIN_CONTROL => $rows->where('status', self::STATUS_APP_SIN_CONTROL)->count(),
            self::STATUS_CONTROL_SIN_APP => $rows->where('status', self::STATUS_CONTROL_SIN_APP)->count(),
            'marcaciones_multiples' => $rows->where('has_multiple_marks', true)->count(),
        ];
    }

    public function buildTotalsByTurno(Collection $rows): Collection
    {
        return $rows
            ->groupBy('turno_id')
            ->map(fn (Collection $group): array => [
                'turno_id' => $group->first()['turno_id'],
                'turno_nombre' => $group->first()['turno_nombre'],
                'turno_inicio' => $group->first()['turno_inicio'],
                'turno_fin' => $group->first()['turno_fin'],
                ...$this->buildSummary($group),
            ])
            ->sortBy('turno_inicio')
            ->values();
    }

    public function buildTotalsByGroup(Collection $rows): Collection
    {
        return $rows
            ->groupBy('group_label')
            ->map(fn (Collection $group, string $groupLabel): array => [
                'group_label' => $groupLabel,
                ...$this->buildSummary($group),
            ])
            ->sortBy('group_label')
            ->values();
    }

    private function buildRowsForTurno(Turno $turno): Collection
    {
        [$turnoInicio, $turnoFin] = $this->resolveTurnoWindow($turno);

        $appMarksByWorker = $this->queryAppMarks($turno, $turnoInicio, $turnoFin)
            ->groupBy(fn (MarcacionPacking $marcacion): string => (string) $marcacion->trabajador_id);

        $controlLogsByWorker = $this->queryControlLogs($turno, $turnoInicio, $turnoFin)
            ->groupBy(fn (ControlAccessLog $log): string => (string) $log->personal_id);

        $rows = collect();

        foreach ($appMarksByWorker as $trabajadorId => $marcaciones) {
            $controlLogs = $controlLogsByWorker->get((string) $trabajadorId, collect());

            $rows->push($this->buildRow(
                turno: $turno,
                turnoInicio: $turnoInicio,
                turnoFin: $turnoFin,
                workerId: (string) $trabajadorId,
                marcaciones: $marcaciones,
                controlLogs: $controlLogs,
            ));
        }

        foreach ($controlLogsByWorker as $personalId => $controlLogs) {
            if ($appMarksByWorker->has((string) $personalId)) {
                continue;
            }

            $rows->push($this->buildRow(
                turno: $turno,
                turnoInicio: $turnoInicio,
                turnoFin: $turnoFin,
                workerId: (string) $personalId,
                marcaciones: collect(),
                controlLogs: $controlLogs,
            ));
        }

        return $rows;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveTurnoWindow(Turno $turno): array
    {
        $timezone = config('app.timezone', 'America/Santiago');
        $turnoDate = $turno->fecha?->toDateString();
        $horaInicio = $turno->hora_inicio?->format('H:i:s');
        $horaFin = $turno->hora_fin?->format('H:i:s');

        $inicio = Carbon::parse("{$turnoDate} {$horaInicio}", $timezone);
        $fin = Carbon::parse("{$turnoDate} {$horaFin}", $timezone);

        if ($fin->lte($inicio)) {
            $fin->addDay();
        }

        return [$inicio, $fin];
    }

    private function queryAppMarks(Turno $turno, Carbon $turnoInicio, Carbon $turnoFin): Collection
    {
        $ubicacionIds = $turno->ubicaciones->pluck('id')->values();

        return MarcacionPacking::query()
            ->with(['trabajador.contratista', 'ubicacion.padre'])
            ->where('marcado_en', '>=', $turnoInicio)
            ->where('marcado_en', '<', $turnoFin)
            ->when($ubicacionIds->isNotEmpty(), function (Builder $query) use ($ubicacionIds): void {
                $query->whereIn('ubicacion_id', $ubicacionIds);
            })
            ->orderBy('marcado_en')
            ->get();
    }

    private function queryControlLogs(Turno $turno, Carbon $turnoInicio, Carbon $turnoFin): Collection
    {
        $turnoDate = $turno->fecha?->toDateString();

        return ControlAccessLog::query()
            ->with(['trabajador.contratista'])
            ->where(function (Builder $query) use ($turnoDate, $turnoInicio, $turnoFin): void {
                $query->whereDate('fecha', $turnoDate)
                    ->orWhere(function (Builder $innerQuery) use ($turnoInicio, $turnoFin): void {
                        $innerQuery
                            ->where('primera_entrada', '>=', $turnoInicio)
                            ->where('primera_entrada', '<', $turnoFin);
                    })
                    ->orWhere(function (Builder $innerQuery) use ($turnoInicio, $turnoFin): void {
                        $innerQuery
                            ->where('ultima_salida', '>=', $turnoInicio)
                            ->where('ultima_salida', '<', $turnoFin);
                    });
            })
            ->orderBy('primera_entrada')
            ->get()
            ->filter(fn (ControlAccessLog $log): bool => $this->controlLogBelongsToWindow($log, $turnoInicio, $turnoFin))
            ->values();
    }

    private function controlLogBelongsToWindow(ControlAccessLog $log, Carbon $turnoInicio, Carbon $turnoFin): bool
    {
        $dates = collect([$log->primera_entrada, $log->ultima_salida])
            ->filter(fn ($date): bool => $date instanceof Carbon);

        if ($dates->isEmpty()) {
            return $log->fecha?->isSameDay($turnoInicio) ?? false;
        }

        return $dates->contains(
            fn (Carbon $date): bool => $date->gte($turnoInicio) && $date->lt($turnoFin),
        ) || ($log->fecha?->isSameDay($turnoInicio) ?? false);
    }

    private function buildRow(
        Turno $turno,
        Carbon $turnoInicio,
        Carbon $turnoFin,
        string $workerId,
        Collection $marcaciones,
        Collection $controlLogs,
    ): array {
        $firstMarcacion = $marcaciones->first();
        $firstControlLog = $controlLogs->first();
        $trabajador = $firstMarcacion?->trabajador ?? $firstControlLog?->trabajador;
        $hasAppMarks = $marcaciones->isNotEmpty();
        $hasControl = $controlLogs->isNotEmpty();
        $status = $this->resolveStatus($hasAppMarks, $hasControl);
        $controlSummary = $this->summarizeControlLogs($controlLogs);
        $groupLabel = $trabajador?->contratista?->razon_social
            ?? $controlSummary['departamento']
            ?? 'Sin contratista/departamento';

        return [
            'turno_id' => $turno->id,
            'turno_nombre' => $turno->nombre,
            'turno_inicio' => $turnoInicio,
            'turno_fin' => $turnoFin,
            'worker_id' => $workerId,
            'documento' => $trabajador?->documento ?? $workerId,
            'nombre' => $trabajador?->nombre_completo ?? $controlSummary['nombre'] ?? 'Sin nombre',
            'contratista' => $trabajador?->contratista?->razon_social,
            'departamento_control' => $controlSummary['departamento'],
            'group_label' => $groupLabel,
            'primera_entrada' => $controlSummary['primera_entrada'],
            'ultima_salida' => $controlSummary['ultima_salida'],
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'status_priority' => $this->statusPriority($status),
            'has_multiple_marks' => $marcaciones->count() > 1,
            'marcaciones_count' => $marcaciones->count(),
            'marcaciones' => $this->formatMarcaciones($marcaciones),
        ];
    }

    private function resolveStatus(bool $hasAppMarks, bool $hasControl): string
    {
        if ($hasAppMarks && $hasControl) {
            return self::STATUS_APP_CONTROL;
        }

        if ($hasAppMarks) {
            return self::STATUS_APP_SIN_CONTROL;
        }

        return self::STATUS_CONTROL_SIN_APP;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_APP_CONTROL => 'Marcó app y aparece en control',
            self::STATUS_APP_SIN_CONTROL => 'Marcó app sin control de acceso',
            self::STATUS_CONTROL_SIN_APP => 'Control de acceso sin marcación app',
            default => 'Sin clasificar',
        };
    }

    private function statusPriority(string $status): int
    {
        return match ($status) {
            self::STATUS_APP_SIN_CONTROL => 10,
            self::STATUS_CONTROL_SIN_APP => 20,
            self::STATUS_APP_CONTROL => 30,
            default => 99,
        };
    }

    /**
     * @return array{nombre: ?string, departamento: ?string, primera_entrada: ?Carbon, ultima_salida: ?Carbon}
     */
    private function summarizeControlLogs(Collection $controlLogs): array
    {
        return [
            'nombre' => $controlLogs->pluck('nombre')->filter()->first(),
            'departamento' => $controlLogs->pluck('departamento')->filter()->unique()->implode(', ') ?: null,
            'primera_entrada' => $this->minDate($controlLogs->pluck('primera_entrada')),
            'ultima_salida' => $this->maxDate($controlLogs->pluck('ultima_salida')),
        ];
    }

    private function minDate(Collection $dates): ?Carbon
    {
        $min = null;

        foreach ($dates as $date) {
            if ($date instanceof Carbon && (! ($min instanceof Carbon) || $date->lt($min))) {
                $min = $date;
            }
        }

        return $min;
    }

    private function maxDate(Collection $dates): ?Carbon
    {
        $max = null;

        foreach ($dates as $date) {
            if ($date instanceof Carbon && (! ($max instanceof Carbon) || $date->gt($max))) {
                $max = $date;
            }
        }

        return $max;
    }

    private function formatMarcaciones(Collection $marcaciones): Collection
    {
        return $marcaciones
            ->sortBy('marcado_en')
            ->map(fn (MarcacionPacking $marcacion): array => [
                'id' => $marcacion->id,
                'marcado_en' => $marcacion->marcado_en,
                'ubicacion' => $marcacion->ubicacion?->nombre_completo ?? $marcacion->ubicacion_texto,
                'numero_serie' => $marcacion->numero_serie_snapshot,
                'codigo_qr' => $marcacion->codigo_qr_snapshot,
                'device_id' => $marcacion->device_id,
                'sync_batch_id' => $marcacion->sync_batch_id,
            ])
            ->values();
    }

    private function formatTurno(Turno $turno): array
    {
        [$inicio, $fin] = $this->resolveTurnoWindow($turno);

        return [
            'id' => $turno->id,
            'nombre' => $turno->nombre,
            'inicio' => $inicio,
            'fin' => $fin,
            'ubicaciones' => $turno->ubicaciones
                ->map(fn ($ubicacion): string => $ubicacion->nombre_completo)
                ->values(),
        ];
    }
}
