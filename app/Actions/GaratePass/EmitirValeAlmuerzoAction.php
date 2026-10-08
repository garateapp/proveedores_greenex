<?php

namespace App\Actions\GaratePass;

use App\Enums\GaratePassErrorCode;
use App\Enums\PerfilTarjetaQr;
use App\Exceptions\GaratePassApiException;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\ValeAlmuerzo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Emisión de vales a partir de un QR escaneado.
 *
 * El QR identifica a una persona, no a un vale: escanearlo emite un vale nuevo
 * y deja a esa persona bloqueada durante la ventana. El QR no se gasta; la misma
 * tarjeta sirve para todos los almuerzos del turno.
 *
 * Una tarjeta con perfil ADMIN_CASINO no emite vale de persona: en ese caso el
 * escaneo solo identifica al administrador que va a autorizar un lote.
 */
class EmitirValeAlmuerzoAction
{
    /**
     * @return array{profile: string, ticket?: array<string, mixed>, admin?: array<string, mixed>}
     */
    public function execute(string $codigoQr): array
    {
        $tarjeta = TarjetaQr::query()
            ->with(['admin.centroCosto'])
            ->where('numero_serie', $codigoQr)
            ->first();

        if ($tarjeta === null) {
            throw new GaratePassApiException(GaratePassErrorCode::TicketNotRegistered);
        }

        if ($tarjeta->estado !== null && in_array($tarjeta->estado, ['bloqueada', 'baja'], true)) {
            throw new GaratePassApiException(GaratePassErrorCode::TicketNotRegistered);
        }

        if ($tarjeta->perfil === PerfilTarjetaQr::AdminCasino) {
            return $this->payloadAdmin($tarjeta);
        }

        return DB::transaction(fn (): array => $this->emitirParaComensal($tarjeta));
    }

    /**
     * @return array{profile: string, admin: array<string, mixed>}
     */
    private function payloadAdmin(TarjetaQr $tarjeta): array
    {
        $admin = $tarjeta->admin;

        if (! $admin instanceof User) {
            throw new GaratePassApiException(GaratePassErrorCode::AdminNotAssigned);
        }

        return [
            'profile' => PerfilTarjetaQr::AdminCasino->value,
            'admin' => [
                'adminId' => (string) $admin->id,
                'adminName' => $admin->name,
                'costCenter' => $admin->centroCosto?->codigo ?? $this->textoSinCentroCosto(),
                'role' => PerfilTarjetaQr::AdminCasino->value,
            ],
        ];
    }

    /**
     * @return array{profile: string, ticket: array<string, mixed>}
     */
    private function emitirParaComensal(TarjetaQr $tarjeta): array
    {
        $asignacion = $this->asignacionVigente($tarjeta);

        if ($asignacion === null) {
            throw new GaratePassApiException(GaratePassErrorCode::TicketNotRegistered);
        }

        $trabajador = Trabajador::query()
            ->with(['contratista', 'centroCosto'])
            ->where('id', $asignacion->trabajador_id)
            ->lockForUpdate()
            ->first();

        if ($trabajador === null) {
            throw new GaratePassApiException(GaratePassErrorCode::TicketNotRegistered);
        }
        if(!$tarjeta->multiticket){}
            $this->verificarVentana($trabajador);
        }

        $emitidoEn = now();

        $vale = ValeAlmuerzo::query()->create([
            'token' => bin2hex(random_bytes(16)),
            'trabajador_id' => $trabajador->id,
            'tarjeta_qr_id' => $tarjeta->id,
            'asignacion_id' => $asignacion->id,
            'contratista' => $trabajador->contratista?->razon_social,
            'centro_costo_id' => $trabajador->centro_costo_id,
            'emitido_en' => $emitidoEn,
        ]);
        Log::info('Vale de almuerzo emitido', [
            'vale_id' => $vale->id,
            'trabajador' => $trabajador,
            'tarjeta_qr_id' => $tarjeta->id,
            'asignacion_id' => $asignacion->id,

        ]);
        return [
            'profile' => PerfilTarjetaQr::Comensal->value,
            'ticket' => [
                'ticketId' => $tarjeta->numero_serie,
                'validationToken' => $vale->token,
                'workerName' => $trabajador->nombre_completo,
                'workerRut' => $trabajador->rut_formateado,
                'contractor' => $trabajador->contratista?->razon_social,
                'costCenter' => $trabajador->centroCosto?->codigo ?? $this->textoSinCentroCosto(),
                'costCenterNeedsImputation' => $trabajador->centro_costo_id === null,
                'hypocaloricDiet' => (bool) $trabajador->dieta_hipocalorica,
                'issuedAt' => $emitidoEn->toIso8601String(),
                'multiticket' => (bool) $tarjeta->multiticket,
            ],
        ];
    }

    /**
     * Asignación vigente de la tarjeta, con lock de fila.
     *
     * Se ordena por id descendente en vez de por asignada_en porque el índice
     * (tarjeta_qr_id, desasignada_en) resuelve la búsqueda por tarjeta y
     * desasignada_en, y agregar asignada_en al ORDER BY forfeitaría el índice.
     * El id es monotónico, así que el orden resultante es equivalente.
     */
    private function asignacionVigente(TarjetaQr $tarjeta): ?TarjetaQrAsignacion
    {
        return TarjetaQrAsignacion::query()
            ->where('tarjeta_qr_id', $tarjeta->id)
            ->whereNull('desasignada_en')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    /**
     * La ventana se keyea por trabajador y no por tarjeta.
     *
     * Si se keyeara por tarjeta, al reasignarla el nuevo trabajador heredaría
     * el enfriamiento del anterior, que no le pertenece. Y una persona con dos
     * tarjetas activas podría sacar dos vales en la misma hora.
     */
    private function verificarVentana(Trabajador $trabajador): void
    {
        $ventanaHoras = (int) config('garatepass.ventana_horas', 4);

        $ultimo = $trabajador->ultimoValeEnVentana($ventanaHoras);

        if ($ultimo === null || ! $ultimo->multiticket) {
            return;
        }

        throw new GaratePassApiException(
            GaratePassErrorCode::PrintWindowActive,
            null,
            [
                'nextAvailableAt' => $ultimo->emitido_en
                    ->copy()
                    ->addHours($ventanaHoras)
                    ->toIso8601String(),
            ],
        );
    }

    private function textoSinCentroCosto(): string
    {
        return (string) config('garatepass.texto_sin_centro_costo', '(sin centro de costo)');
    }
}
