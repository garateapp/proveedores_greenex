<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UbicacionImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'extensions:csv',
                'mimetypes:text/plain,text/csv,application/csv,text/comma-separated-values,application/vnd.ms-excel',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Debe seleccionar un archivo CSV.',
            'file.file' => 'El archivo seleccionado no es válido.',
            'file.extensions' => 'El archivo debe tener extensión .csv.',
            'file.mimetypes' => 'El archivo debe ser un CSV válido.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
