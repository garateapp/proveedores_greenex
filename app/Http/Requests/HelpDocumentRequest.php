<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HelpDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'archivo' => [
                'required',
                'file',
                'max:20480',
                'extensions:doc,docx,pdf,xls,xlsx,ppt,pptx',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del documento es obligatorio.',
            'archivo.required' => 'Debe seleccionar un archivo.',
            'archivo.max' => 'El archivo no debe superar los 20 MB.',
            'archivo.extensions' => 'Solo se permiten archivos Word, PDF, Excel o PowerPoint.',
        ];
    }
}
