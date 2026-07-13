<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PackingAttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackingAttendanceReportRequest;
use App\Services\PackingAttendanceReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PackingAttendanceReportController extends Controller
{
    public function index(
        PackingAttendanceReportRequest $request,
        PackingAttendanceReportService $service,
    ): Response {
        $validated = $request->validated();
        $report = $service->buildForDate($request->reportDate());
        $rows = $this->filterRows(
            rows: $report['rows'],
            turnoId: isset($validated['turno_id']) ? (int) $validated['turno_id'] : null,
            status: $validated['status'] ?? null,
        )->filter(fn (array $row): bool => $row['contratista'] !== null)
            ->values();

        $summary = $service->buildSummary($rows);

        return Inertia::render('admin/packing/asistencia-reporte/index', [
            'filters' => [
                'date' => $report['date']->toDateString(),
                'turno_id' => isset($validated['turno_id']) ? (string) $validated['turno_id'] : '',
                'status' => $validated['status'] ?? '',
            ],
            'turnos' => $report['turnos']->map(fn (array $turno): array => [
                'id' => $turno['id'],
                'nombre' => $turno['nombre'],
                'inicio' => $this->formatDateTime($turno['inicio']),
                'fin' => $this->formatDateTime($turno['fin']),
                'ubicaciones' => $turno['ubicaciones']->all(),
            ])->values(),
            'summary' => $summary,
            'totalsByTurno' => $service->buildTotalsByTurno($rows)
                ->map(fn (array $item): array => $this->formatTotalsByTurno($item))
                ->values(),
            'totalsByGroup' => $service->buildTotalsByGroup($rows)->values(),
            'rows' => $rows->map(fn (array $row): array => $this->formatRow($row))->values(),
            'statusOptions' => [
                [
                    'value' => PackingAttendanceReportService::STATUS_APP_CONTROL,
                    'label' => 'App + control',
                ],
                [
                    'value' => PackingAttendanceReportService::STATUS_APP_SIN_CONTROL,
                    'label' => 'App sin control',
                ],
                [
                    'value' => PackingAttendanceReportService::STATUS_CONTROL_SIN_APP,
                    'label' => 'Control sin app',
                ],
                [
                    'value' => 'multiple',
                    'label' => 'Marcaciones multiples',
                ],
            ],
        ]);
    }

    public function export(
        PackingAttendanceReportRequest $request,
        PackingAttendanceReportService $service,
    ): StreamedResponse {
        $validated = $request->validated();
        $report = $service->buildForDate($request->reportDate());
        $rows = $this->filterRows(
            rows: $report['rows'],
            turnoId: isset($validated['turno_id']) ? (int) $validated['turno_id'] : null,
            status: $validated['status'] ?? null,
        )->filter(fn (array $row): bool => $row['contratista'] !== null)
            ->values();

        $report['rows'] = $rows;
        $report['summary'] = $service->buildSummary($rows);
        $report['totals_by_turno'] = $service->buildTotalsByTurno($rows);
        $report['totals_by_group'] = $service->buildTotalsByGroup($rows);

        $export = new PackingAttendanceReportExport(
            report: $report,
            date: $report['date']->toDateString(),
        );

        $writer = $export->build();
        $filename = "reporte-asistencia-packing-{$report['date']->toDateString()}.xlsx";

        $callback = function () use ($writer): void {
            $writer->save('php://output');
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function filterRows(Collection $rows, ?int $turnoId, ?string $status): Collection
    {
        return $rows
            ->when($turnoId !== null, fn (Collection $collection): Collection => $collection
                ->filter(fn (array $row): bool => (int) $row['turno_id'] === $turnoId))
            ->when($status !== null && $status !== '', function (Collection $collection) use ($status): Collection {
                if ($status === 'multiple') {
                    return $collection->filter(fn (array $row): bool => (bool) $row['has_multiple_marks']);
                }

                return $collection->filter(fn (array $row): bool => $row['status'] === $status);
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatRow(array $row): array
    {
        return [
            'turno_id' => $row['turno_id'],
            'turno_nombre' => $row['turno_nombre'],
            'turno_inicio' => $this->formatDateTime($row['turno_inicio']),
            'turno_fin' => $this->formatDateTime($row['turno_fin']),
            'worker_id' => $row['worker_id'],
            'documento' => $row['documento'],
            'nombre' => $row['nombre'],
            'contratista' => $row['contratista'],
            'departamento_control' => $row['departamento_control'],
            'group_label' => $row['group_label'],
            'primera_entrada' => $this->formatDateTime($row['primera_entrada']),
            'ultima_salida' => $this->formatDateTime($row['ultima_salida']),
            'status' => $row['status'],
            'status_label' => $row['status_label'],
            'has_multiple_marks' => $row['has_multiple_marks'],
            'marcaciones_count' => $row['marcaciones_count'],
            'ubicaciones' => $row['ubicaciones'],
            'marcaciones' => $row['marcaciones']->map(fn (array $marcacion): array => [
                'id' => $marcacion['id'],
                'marcado_en' => $this->formatDateTime($marcacion['marcado_en']),
                'ubicacion' => $marcacion['ubicacion'],
                'numero_serie' => $marcacion['numero_serie'],
                'codigo_qr' => $marcacion['codigo_qr'],
                'device_id' => $marcacion['device_id'],
                'sync_batch_id' => $marcacion['sync_batch_id'],
            ])->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function formatTotalsByTurno(array $item): array
    {
        return [
            ...$item,
            'turno_inicio' => $this->formatDateTime($item['turno_inicio']),
            'turno_fin' => $this->formatDateTime($item['turno_fin']),
        ];
    }

    private function formatDateTime(mixed $date): ?string
    {
        if (! ($date instanceof Carbon)) {
            return null;
        }

        return $date->format('Y-m-d H:i:s');
    }
}
