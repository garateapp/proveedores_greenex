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
        $search = trim((string) $request->input('search', ''));

        $marcaciones = MarcacionPacking::query()
            ->with(['trabajador.contratista', 'tarjetaQr'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->whereHas('trabajador', function ($trabajadorQuery) use ($search): void {
                        $trabajadorQuery->where('documento', 'like', "%{$search}%")
                            ->orWhere('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%");
                    })->orWhere('numero_serie_snapshot', 'like', "%{$search}%");
                });
            })
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
            ])
            ->values();

        return Inertia::render('admin/packing/marcaciones/index', [
            'marcaciones' => $marcaciones,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
