<?php

namespace App\Http\Requests\Api;

use App\Enums\TipoMovimientoTarjetaQr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTarjetaQrMovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'qr' => ['required', 'string', 'max:255', 'exists:tarjetas_qr,codigo_qr'],
            'tipo' => ['required', Rule::enum(TipoMovimientoTarjetaQr::class)],
            'filtro' => ['required', 'string', 'max:255'],
            'filtro_nombre' => ['required', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qr.required' => 'El código QR es obligatorio.',
            'qr.exists' => 'La tarjeta QR no existe.',
            'tipo.required' => 'El tipo de movimiento es obligatorio.',
            'tipo.enum' => 'El tipo debe ser entrada o salida.',
            'filtro.required' => 'El filtro es obligatorio.',
            'filtro_nombre.required' => 'El nombre del filtro es obligatorio.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha no tiene un formato válido.',
        ];
    }
}
