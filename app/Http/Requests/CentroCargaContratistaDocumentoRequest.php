<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CentroCargaContratistaDocumentoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canManageWorkers() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contratista_id' => ['required', 'integer', 'exists:contratistas,id'],
            'tipo_documento_id' => [
                'required',
                'integer',
                'exists:tipo_documentos,id',
            ],
            'periodo_ano' => ['required', 'integer', 'min:2020', 'max:'.date('Y')],
            'periodo_mes' => ['nullable', 'integer', 'min:1', 'max:12'],
            'archivo' => ['required', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png'],
            'expiry_date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.mimetypes' => 'El archivo debe ser PDF, JPG o PNG.',
            'expiry_date.date_format' => 'La fecha de vencimiento debe tener formato AAAA-MM-DD.',
        ];
    }
}
