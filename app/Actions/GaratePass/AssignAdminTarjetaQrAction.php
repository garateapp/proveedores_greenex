<?php

namespace App\Actions\GaratePass;

use App\Enums\PerfilTarjetaQr;
use App\Models\TarjetaQr;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Convierte una tarjeta de comensal en una de administrador, y viceversa.
 *
 * Los dos perfiles son excluyentes por diseño, no por convenience: al escanear,
 * la app decide qué hacer según el perfil de la tarjeta. Si una tarjeta de
 * administrador conservara una asignación de trabajador activa, el escaneo
 * seguiría resolviendo al administrador y el trabajador quedaría ocupando su
 * cupón de asignación sin poder usarlo. Por eso la conversión cierra la
 * asignación en vez de dejarla colgando.
 */
class AssignAdminTarjetaQrAction
{
    public function executeAsAdmin(
        TarjetaQr $tarjeta,
        User $administrador,
        User $actor,
        ?string $observaciones = null,
    ): TarjetaQr {
        if (in_array($tarjeta->estado, ['bloqueada', 'baja'], true)) {
            throw ValidationException::withMessages([
                'tarjeta_id' => 'La tarjeta seleccionada no se puede asignar a un administrador.',
            ]);
        }

        return DB::transaction(function () use ($tarjeta, $administrador, $actor, $observaciones): TarjetaQr {
            $this->cerrarAsignacionActiva($tarjeta, $actor);

            $tarjeta->forceFill([
                'perfil' => PerfilTarjetaQr::AdminCasino,
                'admin_user_id' => $administrador->id,
                'estado' => 'asignada',
                'observaciones' => $observaciones,
            ])->save();

            return $tarjeta;
        });
    }

    /**
     * Devuelve la tarjeta al perfil de comensal. Se desvincula el administrador
     * porque un perfil COMENSAL con `admin_user_id` poblado es un estado que
     * ningún endpoint sabe leer y que después confundiría a quien la audite.
     */
    public function executeAsComensal(TarjetaQr $tarjeta): TarjetaQr
    {
        return DB::transaction(function () use ($tarjeta): TarjetaQr {
            $tarjeta->forceFill([
                'perfil' => PerfilTarjetaQr::Comensal,
                'admin_user_id' => null,
                'estado' => $tarjeta->asignacionActiva === null ? 'disponible' : 'asignada',
            ])->save();

            return $tarjeta;
        });
    }

    private function cerrarAsignacionActiva(TarjetaQr $tarjeta, User $actor): void
    {
        $asignacion = $tarjeta->asignacionActiva()->first();

        if ($asignacion === null) {
            return;
        }

        $asignacion->update([
            'desasignada_por' => $actor->id,
            'desasignada_en' => now(),
        ]);
    }
}
