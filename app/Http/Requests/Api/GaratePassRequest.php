<?php

namespace App\Http\Requests\Api;

use App\Enums\GaratePassErrorCode;
use App\Exceptions\GaratePassApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los requests de GaratePass.
 *
 * Traduce los fallos de validación al mismo sobre de error que usa el resto de
 * la API ({ "error": { "code", "message" } }), de modo que la app nunca reciba
 * la forma por defecto de Laravel ({ "message", "errors" }) y tenga que
 * distinguir dos formatos para el mismo endpoint.
 */
abstract class GaratePassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Código con el que viaja un fallo de validación de este request.
     */
    abstract protected function validationErrorCode(): GaratePassErrorCode;

    /**
     * @param  array<string, string>  $messages
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new GaratePassApiException(
            $this->validationErrorCode(),
            (string) $validator->errors()->first(),
        );
    }
}
