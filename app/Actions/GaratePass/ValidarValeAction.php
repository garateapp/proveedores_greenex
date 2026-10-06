<?php

namespace App\Actions\GaratePass;

use App\Enums\GaratePassErrorCode;
use App\Enums\TipoVale;
use App\Exceptions\GaratePassApiException;
use App\Models\ValeAlmuerzo;
use App\Models\ValeLote;

/**
 * Verificación de legitimidad de un vale impreso.
 *
 * El código impreso en el vale es un token opaco: ni el id autoincremental
 * (enumerable) ni el número de tarjeta (público y repetido). Validarlo contra
 * la base es lo que permite, además, que un vale emitido pero nunca impreso
 * siga siendo detectable en una conciliación.
 *
 * Este método no muta nada. El canje es exclusivo de los vales de lote, que son
 * prepagados y se usan una vez.
 */
class ValidarValeAction
{
    /**
     * @return array{status: string, type: string, ticketId: string, issuedAt: string, costCenter: string, batchId?: string, position?: int, worker?: array<string, mixed>}
     */
    public function execute(string $token): array
    {
        $valeLote = ValeLote::query()
            ->with('centroCosto')
            ->where('codigo', $token)
            ->first();

        if ($valeLote instanceof ValeLote) {
            return $this->payloadLote($valeLote);
        }

        $valeAlmuerzo = ValeAlmuerzo::query()
            ->with(['trabajador.contratista', 'centroCosto', 'tarjetaQr'])
            ->where('token', $token)
            ->first();

        if ($valeAlmuerzo instanceof ValeAlmuerzo) {
            return $this->payloadAlmuerzo($valeAlmuerzo);
        }

        throw new GaratePassApiException(GaratePassErrorCode::TicketNotFound);
    }

    /**
     * @return array{status: string, type: string, ticketId: string, issuedAt: string, costCenter: string, batchId: string, position: int}
     */
    private function payloadLote(ValeLote $vale): array
    {
        return [
            'status' => $vale->estaCanjeado() ? 'REDEEMED' : 'VALID',
            'type' => TipoVale::Lote->value,
            'ticketId' => $vale->codigo,
            'batchId' => $vale->lote_id,
            'position' => $vale->posicion,
            'issuedAt' => $vale->emitido_en->toIso8601String(),
            'costCenter' => $vale->centroCosto?->codigo ?? $this->textoSinCentroCosto(),
        ];
    }

    /**
     * @return array{status: string, type: string, ticketId: string, issuedAt: string, costCenter: string, worker: array<string, mixed>}
     */
    private function payloadAlmuerzo(ValeAlmuerzo $vale): array
    {
        $trabajador = $vale->trabajador;

        return [
            'status' => 'VALID',
            'type' => TipoVale::Almuerzo->value,
            'ticketId' => $vale->tarjetaQr?->numero_serie ?? (string) $vale->tarjeta_qr_id,
            'issuedAt' => $vale->emitido_en->toIso8601String(),
            'costCenter' => $vale->centroCosto?->codigo ?? $this->textoSinCentroCosto(),
            'costCenterNeedsImputation' => $vale->centro_costo_id === null,
            'worker' => [
                'id' => $vale->trabajador_id,
                'name' => $trabajador?->nombre_completo,
                'rut' => $trabajador?->rut_formateado,
                'contractor' => $vale->contratista,
            ],
        ];
    }

    private function textoSinCentroCosto(): string
    {
        return (string) config('garatepass.texto_sin_centro_costo', '(sin centro de costo)');
    }
}
