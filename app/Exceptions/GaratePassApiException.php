<?php

namespace App\Exceptions;

use App\Enums\GaratePassErrorCode;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * Error de la API de GaratePass. Todos los endpoints la usan para que el
 * sobre de respuesta sea idéntico en los tres endpoints:
 *
 *   { "error": { "code": "...", "message": "...", "nextAvailableAt": "..." } }
 *
 * nextAvailableAt solo viaja en PRINT_WINDOW_ACTIVE.
 */
class GaratePassApiException extends RuntimeException
{
    /**
     * @param  array<string, string>  $extra
     */
    public function __construct(
        public readonly GaratePassErrorCode $errorCode,
        ?string $message = null,
        public readonly array $extra = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?? $errorCode->defaultMessage(), 0, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => array_merge([
                'code' => $this->errorCode->value,
                'message' => $this->getMessage(),
            ], $this->extra),
        ], $this->errorCode->httpStatus());
    }
}
