<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Packing\AssignTarjetaQrAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TarjetaQrAsignacionRequest;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
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

    /**
     * Cierra la asignación activa y devuelve la tarjeta a estado disponible.
     */
    public function destroy(TarjetaQr $tarjeta): RedirectResponse
    {
        $asignacionActiva = TarjetaQrAsignacion::query()
            ->where('tarjeta_qr_id', $tarjeta->id)
            ->whereNull('desasignada_en')
            ->first();

        if ($asignacionActiva === null) {
            return redirect('/admin/packing/tarjetas')
                ->with('success', 'La tarjeta ya está disponible.');
        }

        $asignacionActiva->update([
            'desasignada_por' => auth()->id(),
            'desasignada_en' => now(),
            'observaciones' => $asignacionActiva->observaciones
                ? $asignacionActiva->observaciones.' · Desasignada manualmente'
                : 'Desasignada manualmente',
        ]);

        $tarjeta->update(['estado' => 'disponible']);

        return redirect('/admin/packing/tarjetas')
            ->with('success', 'Tarjeta desasignada y disponible.');
    }
}
