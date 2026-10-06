<?php

namespace App\Http\Requests\Api;

use App\Enums\GaratePassErrorCode;
use App\Exceptions\GaratePassApiException;

class TicketBatchRequest extends GaratePassRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'adminCode' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.(int) config('garatepass.lote_maximo', 500)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'adminCode.required' => 'El código QR del administrador es obligatorio.',
            'adminCode.max' => 'El código QR excede el largo máximo.',
            'quantity.required' => 'La cantidad de vales es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad debe ser al menos 1.',
            'quantity.max' => 'La cantidad supera el máximo permitido por lote.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $codigo = (string) $this->input('adminCode');

            if ($codigo !== '' && preg_match('/^[A-Z]{2,6}-[A-Z0-9]{1,12}$/', strtoupper($codigo)) !== 1) {
                $validator->errors()->add('adminCode', 'El código no viene en el formato esperado.');
            }
        });
    }

    protected function validationErrorCode(): GaratePassErrorCode
    {
        return GaratePassErrorCode::InvalidPayload;
    }

    /**
     * El lote es reintentable: sin clave, un reintento del cliente emitiría un
     * segundo lote. Se exige antes de validar el cuerpo.
     */
    public function validateResolved(): void
    {
        if ($this->idempotencyKey() === '') {
            throw new GaratePassApiException(GaratePassErrorCode::IdempotencyKeyMissing);
        }

        parent::validateResolved();
    }

    public function idempotencyKey(): string
    {
        return trim((string) $this->header('Idempotency-Key', ''));
    }
}
