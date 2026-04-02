<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Contratista = 'contratista';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Contratista => 'Contratista',
            self::Supervisor => 'Supervisor',
        };
    }

    public function canManageContratistas(): bool
    {
        return $this === self::Admin;
    }

    public function canManageWorkers(): bool
    {
        return in_array($this, [self::Admin, self::Contratista, self::Supervisor]);
    }

    public function canViewAllData(): bool
    {
        return $this === self::Admin;
    }
}
