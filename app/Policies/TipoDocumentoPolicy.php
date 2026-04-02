<?php

namespace App\Policies;

use App\Models\TipoDocumento;
use App\Models\User;

class TipoDocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TipoDocumento $tipoDocumento): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TipoDocumento $tipoDocumento): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TipoDocumento $tipoDocumento): bool
    {
        return $user->isAdmin();
    }
}
