<?php

namespace App\Http\Requests\Api;

use App\Enums\GaratePassErrorCode;

class TicketConsultaRequest extends GaratePassRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'numero_serie' => ['required', 'string', 'max:100'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_serie.required' => 'El número de serie de la tarjeta es obligatorio.',
            'numero_serie.max' => 'El número de serie excede el largo máximo.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
        ];
    }

    protected function validationErrorCode(): GaratePassErrorCode
    {
        return GaratePassErrorCode::QrInvalidFormat;
    }

    public function numeroSerie(): string
    {
        return trim((string) $this->input('numero_serie'));
    }

    public function fecha(): ?string
    {
        return $this->filled('fecha') ? (string) $this->input('fecha') : null;
    }
}
