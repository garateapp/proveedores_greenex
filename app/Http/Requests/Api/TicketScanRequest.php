<?php

namespace App\Http\Requests\Api;

use App\Enums\GaratePassErrorCode;

class TicketScanRequest extends GaratePassRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código QR es obligatorio.',
            'code.max' => 'El código QR excede el largo máximo.',
        ];
    }

    /**
     * Valida el formato del código antes de tocar la base de datos.
     *
     * Los números de serie del Portal siguen el patrón PREFIX-NNNN (PACK-5001,
     * GAR-000123). Rechazar lo que no lo cumple evita gastar una consulta con
     * un string arbitrario.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $codigo = (string) $this->input('code');

            if ($codigo !== '' && preg_match('/^[A-Z]{2,6}-[A-Z0-9]{1,12}$/', strtoupper($codigo)) !== 1) {
                $validator->errors()->add('code', 'El código no viene en el formato esperado.');
            }
        });
    }

    protected function validationErrorCode(): GaratePassErrorCode
    {
        return GaratePassErrorCode::QrInvalidFormat;
    }
}
