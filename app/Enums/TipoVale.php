<?php

namespace App\Enums;

enum TipoVale: string
{
    case Almuerzo = 'ALMUERZO';
    case Lote = 'LOTE';

    public function label(): string
    {
        return match ($this) {
            self::Almuerzo => 'Vale de almuerzo',
            self::Lote => 'Vale de lote',
        };
    }
}
