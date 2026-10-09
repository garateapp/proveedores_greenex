<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketEmitidoReporteIndexRequest;
use App\Services\TicketEmitidoReportService;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reporte visual de los tickets de almuerzo emitidos.
 *
 * Abre el total por contratista y, para el contratista con detalle configurado,
 * permite además filtrar y desglosar por centro de costo.
 */
class TicketEmitidoReporteController extends Controller
{
    public function index(TicketEmitidoReporteIndexRequest $request, TicketEmitidoReportService $service): Response
    {
        $timezone = config('app.timezone', 'America/Santiago');

        $desde = $request->desde()
            ? Carbon::parse($request->desde(), $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfDay();

        $hasta = $request->hasta()
            ? Carbon::parse($request->hasta(), $timezone)->endOfDay()
            : $desde->copy()->endOfDay();

        $contratistaFilter = $request->contratista();
        $centroCostoFilter = $request->centroCostoId();

        $detalle = $service->contratistaDetalle();
        $detalleRazonSocial = $detalle?->razon_social;

        $totals = $service->totalsByContratista($desde, $hasta);

        $contratistas = collect($totals)->pluck('contratista')->all();

        if ($detalleRazonSocial !== null && ! in_array($detalleRazonSocial, $contratistas, true)) {
            $contratistas[] = $detalleRazonSocial;
        }

        sort($contratistas);

        if ($contratistaFilter !== null) {
            $totals = array_values(array_filter(
                $totals,
                fn (array $row): bool => $row['contratista'] === $contratistaFilter,
            ));
        }

        $mostrarDetalleCentroCosto = $contratistaFilter !== null
            && $detalleRazonSocial !== null
            && $contratistaFilter === $detalleRazonSocial;

        $detalleCentroCosto = null;
        $centrosCosto = [];

        if ($mostrarDetalleCentroCosto) {
            $rows = $service->totalsByCentroCosto($desde, $hasta, $detalleRazonSocial);

            $centrosCosto = collect($rows)
                ->filter(fn (array $row): bool => $row['centro_costo_id'] !== null)
                ->map(fn (array $row): array => [
                    'value' => (string) $row['centro_costo_id'],
                    'label' => $row['codigo'] ?? (string) $row['centro_costo_id'],
                ])
                ->values()
                ->all();

            if ($centroCostoFilter !== null) {
                $rows = array_values(array_filter(
                    $rows,
                    fn (array $row): bool => $row['centro_costo_id'] === $centroCostoFilter,
                ));
            }

            $detalleCentroCosto = [
                'total' => (int) collect($rows)->sum('total'),
                'rows' => $rows,
            ];
        }

        return Inertia::render('admin/garatepass/tickets/index', [
            'filters' => [
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
                'contratista' => $contratistaFilter,
                'centro_costo_id' => $centroCostoFilter,
            ],
            'total' => (int) collect($totals)->sum('total'),
            'totalsByContratista' => $totals,
            'contratistas' => $contratistas,
            'detalleRut' => $detalle ? [
                'rut' => $detalle->rut,
                'contratista' => $detalle->razon_social,
            ] : null,
            'detalleCentroCosto' => $detalleCentroCosto,
            'centrosCosto' => $centrosCosto,
        ]);
    }
}
