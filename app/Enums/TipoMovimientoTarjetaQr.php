<?php

namespace App\Enums;

enum TipoMovimientoTarjetaQr: string
{
    case Entrada = 'entrada';
    case Salida = 'salida';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida',
        };
    }
}
