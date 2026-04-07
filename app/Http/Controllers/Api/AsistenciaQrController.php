<?php

namespace App\Http\Controllers\Api;

use App\Actions\Packing\SyncPackingMarcacionesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAsistenciaQrRequest;
use App\Models\MarcacionPacking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AsistenciaQrController extends Controller
{
    public function store(
        StoreAsistenciaQrRequest $request,
        SyncPackingMarcacionesAction $syncPackingMarcacionesAction,
    ): JsonResponse {
        $validated = $request->validated();
        $uuid = (string) Str::uuid();

        $result = $syncPackingMarcacionesAction->execute(
            [[
                'uuid' => $uuid,
                'codigo_qr' => $validated['codigo_qr'],
                'marcado_en' => $validated['marcado_en'] ?? now()->toDateTimeString(),
                'device_id' => $validated['device_id'] ?? null,
                'latitud' => $validated['latitud'] ?? null,
                'longitud' => $validated['longitud'] ?? null,
                'ubicacion_id' => $validated['ubicacion_id'] ?? null,
                'ubicacion_texto' => $validated['ubicacion_texto'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
            ]],
            $request->user(),
            $validated['sync_batch_id'] ?? null,
        );

        if (($result['created'] ?? 0) > 0) {
            $marcacion = MarcacionPacking::query()
                ->with(['trabajador', 'tarjetaQr', 'ubicacion'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            return response()->json([
                'status' => 'created',
                'message' => 'Marcación registrada exitosamente.',
                'data' => [
                    'uuid' => $marcacion->uuid,
                    'marcado_en' => $marcacion->marcado_en?->toISOString(),
                    'trabajador' => [
                        'id' => $marcacion->trabajador_id,
                        'nombre' => $marcacion->trabajador?->nombre_completo,
                    ],
                    'tarjeta' => [
                        'id' => $marcacion->tarjeta_qr_id,
                        'codigo_qr' => $marcacion->codigo_qr_snapshot,
                        'numero_serie' => $marcacion->numero_serie_snapshot,
                    ],
                    'ubicacion' => $marcacion->ubicacion_id ? [
                        'id' => $marcacion->ubicacion_id,
                        'nombre' => $marcacion->ubicacion?->nombre,
                    ] : null,
                ],
            ], 201);
        }

        if (($result['ignored'] ?? 0) > 0) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Marcación ignorada por duplicado dentro de 120 minutos.',
            ]);
        }

        return response()->json([
            'status' => 'rejected',
            'message' => 'Tarjeta QR inválida, inactiva o sin asignación vigente en la hora informada.',
        ], 422);
    }
}
