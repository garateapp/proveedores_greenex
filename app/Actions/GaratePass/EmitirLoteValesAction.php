<?php

namespace App\Actions\GaratePass;

use App\Enums\GaratePassErrorCode;
use App\Enums\PerfilTarjetaQr;
use App\Exceptions\GaratePassApiException;
use App\Models\IdempotencyKey;
use App\Models\TarjetaQr;
use App\Models\User;
use App\Models\ValeLote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Emisión de vales de lote por un administrador.
 *
 * Un lote lo emite un administrador para un turno, no para personas concretas,
 * así que sus vales no se asocian a un trabajador y no entran en la ventana de
 * 4 horas: son vales prepagados, canjeables una vez.
 */
class EmitirLoteValesAction
{
    public function __construct(private readonly GenerarCorrelativo $generarCorrelativo) {}

    /**
     * Emite el lote y guarda la clave de idempotencia en una sola transacción,
     * de modo que o se guardan ambos o ninguno. Esa atomicidad es lo que evita
     * el peor escenario: lote emitido, clave no guardada, red caída, y el
     * reintento del cliente emite un segundo lote.
     *
     * El camino rápido —clave ya resuelta— no abre transacción, y el camino
     * lento es seguro ante reintentos concurrentes de la misma clave: la
     * segunda petición devuelve la respuesta de la primera en vez de emitir un
     * lote duplicado o fallar con un error de servidor.
     *
     * @return array{batchId: string, authorizedBy: string, costCenter: string, tickets: list<array{id: string, index: int}>}
     */
    public function execute(string $codigoQr, int $cantidad, string $claveIdempotencia, string $endpoint): array
    {
        $admin = $this->resolverAdmin($codigoQr);
        $hash = $this->hashDePeticion($codigoQr, $cantidad);

        $respuestaPrevia = $this->buscarPrevia($claveIdempotencia, $endpoint);

        if ($respuestaPrevia !== null) {
            return $this->respuestaGuardada($respuestaPrevia, $hash);
        }

        try {
            return DB::transaction(function () use ($admin, $cantidad, $claveIdempotencia, $endpoint, $hash): array {
                $previa = $this->buscarPrevia($claveIdempotencia, $endpoint, true);

                if ($previa !== null) {
                    return $this->respuestaGuardada($previa, $hash);
                }

                $respuesta = $this->emitir($admin, $cantidad);

                IdempotencyKey::query()->create([
                    'clave' => $claveIdempotencia,
                    'endpoint' => $endpoint,
                    'request_hash' => $hash,
                    'response_status' => 200,
                    'response_body' => json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);

                return $respuesta;
            });
        } catch (UniqueConstraintViolationException $e) {
            $previa = $this->buscarPrevia($claveIdempotencia, $endpoint);

            if ($previa === null) {
                throw $e;
            }

            return $this->respuestaGuardada($previa, $hash);
        }
    }

    /**
     * @return array{batchId: string, authorizedBy: string, costCenter: string, tickets: list<array{id: string, index: int}>}
     */
    private function emitir(User $admin, int $cantidad): array
    {
        $loteId = (string) Str::uuid();
        $emitidoEn = now();
        $centroCosto = $admin->centroCosto;
        $prefijo = (string) config('garatepass.prefijo_lote', 'LOT-');
        $tickets = [];

        for ($posicion = 1; $posicion <= $cantidad; $posicion++) {
            $vale = ValeLote::query()->create([
                'codigo' => $this->generarCorrelativo->next('vales_lote', $prefijo),
                'lote_id' => $loteId,
                'posicion' => $posicion,
                'administrador_id' => $admin->id,
                'centro_costo_id' => $admin->centro_costo_id,
                'emitido_en' => $emitidoEn,
            ]);

            $tickets[] = [
                'id' => $vale->codigo,
                'index' => $vale->posicion,
            ];
        }

        return [
            'batchId' => $loteId,
            'authorizedBy' => $admin->name,
            'costCenter' => $centroCosto?->codigo ?? (string) config('garatepass.texto_sin_centro_costo', '(sin centro de costo)'),
            'tickets' => $tickets,
        ];
    }

    /**
     * La primera comprobación va fuera de la transacción: un reintento ya
     * resuelto no debe abrir una transacción ni bloquear filas. Se repite
     * dentro con bloqueo de fila antes de emitir, y si aun así dos peticiones
     * idénticas llegan a la vez, la segunda recibe el error de unicidad de la
     * clave y cae en el catch, donde devuelve la respuesta que ganó.
     */
    private function buscarPrevia(string $claveIdempotencia, string $endpoint, bool $conBloqueo = false): ?IdempotencyKey
    {
        $consulta = IdempotencyKey::query()
            ->where('clave', $claveIdempotencia)
            ->where('endpoint', $endpoint);

        if ($conBloqueo) {
            $consulta->lockForUpdate();
        }

        return $consulta->first();
    }

    private function resolverAdmin(string $codigoQr): User
    {
        $tarjeta = TarjetaQr::query()
            ->with(['admin.centroCosto'])
            ->where('numero_serie', $codigoQr)
            ->first();

        if ($tarjeta === null) {
            throw new GaratePassApiException(GaratePassErrorCode::TicketNotRegistered);
        }

        if ($tarjeta->perfil !== PerfilTarjetaQr::AdminCasino) {
            throw new GaratePassApiException(GaratePassErrorCode::Forbidden);
        }

        $admin = $tarjeta->admin;

        if (! $admin instanceof User) {
            throw new GaratePassApiException(GaratePassErrorCode::AdminNotAssigned);
        }

        if (! $admin->puedeEmitirLoteVales()) {
            throw new GaratePassApiException(GaratePassErrorCode::Forbidden);
        }

        return $admin;
    }

    /**
     * La misma clave con otra carga es un bug del cliente, no un reintento, así
     * que se rechaza en vez de devolver la respuesta anterior.
     *
     * @return array{batchId: string, authorizedBy: string, costCenter: string, tickets: list<array{id: string, index: int}>}
     */
    private function respuestaGuardada(IdempotencyKey $registro, string $hash): array
    {
        if (! hash_equals($registro->request_hash, $hash)) {
            throw new GaratePassApiException(GaratePassErrorCode::IdempotencyKeyReused);
        }

        $guardada = $registro->respuestaDecodificada();

        if ($guardada === null) {
            throw new GaratePassApiException(GaratePassErrorCode::IdempotencyKeyReused);
        }

        return $guardada;
    }

    private function hashDePeticion(string $codigoQr, int $cantidad): string
    {
        $normalizado = json_encode(
            ['adminCode' => $codigoQr, 'quantity' => $cantidad],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return hash('sha256', (string) $normalizado);
    }
}
