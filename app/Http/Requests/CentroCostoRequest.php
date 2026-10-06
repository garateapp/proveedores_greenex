<?php

namespace App\Http\Requests;

use App\Models\CentroCosto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CentroCostoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $centroCosto = $this->route('centroCosto');

        return [
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('centros_costo', 'codigo')->ignore($centroCosto?->id),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'activo' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del centro de costo es obligatorio.',
            'codigo.unique' => 'Ya existe un centro de costo con este código.',
            'codigo.max' => 'El código no puede superar los 50 caracteres.',
            'nombre.required' => 'El nombre del centro de costo es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'activo.required' => 'Indique si el centro de costo está activo.',
        ];
    }

    public function centroCosto(): CentroCosto
    {
        return $this->route('centroCosto');
    }
}
