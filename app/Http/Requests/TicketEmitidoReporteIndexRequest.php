<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros del reporte visual de tickets emitidos.
 *
 * El rango es por rango de fechas; el filtro por centro de costo solo aplica
 * cuando se selecciona el contratista con detalle configurado.
 */
class TicketEmitidoReporteIndexRequest extends FormRequest
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
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'contratista' => ['nullable', 'string', 'max:255'],
            'centro_costo_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'desde.date_format' => 'La fecha inicial debe tener el formato AAAA-MM-DD.',
            'hasta.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'centro_costo_id.integer' => 'El centro de costo seleccionado no es válido.',
        ];
    }

    public function desde(): ?string
    {
        return $this->filled('desde') ? (string) $this->input('desde') : null;
    }

    public function hasta(): ?string
    {
        return $this->filled('hasta') ? (string) $this->input('hasta') : null;
    }

    public function contratista(): ?string
    {
        $contratista = trim((string) $this->input('contratista', ''));

        return $contratista !== '' ? $contratista : null;
    }

    public function centroCostoId(): ?int
    {
        return $this->filled('centro_costo_id') ? (int) $this->input('centro_costo_id') : null;
    }
}
