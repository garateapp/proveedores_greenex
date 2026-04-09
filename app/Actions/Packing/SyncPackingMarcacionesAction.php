<?php

namespace App\Actions\Packing;

use App\Models\MarcacionPacking;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
use App\Models\User;
use Illuminate\Support\Carbon;

class SyncPackingMarcacionesAction
{
    /**
     * @param  array<int, array<string, mixed>>  $marcaciones
     * @return array{created:int, ignored:int, rejected:int}
     */
    public function execute(array $marcaciones, ?User $actor, ?string $syncBatchId = null): array
    {
        $timezone = config('app.timezone');

        usort($marcaciones, static function (array $left, array $right): int {
            return strcmp((string) $left['marcado_en'], (string) $right['marcado_en']);
        });

        $created = 0;
        $ignored = 0;
        $rejected = 0;

        foreach ($marcaciones as $marcacion) {
            if (MarcacionPacking::query()->where('uuid', $marcacion['uuid'])->exists()) {
                $ignored++;

                continue;
            }

            $tarjeta = TarjetaQr::query()
                ->where('codigo_qr', $marcacion['codigo_qr'])
                ->first();

            if ($tarjeta === null || in_array($tarjeta->estado, ['bloqueada', 'baja'], true)) {
                $rejected++;

                continue;
            }

            $marcadoEn = Carbon::parse((string) $marcacion['marcado_en'], $timezone)
                ->setTimezone($timezone);

            $asignacion = TarjetaQrAsignacion::query()
                ->where('tarjeta_qr_id', $tarjeta->id)
                ->where('asignada_en', '<=', $marcadoEn)
                ->where(function ($query) use ($marcadoEn): void {
                    $query->whereNull('desasignada_en')
                        ->orWhere('desasignada_en', '>', $marcadoEn);
                })
                ->latest('asignada_en')
                ->first();

            if ($asignacion === null) {
                $rejected++;

                continue;
            }

            $duplicateWindowStart = $marcadoEn->copy()->subMinutes(120);
            $duplicateWindowEnd = $marcadoEn->copy()->addMinutes(120);

            $duplicateExists = MarcacionPacking::query()
                ->where('trabajador_id', $asignacion->trabajador_id)
                ->whereBetween('marcado_en', [$duplicateWindowStart, $duplicateWindowEnd])
                ->exists();

            if ($duplicateExists) {
                $ignored++;

                continue;
            }

            MarcacionPacking::create([
                'uuid' => $marcacion['uuid'],
                'trabajador_id' => $asignacion->trabajador_id,
                'tarjeta_qr_id' => $tarjeta->id,
                'tarjeta_qr_asignacion_id' => $asignacion->id,
                'numero_serie_snapshot' => $tarjeta->numero_serie,
                'codigo_qr_snapshot' => $tarjeta->codigo_qr,
                'marcado_en' => $marcadoEn,
                'registrado_por' => $actor?->id,
                'device_id' => $marcacion['device_id'] ?? null,
                'sync_batch_id' => $syncBatchId,
                'latitud' => $marcacion['latitud'] ?? null,
                'longitud' => $marcacion['longitud'] ?? null,
                'ubicacion_id' => $marcacion['ubicacion_id'] ?? null,
                'ubicacion_texto' => $marcacion['ubicacion_texto'] ?? null,
                'metadata' => $marcacion['metadata'] ?? null,
                'sincronizado_at' => now(),
            ]);

            $created++;
        }

        return [
            'created' => $created,
            'ignored' => $ignored,
            'rejected' => $rejected,
        ];
    }
}
