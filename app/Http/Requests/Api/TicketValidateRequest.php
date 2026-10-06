<?php

namespace App\Http\Requests\Api;

use App\Enums\GaratePassErrorCode;

class TicketValidateRequest extends GaratePassRequest
{
    /**
     * Este endpoint recibe los dos tipos de vale: el token opaco de 32
     * caracteres que se imprime en el vale de una persona, y el correlativo
     * "LOT-######" de un vale de lote. Limitar la validación al token de 32
     * dejaría inalcanzable la rama de lote y devolvería 404 ante un código
     * perfectamente válido.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'regex:'.$this->formatoToken()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'El token del vale es obligatorio.',
            'token.regex' => 'El token del vale no tiene el formato esperado.',
        ];
    }

    /**
     * Un token ausente o mal formado es un error de la solicitud, no un vale
     * que no exista: confundirlos haría que un 404 del servidor se leyera en
     * la app como "el vale no está en la base".
     */
    protected function validationErrorCode(): GaratePassErrorCode
    {
        return GaratePassErrorCode::InvalidPayload;
    }

    private function formatoToken(): string
    {
        $prefijo = preg_quote((string) config('garatepass.prefijo_lote', 'LOT-'), '/');

        return '/^(?:[0-9a-f]{32}|'.$prefijo.'[0-9]{6,})$/i';
    }
}
