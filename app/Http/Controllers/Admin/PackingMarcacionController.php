<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contratista;
use App\Models\MarcacionPacking;
use App\Models\Ubicacion;
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
        $contratistaId = $isAdmin ? $request->integer('contratista_id') : null;
        $ubicacionId = $request->integer('ubicacion_id');
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));

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
            ->when($isAdmin && $contratistaId > 0, function ($innerQuery) use ($contratistaId): void {
                $innerQuery->whereHas('trabajador', function ($trabajadorQuery) use ($contratistaId): void {
                    $trabajadorQuery->where('contratista_id', $contratistaId);
                });
            })
            ->when($ubicacionId > 0, function ($innerQuery) use ($ubicacionId): void {
                $innerQuery->where('ubicacion_id', $ubicacionId);
            })
            ->when($dateFrom !== '', function ($innerQuery) use ($dateFrom): void {
                $innerQuery->whereDate('marcado_en', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($innerQuery) use ($dateTo): void {
                $innerQuery->whereDate('marcado_en', '<=', $dateTo);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->whereHas('trabajador', function ($trabajadorQuery) use ($search): void {
                        $trabajadorQuery->where('documento', 'like', "%{$search}%")
                            ->orWhere('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%");
                    })->orWhere('numero_serie_snapshot', 'like', "%{$search}%")
                        ->orWhere('codigo_qr_snapshot', 'like', "%{$search}%");
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
        $contratistas = $isAdmin
            ? Contratista::query()
                ->select(['id', 'razon_social'])
                ->orderBy('razon_social')
                ->get()
                ->map(fn (Contratista $contratista): array => [
                    'id' => $contratista->id,
                    'nombre' => $contratista->razon_social,
                ])
                ->values()
            : collect();
        $ubicaciones = Ubicacion::query()
            ->with('padre')
            ->where('activa', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Ubicacion $ubicacion): array => [
                'id' => $ubicacion->id,
                'nombre' => $ubicacion->nombre_completo,
            ])
            ->values();

        return Inertia::render('admin/packing/marcaciones/index', [
            'marcaciones' => $marcaciones,
            'indexUrl' => $indexUrl,
            'canManageCards' => $isAdmin,
            'contratistas' => $contratistas,
            'ubicaciones' => $ubicaciones,
            'filters' => [
                'search' => $search,
                'contratista_id' => $contratistaId > 0 ? (string) $contratistaId : '',
                'ubicacion_id' => $ubicacionId > 0 ? (string) $ubicacionId : '',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
