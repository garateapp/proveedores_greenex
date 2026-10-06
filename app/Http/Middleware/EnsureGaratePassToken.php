<?php

namespace App\Http\Middleware;

use App\Enums\GaratePassErrorCode;
use App\Exceptions\GaratePassApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGaratePassToken
{
    /**
     * Autentica la app GaratePass contra el token compartido.
     *
     * El token identifica a la aplicación, no a una persona, así que
     * request()->user() queda sin resolver a propósito: cada endpoint
     * determina la identidad de quien opera a partir del QR escaneado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configurado = (string) config('garatepass.token');

        if ($configurado === '') {
            throw new GaratePassApiException(GaratePassErrorCode::Unauthorized);
        }

        $presentado = (string) ($request->bearerToken() ?? '');

        if ($presentado === '' || ! hash_equals($configurado, $presentado)) {
            throw new GaratePassApiException(GaratePassErrorCode::Unauthorized);
        }

        return $next($request);
    }
}
