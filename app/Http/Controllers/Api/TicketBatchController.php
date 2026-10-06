<?php

namespace App\Http\Controllers\Api;

use App\Actions\GaratePass\EmitirLoteValesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketBatchRequest;
use Illuminate\Http\JsonResponse;

class TicketBatchController extends Controller
{
    public function __invoke(TicketBatchRequest $request, EmitirLoteValesAction $emitirLoteVales): JsonResponse
    {
        $validated = $request->validated();

        $respuesta = $emitirLoteVales->execute(
            (string) $validated['adminCode'],
            (int) $validated['quantity'],
            $request->idempotencyKey(),
            'POST /api/v1/tickets/batch',
        );

        return response()->json($respuesta);
    }
}
