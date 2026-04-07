<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarcacionPacking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackingMarcacionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $search = trim((string) $request->input('search', ''));

        $query = MarcacionPacking::query()
            ->with(['trabajador.contratista', 'tarjetaQr', 'ubicacion'])
            ->when(! $isAdmin, function ($innerQuery) use ($user): void {
                if ($user?->contratista_id === null) {
                    $innerQuery->whereRaw('1 = 0');

                    return;
                }

                $innerQuery->whereHas('trabajador', function ($trabajadorQuery) use ($user): void {
                    $trabajadorQuery->where('contratista_id', $user->contratista_id);
                });
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->whereHas('trabajador', function ($trabajadorQuery) use ($search): void {
                        $trabajadorQuery->where('documento', 'like', "%{$search}%")
                            ->orWhere('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%");
                    })->orWhere('numero_serie_snapshot', 'like', "%{$search}%");
                });
            });

        $marcaciones = $query
            ->orderByDesc('marcado_en')
            ->limit(200)
            ->get()
            ->map(fn (MarcacionPacking $marcacion): array => [
                'id' => $marcacion->id,
                'uuid' => $marcacion->uuid,
                'trabajador' => $marcacion->trabajador?->nombre_completo,
                'documento' => $marcacion->trabajador?->documento,
                'contratista' => $marcacion->trabajador?->contratista?->razon_social,
                'numero_serie' => $marcacion->numero_serie_snapshot,
                'codigo_qr' => $marcacion->codigo_qr_snapshot,
                'marcado_en' => $marcacion->marcado_en?->format('Y-m-d H:i:s'),
                'device_id' => $marcacion->device_id,
                'sync_batch_id' => $marcacion->sync_batch_id,
                'ubicacion' => $marcacion->ubicacion?->nombre ?? $marcacion->ubicacion_texto,
            ])
            ->values();

        $indexUrl = $request->routeIs('admin.*') ? '/admin/packing/marcaciones' : '/packing/marcaciones';

        return Inertia::render('admin/packing/marcaciones/index', [
            'marcaciones' => $marcaciones,
            'indexUrl' => $indexUrl,
            'canManageCards' => $isAdmin,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
