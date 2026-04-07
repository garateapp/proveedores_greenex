<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Packing\AssignTarjetaQrAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TarjetaQrAsignacionRequest;
use App\Models\TarjetaQr;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;

class PackingTarjetaAsignacionController extends Controller
{
    public function store(
        TarjetaQrAsignacionRequest $request,
        TarjetaQr $tarjeta,
        AssignTarjetaQrAction $assignTarjetaQrAction,
    ): RedirectResponse {
        $trabajador = Trabajador::query()->findOrFail($request->validated('trabajador_id'));

        $assignTarjetaQrAction->execute(
            $tarjeta,
            $trabajador,
            $request->user(),
            $request->validated('asignada_en'),
            $request->validated('observaciones'),
        );

        return redirect('/admin/packing/tarjetas')
            ->with('success', 'Tarjeta QR asignada correctamente.');
    }
}
