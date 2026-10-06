<?php

namespace App\Http\Requests\Api;

use App\Enums\GaratePassErrorCode;

class TicketRedeemRequest extends GaratePassRequest
{
    /**
     * El canje es exclusivo de los vales de lote: los de persona no se canjean,
     * así que un token de persona debe rechazarse por formato en vez de caer
     * en un 404 de "vale inexistente" que no distingue los dos casos.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:'.$this->formatoCodigoLote()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código del vale es obligatorio.',
            'code.regex' => 'El código debe corresponder a un vale de lote.',
        ];
    }

    protected function validationErrorCode(): GaratePassErrorCode
    {
        return GaratePassErrorCode::InvalidPayload;
    }

    private function formatoCodigoLote(): string
    {
        $prefijo = preg_quote((string) config('garatepass.prefijo_lote', 'LOT-'), '/');

        return '/^'.$prefijo.'[0-9]{6,}$/i';
    }
}
