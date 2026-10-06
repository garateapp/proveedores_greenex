<?php

namespace App\Actions\GaratePass;

use App\Enums\GaratePassErrorCode;
use App\Exceptions\GaratePassApiException;
use App\Models\ValeLote;
use Illuminate\Support\Facades\DB;

/**
 * Canje de un vale de lote.
 *
 * El bloqueo de fila evita que dos cobros simultáneos pasen ambos el chequeo de
 * canjeado y consuman el mismo vale.
 */
class CanjearValeLoteAction
{
    /**
     * @return array{status: string, ticketId: string, batchId: string, position: int, costCenter: string, redeemedAt: string}
     */
    public function execute(string $codigo): array
    {
        return DB::transaction(function () use ($codigo): array {
            $vale = ValeLote::query()
                ->with('centroCosto')
                ->where('codigo', $codigo)
                ->lockForUpdate()
                ->first();

            if (! $vale instanceof ValeLote) {
                throw new GaratePassApiException(GaratePassErrorCode::TicketNotFound);
            }

            if ($vale->estaCanjeado()) {
                throw new GaratePassApiException(GaratePassErrorCode::TicketAlreadyUsed);
            }

            $vale->forceFill([
                'canjeado_en' => now(),
            ])->save();

            return [
                'status' => 'REDEEMED',
                'ticketId' => $vale->codigo,
                'batchId' => $vale->lote_id,
                'position' => $vale->posicion,
                'costCenter' => $vale->centroCosto?->codigo ?? (string) config('garatepass.texto_sin_centro_costo', '(sin centro de costo)'),
                'redeemedAt' => $vale->canjeado_en->toIso8601String(),
            ];
        });
    }
}
