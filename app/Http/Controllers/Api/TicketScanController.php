<?php

namespace App\Http\Controllers\Api;

use App\Actions\GaratePass\EmitirValeAlmuerzoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketScanRequest;
use Illuminate\Http\JsonResponse;

class TicketScanController extends Controller
{
    public function __invoke(TicketScanRequest $request, EmitirValeAlmuerzoAction $emitirValeAlmuerzo): JsonResponse
    {
        $payload = $emitirValeAlmuerzo->execute((string) $request->validated('code'));

        $respuesta = ['profile' => $payload['profile']];

        if (isset($payload['tickets'])) {
            if (count($payload['tickets']) === 1) {
                $respuesta['ticket'] = $payload['tickets'][0];
            } else {
                $respuesta['tickets'] = $payload['tickets'];
            }
        }

        if (isset($payload['admin'])) {
            $respuesta['admin'] = $payload['admin'];
        }

        $respuesta['serverTime'] = now()->toIso8601String();

        return response()->json($respuesta);
    }
}
