<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CentroCargaDocumentoRequest extends FormRequest
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
            'trabajador_id' => ['required', 'string', 'exists:trabajadores,id'],
            'tipo_documento_id' => [
                'required',
                'integer',
                'exists:tipo_documentos,id',
            ],
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
