<?php

namespace App\Http\Controllers\Api;

use App\Actions\GaratePass\CanjearValeLoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketRedeemRequest;
use Illuminate\Http\JsonResponse;

class TicketRedeemController extends Controller
{
    public function __invoke(TicketRedeemRequest $request, CanjearValeLoteAction $canjearValeLote): JsonResponse
    {
        return response()->json($canjearValeLote->execute((string) $request->validated('code')));
    }
}
