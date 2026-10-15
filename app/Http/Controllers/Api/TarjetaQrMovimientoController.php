<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoMovimientoTarjetaQr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTarjetaQrMovimientoRequest;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrMovimiento;
use Illuminate\Http\JsonResponse;

class TarjetaQrMovimientoController extends Controller
{
    public function store(StoreTarjetaQrMovimientoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tarjeta = TarjetaQr::query()
            ->where('codigo_qr', $validated['qr'])
            ->firstOrFail();

        $movimiento = TarjetaQrMovimiento::query()->create([
            'tarjeta_qr_id' => $tarjeta->id,
            'codigo_qr' => $tarjeta->codigo_qr,
            'tipo' => $validated['tipo'],
            'filtro' => $validated['filtro'],
            'filtro_nombre' => $validated['filtro_nombre'],
            'fecha' => $validated['fecha'],
        ]);

        return response()->json([
            'status' => 'created',
            'message' => 'Movimiento de tarjeta QR registrado exitosamente.',
            'data' => [
                'id' => $movimiento->id,
                'qr' => $movimiento->codigo_qr,
                'tipo' => $movimiento->tipo instanceof TipoMovimientoTarjetaQr
                    ? $movimiento->tipo->value
                    : $movimiento->tipo,
                'filtro' => $movimiento->filtro,
                'filtro_nombre' => $movimiento->filtro_nombre,
                'fecha' => $movimiento->fecha?->toIso8601String(),
            ],
        ], 201);
    }
}
