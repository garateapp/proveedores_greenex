<?php

namespace App\Services;

use App\Models\CentroCosto;
use App\Models\Contratista;
use App\Models\ValeAlmuerzo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cuenta los vales de almuerzo (tickets) emitidos en una ventana de tiempo.
 *
 * El reporte abre el total por contratista. Para el contratista configurado en
 * `garatepass.reporte_rut_contratista` además abre el detalle por centro de
 * costo: es el único que opera con esa granularidad.
 */
class TicketEmitidoReportService
{
    /**
     * @return array{
     *     desde: Carbon,
     *     hasta: Carbon,
     *     total: int,
     *     totals_by_contratista: array<int, array{contratista: string, total: int}>,
     *     rut_detalle: array{rut: string, contratista: string}|null,
     *     totals_by_centro_costo: array<int, array{centro_costo_id: int|null, codigo: string|null, nombre: string|null, total: int}>
     * }
     */
    public function buildForRange(Carbon $desde, Carbon $hasta): array
    {
        $totalsByContratista = $this->totalsByContratista($desde, $hasta);
        $contratistaDetalle = $this->contratistaDetalle();

        $rutDetalle = null;
        $totalsByCentroCosto = [];

        if ($contratistaDetalle instanceof Contratista) {
            $rutDetalle = [
                'rut' => $contratistaDetalle->rut,
                'contratista' => $contratistaDetalle->razon_social,
            ];
            $totalsByCentroCosto = $this->totalsByCentroCosto(
                $desde,
                $hasta,
                $contratistaDetalle->razon_social,
            );
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'total' => (int) collect($totalsByContratista)->sum('total'),
            'totals_by_contratista' => $totalsByContratista,
            'rut_detalle' => $rutDetalle,
            'totals_by_centro_costo' => $totalsByCentroCosto,
        ];
    }

    /**
     * Reporte de un día completo, de 00:00:00 a 23:59:59.
     *
     * @return array{
     *     desde: Carbon,
     *     hasta: Carbon,
     *     total: int,
     *     totals_by_contratista: array<int, array{contratista: string, total: int}>,
     *     rut_detalle: array{rut: string, contratista: string}|null,
     *     totals_by_centro_costo: array<int, array{centro_costo_id: int|null, codigo: string|null, nombre: string|null, total: int}>
     * }
     */
    public function buildForDate(Carbon $date): array
    {
        return $this->buildForRange($date->copy()->startOfDay(), $date->copy()->endOfDay());
    }

    /**
     * @return array<int, array{contratista: string, total: int}>
     */
    public function totalsByContratista(Carbon $desde, Carbon $hasta): array
    {
        return ValeAlmuerzo::query()
            ->whereBetween('emitido_en', [$desde, $hasta])
            ->selectRaw('contratista, COUNT(*) as total')
            ->groupBy('contratista')
            ->orderByDesc('total')
            ->get()
            ->map(fn (ValeAlmuerzo $row): array => [
                'contratista' => filled($row->contratista) ? $row->contratista : '(sin contratista)',
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return array<int, array{centro_costo_id: int|null, codigo: string|null, nombre: string|null, total: int}>
     */
    public function totalsByCentroCosto(Carbon $desde, Carbon $hasta, string $razonSocial): array
    {
        $rows = ValeAlmuerzo::query()
            ->whereBetween('emitido_en', [$desde, $hasta])
            ->where('contratista', $razonSocial)
            ->selectRaw('centro_costo_id, COUNT(*) as total')
            ->groupBy('centro_costo_id')
            ->get();

        /** @var Collection<int, CentroCosto> $centros */
        $centros = CentroCosto::withTrashed()
            ->whereIn('id', $rows->pluck('centro_costo_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function (ValeAlmuerzo $row) use ($centros): array {
                $centro = $row->centro_costo_id ? $centros->get($row->centro_costo_id) : null;

                return [
                    'centro_costo_id' => $row->centro_costo_id,
                    'codigo' => $centro?->codigo,
                    'nombre' => $centro?->nombre,
                    'total' => (int) $row->total,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    public function contratistaDetalle(): ?Contratista
    {
        $rut = preg_replace('/[^0-9kK]/', '', (string) config('garatepass.reporte_rut_contratista'));

        if ($rut === '') {
            return null;
        }

        return Contratista::query()
            ->whereRaw("REPLACE(REPLACE(UPPER(rut), '.', ''), '-', '') = ?", [strtoupper($rut)])
            ->first();
    }
}
