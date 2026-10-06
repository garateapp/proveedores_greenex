<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros de la consulta de vales de lote.
 *
 * Las fechas llegan sin validar desde la barra de filtros y terminan en
 * `whereDate`, donde un valor arbitrario no falla de forma legible en MySQL.
 */
class ValeLoteIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['vigente', 'canjeado'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }

    public function messages(): array
    {
        return [
            'desde.date_format' => 'La fecha inicial debe tener el formato AAAA-MM-DD.',
            'hasta.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'estado.in' => 'El estado debe ser vigente o canjeado.',
        ];
    }

    public function search(): string
    {
        return trim((string) $this->input('search', ''));
    }

    public function estado(): ?string
    {
        $estado = $this->input('estado');

        return is_string($estado) && $estado !== '' ? $estado : null;
    }

    public function desde(): ?string
    {
        return $this->filled('desde') ? (string) $this->input('desde') : null;
    }

    public function hasta(): ?string
    {
        return $this->filled('hasta') ? (string) $this->input('hasta') : null;
    }
}
