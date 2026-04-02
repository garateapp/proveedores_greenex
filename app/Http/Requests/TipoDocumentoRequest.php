<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TipoDocumentoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canManageContratistas() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('tipo_documento')?->id;

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:50', 'unique:tipo_documentos,codigo,'.($id ?? 'NULL').',id'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'periodicidad' => ['required', 'string', 'in:mensual,trimestral,semestral,anual,unico'],
            'permite_multiples_en_mes' => ['required', 'boolean'],
            'es_obligatorio' => ['required', 'boolean'],
            'es_documento_trabajador' => ['required', 'boolean'],
            'dias_vencimiento' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'formatos_permitidos' => ['required', 'array', 'min:1'],
            'formatos_permitidos.*' => ['string', 'max:10'],
            'tipo_faena_ids' => ['required', 'array', 'min:1'],
            'tipo_faena_ids.*' => ['integer', 'exists:tipo_faenas,id'],
            'tamano_maximo_kb' => ['required', 'integer', 'min:1', 'max:51200'],
            'requiere_validacion' => ['required', 'boolean'],
            'instrucciones' => ['nullable', 'string', 'max:2000'],
            'activo' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permite_multiples_en_mes' => $this->boolean('permite_multiples_en_mes'),
        ]);
    }
}
