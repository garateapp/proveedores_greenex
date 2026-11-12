<?php

namespace App\Services;

use App\Models\ControlAccessLog;
use App\Models\MarcacionPacking;
use App\Models\Turno;
use App\Models\ValeAlmuerzo;
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

        $turnoUbicacionIds = $turnos
            ->flatMap->ubicaciones
            ->pluck('id')
            ->unique()
            ->values();

        $startOfDay = $reportDate->copy();
        $endOfDay = $reportDate->copy()->endOfDay();

        $appMarks = MarcacionPacking::query()
            ->with(['trabajador.contratista', 'ubicacion.padre'])
            ->whereIn('ubicacion_id', $turnoUbicacionIds)
            ->whereBetween('marcado_en', [$startOfDay, $endOfDay])
            ->orderBy('marcado_en')
            ->get();

        $controlLogs = ControlAccessLog::query()
            ->with(['trabajador.contratista'])
            ->whereBetween('fecha', [$startOfDay, $endOfDay])
            ->orderBy('primera_entrada')
            ->get();

        $valesAlmuerzo = ValeAlmuerzo::query()
            ->whereDate('emitido_en', $reportDate->toDateString())
            ->pluck('trabajador_id')
            ->unique()
            ->values();

        $rows = $this->buildRows($turnos, $appMarks, $controlLogs, $valesAlmuerzo);

        return [
            'date' => $reportDate,
            'turnos' => $turnos->map(fn (Turno $turno): array => $this->formatTurno($turno))->values(),
            'rows' => $rows,
            'summary' => $this->buildSummary($rows),
            'totals_by_turno' => $this->buildTotalsByTurno($rows),
            'totals_by_group' => $this->buildTotalsByGroup($rows),
            'totals_by_ubicacion_padre' => $this->buildTotalsByUbicacionPadre($rows),
            'totals_by_contratista_padre' => $this->buildTotalsByContratistaPadre($rows),
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

    public function buildTotalsByUbicacionPadre(Collection $rows): Collection
    {
        return $rows
            ->groupBy('ubicacion_padre')
            ->map(fn (Collection $group, string $padre): array => [
                'ubicacion_padre' => $padre ?? 'Sin padre',
                'total' => $group->count(),
                self::STATUS_APP_CONTROL => $group->where('status', self::STATUS_APP_CONTROL)->count(),
                self::STATUS_APP_SIN_CONTROL => $group->where('status', self::STATUS_APP_SIN_CONTROL)->count(),
                self::STATUS_CONTROL_SIN_APP => $group->where('status', self::STATUS_CONTROL_SIN_APP)->count(),
                'marcaciones_multiples' => $group->where('has_multiple_marks', true)->count(),
                'contratistas' => $group->groupBy('contratista')->map(fn (Collection $sub, string $contratista): array => [
                    'contratista' => $contratista ?? 'Sin contratista',
                    'total' => $sub->count(),
                ])->sortByDesc('total')->values()->all(),
            ])
            ->sortByDesc('total')
            ->values();
    }

    public function buildTotalsByContratistaPadre(Collection $rows): Collection
    {
        $grouped = $rows->groupBy(fn (array $row): string => ($row['ubicacion_padre'] ?? 'Sin padre').'|'.($row['contratista'] ?? 'Sin contratista'));

        $result = collect();

        foreach ($grouped as $key => $group) {
            [$padre, $contratista] = explode('|', $key, 2);

            $result->push([
                'ubicacion_padre' => $padre,
                'contratista' => $contratista,
                'total' => $group->count(),
                self::STATUS_APP_CONTROL => $group->where('status', self::STATUS_APP_CONTROL)->count(),
                self::STATUS_APP_SIN_CONTROL => $group->where('status', self::STATUS_APP_SIN_CONTROL)->count(),
                self::STATUS_CONTROL_SIN_APP => $group->where('status', self::STATUS_CONTROL_SIN_APP)->count(),
            ]);
        }

        return $result->sortByDesc('total')->values();
    }

    public function buildRows(Collection $turnos, Collection $appMarks, Collection $controlLogs, Collection $valeTrabajadorIds): Collection
    {
        $appMarksByWorker = $appMarks->groupBy(fn (MarcacionPacking $marcacion): string => (string) $marcacion->trabajador_id);

        $controlLogsByWorker = $controlLogs->groupBy(fn (ControlAccessLog $log): string => (string) $log->personal_id);

        $workerIds = $appMarksByWorker->keys()->merge($controlLogsByWorker->keys())->unique()->values();

        $rows = collect();

        foreach ($workerIds as $workerId) {
            $marcaciones = $appMarksByWorker->get($workerId, collect());
            $logs = $controlLogsByWorker->get($workerId, collect());

            $turno = $this->findTurnoByTime($turnos, $this->referenceTime($marcaciones, $logs));

            if (! $turno) {
                continue;
            }

            [$turnoInicio, $turnoFin] = $this->resolveTurnoWindow($turno);

            $marcacionesEnTurno = $marcaciones->filter(fn (MarcacionPacking $m): bool => $m->marcado_en instanceof Carbon && $m->marcado_en->gte($turnoInicio) && $m->marcado_en->lt($turnoFin))->values();

            $logsEnTurno = $logs->filter(fn (ControlAccessLog $log): bool => $this->controlLogBelongsToWindow($log, $turnoInicio, $turnoFin))->values();

            $trabajador = $marcacionesEnTurno->first()?->trabajador ?? $logsEnTurno->first()?->trabajador;

            $rows->push($this->buildRow(
                turno: $turno,
                workerId: $workerId,
                marcaciones: $marcacionesEnTurno,
                controlLogs: $logsEnTurno,
                valeAlmuerzo: $valeTrabajadorIds->contains($workerId),
            ));
        }

        return $rows
            ->sortBy([
                ['turno_inicio', 'asc'],
                ['status_priority', 'asc'],
                ['group_label', 'asc'],
                ['nombre', 'asc'],
            ])
            ->values();
    }

    private function findTurnoByTime(Collection $turnos, Carbon $time): ?Turno
    {
        return $turnos->first(function (Turno $turno) use ($time): bool {
            [$inicio, $fin] = $this->resolveTurnoWindow($turno);

            return $time->gte($inicio) && $time->lt($fin);
        });
    }

    private function referenceTime(Collection $marcaciones, Collection $controlLogs): Carbon
    {
        $firstMark = $marcaciones->first();

        if ($firstMark && $firstMark->marcado_en instanceof Carbon) {
            return $firstMark->marcado_en;
        }

        $firstLog = $controlLogs->first();

        if ($firstLog && $firstLog->primera_entrada instanceof Carbon) {
            return $firstLog->primera_entrada;
        }

        if ($firstLog && $firstLog->fecha instanceof Carbon) {
            return $firstLog->fecha;
        }

        return now();
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

        $inicio = Carbon::parse("{$turnoDate} {$horaInicio}", $timezone)->subHour();
        $fin = Carbon::parse("{$turnoDate} {$horaFin}", $timezone)->addHour();

        if ($fin->lte($inicio)) {
            $fin->addDay();
        }

        return [$inicio, $fin];
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
        string $workerId,
        Collection $marcaciones,
        Collection $controlLogs,
        bool $valeAlmuerzo,
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
            'turno_inicio' => $this->formatDateTime($turno->hora_inicio?->format('Y-m-d H:i:s')),
            'turno_fin' => $this->formatDateTime($turno->hora_fin?->format('Y-m-d H:i:s')),
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
            'ubicaciones' => $marcaciones
                ->map(fn (MarcacionPacking $m): ?string => $m->ubicacion?->nombre_completo ?? $m->ubicacion_texto)
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'ubicacion_padre' => $firstMarcacion?->ubicacion?->padre?->nombre ?? null,
            'vale_almuerzo' => $valeAlmuerzo,
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
