<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketConsultaRequest;
use App\Models\TarjetaQr;
use App\Models\ValeAlmuerzo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TicketConsultaController extends Controller
{
    public function __invoke(TicketConsultaRequest $request)
    {
        $numeroSerie = $request->numeroSerie();
        $fecha = $request->fecha() ?? now()->toDateString();

        $tarjeta = TarjetaQr::query()
            ->where('numero_serie', $numeroSerie)
            ->first();
        Log::info('Consulta de ticket', [
            'numero_serie' => $numeroSerie,
            'fecha' => $fecha,
            'tarjeta_id' => $tarjeta?->id,

        ]);

        if ($tarjeta === null) {
            abort(404, 'Tarjeta no registrada.');
        }

        if ($tarjeta->estado !== null && in_array($tarjeta->estado, ['bloqueada', 'baja'], true)) {
            abort(404, 'Tarjeta bloqueada o dada de baja.');
        }

        $asignacion = $tarjeta->asignacionActiva;

        if ($asignacion === null) {
            abort(404, 'La tarjeta no tiene una asignación vigente.');
        }

        $trabajador = $asignacion->trabajador;

        if ($trabajador === null) {
            abort(404, 'El trabajador asignado no existe.');
        }

        $fechaParseada = Carbon::parse($fecha);

        $vales = ValeAlmuerzo::query()
            ->with(['centroCosto'])
            ->where('trabajador_id', $trabajador->id)
            ->whereDate('emitido_en', $fechaParseada->toDateString())
            ->orderByDesc('emitido_en')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ValeAlmuerzo $vale): array => [
                'token' => $vale->token,
                'emitido_en' => $vale->emitido_en->toIso8601String(),
                'centro_costo' => $vale->centroCosto?->codigo ?? null,
                'centro_costo_nombre' => $vale->centroCosto?->nombre ?? null,
            ])
            ->all();

        return response()->json([
            'profile' => 'comensal',
            'fecha' => $fechaParseada->toDateString(),
            'tarjeta' => [
                'numero_serie' => $tarjeta->numero_serie,
                'estado' => $tarjeta->estado,
                'multiticket' => (bool) $tarjeta->multiticket,
            ],
            'trabajador' => [
                'id' => $trabajador->id,
                'nombre' => $trabajador->nombre_completo,
                'rut' => $trabajador->rut_formateado,
                'contratista' => $trabajador->contratista?->razon_social ?? null,
            ],
            'tiene_vales' => count($vales) > 0,
            'vales_emitidos' => $vales,
            'total_vales_dia' => count($vales),
            'motivo_falla' => null,
        ]);
    }
}
