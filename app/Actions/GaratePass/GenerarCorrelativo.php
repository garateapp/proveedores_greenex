<?php

namespace App\Actions\GaratePass;

use App\Models\Correlativo;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Correlativos legibles y secuenciales para los vales de lote.
 *
 * El bloqueo de fila es lo que impide que dos emisiones simultáneas obtengan
 * el mismo número. Debe invocarse siempre dentro de una transacción ya
 * abierta, para que el incremento y el insert del vale committeen juntos.
 */
class GenerarCorrelativo
{
    public function next(string $clave, string $prefijo, int $ancho = 6): string
    {
        $correlativo = $this->bloquear($clave);

        if ($correlativo === null) {
            $correlativo = $this->crearOReintentar($clave);
        } else {
            $correlativo->increment('ultimo_numero');
        }

        return $prefijo.str_pad((string) $correlativo->ultimo_numero, $ancho, '0', STR_PAD_LEFT);
    }

    private function bloquear(string $clave): ?Correlativo
    {
        return Correlativo::query()->where('clave', $clave)->lockForUpdate()->first();
    }

    /**
     * La primera emisión de una clave dada es el único momento en que la fila no
     * existe, y bloquear una fila ausente no bloquea nada: dos emisiones
     * simultáneas pueden ver `null` a la vez y ambas intentar insertar. La clave
     * es única, así que una de las dos gana y la otra recibe el error de
     * unicidad; en ese caso se relee la fila que ganó y se incrementa, que es
     * exactamente lo que habría ocurrido si la segunda hubiera llegado unos
     * milisegundos después.
     */
    private function crearOReintentar(string $clave): Correlativo
    {
        try {
            return Correlativo::query()->create([
                'clave' => $clave,
                'ultimo_numero' => 1,
            ]);
        } catch (UniqueConstraintViolationException) {
            $correlativo = $this->bloquear($clave);

            if ($correlativo === null) {
                throw new \RuntimeException("No se pudo crear ni leer el correlativo [{$clave}].");
            }

            $correlativo->increment('ultimo_numero');

            return $correlativo;
        }
    }
}
