<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminTarjetaQrRequest extends FormRequest
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
        $tarjeta = $this->route('tarjeta');

        return [
            'admin_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Admin->value),
            ],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'desasignar_trabajador' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_user_id.required' => 'Seleccione el usuario administrador de la tarjeta.',
            'admin_user_id.exists' => 'El usuario seleccionado no existe o no tiene rol de administrador.',
            'observaciones.max' => 'Las observaciones no pueden superar los 500 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'admin_user_id' => 'administrador',
        ];
    }
}
