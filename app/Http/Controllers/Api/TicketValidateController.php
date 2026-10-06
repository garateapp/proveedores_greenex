<?php

namespace App\Http\Controllers\Api;

use App\Actions\GaratePass\ValidarValeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketValidateRequest;
use Illuminate\Http\JsonResponse;

class TicketValidateController extends Controller
{
    public function __invoke(TicketValidateRequest $request, ValidarValeAction $validarVale): JsonResponse
    {
        return response()->json($validarVale->execute((string) $request->validated('token')));
    }
}
