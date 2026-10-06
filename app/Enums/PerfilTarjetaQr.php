<?php

namespace App\Enums;

enum PerfilTarjetaQr: string
{
    case Comensal = 'COMENSAL';
    case AdminCasino = 'ADMIN_CASINO';

    public function label(): string
    {
        return match ($this) {
            self::Comensal => 'Comensal',
            self::AdminCasino => 'Administrador casino',
        };
    }

    public function requiereAdmin(): bool
    {
        return $this === self::AdminCasino;
    }
}
