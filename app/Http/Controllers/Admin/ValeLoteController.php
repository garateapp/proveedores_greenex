<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValeLoteIndexRequest;
use App\Models\ValeLote;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Consulta de los lotes emitidos desde la app.
 *
 * Esta pantalla no emite ni canjea nada: los vales de lote nacen en el casino,
 * escaneando el QR del administrador, y se canjean en el punto de servicio con
 * la app. El Portal solo los muestra, para soporte y conciliación.
 */
class ValeLoteController extends Controller
{
    public function index(ValeLoteIndexRequest $request): Response
    {
        $search = $request->search();
        $estado = $request->estado();
        $desde = $request->desde();
        $hasta = $request->hasta();

        $vales = ValeLote::query()
            ->with(['administrador', 'centroCosto'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('codigo', 'like', "%{$search}%")
                        ->orWhere('lote_id', 'like', "%{$search}%")
                        ->orWhereHas('administrador', fn ($admin) => $admin->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($estado === 'vigente', fn ($query) => $query->whereNull('canjeado_en'))
            ->when($estado === 'canjeado', fn ($query) => $query->whereNotNull('canjeado_en'))
            ->when($desde, fn ($query) => $query->whereDate('emitido_en', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('emitido_en', '<=', $hasta))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ValeLote $vale): array => [
                'id' => $vale->id,
                'codigo' => $vale->codigo,
                'lote_id' => $vale->lote_id,
                'posicion' => $vale->posicion,
                'administrador' => $vale->administrador?->name,
                'centro_costo' => $vale->centroCosto?->codigo,
                'emitido_en' => $vale->emitido_en?->format('Y-m-d H:i'),
                'canjeado_en' => $vale->canjeado_en?->format('Y-m-d H:i'),
            ]);

        $resumen = ValeLote::query()
            ->when($desde, fn ($query) => $query->whereDate('emitido_en', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('emitido_en', '<=', $hasta))
            ->selectRaw('count(*) as total')
            ->selectRaw('count(canjeado_en) as canjeados')
            ->first();

        $total = (int) ($resumen?->total ?? 0);
        $canjeados = (int) ($resumen?->canjeados ?? 0);

        return Inertia::render('admin/garatepass/vales-lote/index', [
            'vales' => $vales,
            'resumen' => [
                'total' => $total,
                'canjeados' => $canjeados,
                'vigentes' => $total - $canjeados,
            ],
            'filters' => [
                'search' => $search,
                'estado' => $estado,
                'desde' => $desde,
                'hasta' => $hasta,
            ],
        ]);
    }
}
