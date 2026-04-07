<?php

namespace App\Actions\Packing;

use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTarjetaQrAction
{
    public function execute(
        TarjetaQr $tarjeta,
        Trabajador $trabajador,
        User $actor,
        string $asignadaEn,
        ?string $observaciones = null,
    ): TarjetaQrAsignacion {
        if (in_array($tarjeta->estado, ['bloqueada', 'baja'], true)) {
            throw ValidationException::withMessages([
                'tarjeta' => 'La tarjeta seleccionada no se puede asignar.',
            ]);
        }

        $fechaAsignacion = Carbon::parse($asignadaEn);

        return DB::transaction(function () use ($tarjeta, $trabajador, $actor, $fechaAsignacion, $observaciones): TarjetaQrAsignacion {
            $asignacionActivaTarjeta = TarjetaQrAsignacion::query()
                ->where('tarjeta_qr_id', $tarjeta->id)
                ->whereNull('desasignada_en')
                ->first();

            if ($asignacionActivaTarjeta !== null && $asignacionActivaTarjeta->trabajador_id === $trabajador->id) {
                return $asignacionActivaTarjeta;
            }

            $asignacionActivaTrabajador = TarjetaQrAsignacion::query()
                ->where('trabajador_id', $trabajador->id)
                ->whereNull('desasignada_en')
                ->first();

            $this->closeAssignment($asignacionActivaTrabajador, $actor, $fechaAsignacion);
            $this->closeAssignment($asignacionActivaTarjeta, $actor, $fechaAsignacion);

            $nuevaAsignacion = TarjetaQrAsignacion::create([
                'tarjeta_qr_id' => $tarjeta->id,
                'trabajador_id' => $trabajador->id,
                'asignada_por' => $actor->id,
                'asignada_en' => $fechaAsignacion,
                'observaciones' => $observaciones,
            ]);

            TarjetaQr::query()->whereKey($tarjeta->id)->update([
                'estado' => 'asignada',
            ]);

            return $nuevaAsignacion;
        });
    }

    private function closeAssignment(?TarjetaQrAsignacion $asignacion, User $actor, Carbon $fechaAsignacion): void
    {
        if ($asignacion === null || $asignacion->desasignada_en !== null) {
            return;
        }

        $asignacion->update([
            'desasignada_por' => $actor->id,
            'desasignada_en' => $fechaAsignacion,
        ]);

        $asignacion->tarjetaQr()->update([
            'estado' => 'disponible',
        ]);
    }
}
