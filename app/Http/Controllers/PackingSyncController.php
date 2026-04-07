<?php

namespace App\Http\Controllers;

use App\Actions\Packing\SyncPackingMarcacionesAction;
use App\Http\Requests\PackingSyncRequest;
use Illuminate\Http\JsonResponse;

class PackingSyncController extends Controller
{
    public function store(
        PackingSyncRequest $request,
        SyncPackingMarcacionesAction $syncPackingMarcacionesAction,
    ): JsonResponse {
        $result = $syncPackingMarcacionesAction->execute(
            $request->validated('marcaciones'),
            $request->user(),
            $request->validated('sync_batch_id'),
        );

        return response()->json($result);
    }
}
