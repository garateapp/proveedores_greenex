<?php

namespace App\Http\Requests;

use App\Models\PlantillaDocumentoTrabajador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlantillaDocumentoTrabajadorRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255'],
            'tipo_documento_id' => [
                'required',
                'integer',
                Rule::exists('tipo_documentos', 'id')->where(
                    fn ($query) => $query->where('es_documento_trabajador', true),
                ),
            ],
            'contenido_html' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value)) {
                        $fail('El contenido de la plantilla no es válido.');

                        return;
                    }

                    preg_match_all('/{{\s*([a-z_]+)\s*}}/i', $value, $matches);
                    $variablesEncontradas = collect($matches[1] ?? [])
                        ->map(fn (string $variable) => strtolower($variable))
                        ->unique()
                        ->values();

                    $variablesInvalidas = $variablesEncontradas
                        ->reject(fn (string $variable) => in_array($variable, self::allowedVariables(), true))
                        ->values();

                    if ($variablesInvalidas->isNotEmpty()) {
                        $fail(
                            'Variables no permitidas: '.$variablesInvalidas->implode(', ').'. '
                            .'Variables disponibles: '.implode(', ', self::allowedVariablesForDisplay()).'.',
                        );
                    }
                },
            ],
            'fuente_nombre' => [
                'required',
                'string',
                Rule::in(array_keys(PlantillaDocumentoTrabajador::FUENTES_DISPONIBLES)),
            ],
            'fuente_tamano' => ['required', 'integer', 'min:9', 'max:18'],
            'color_texto' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'formato_papel' => [
                'required',
                'string',
                Rule::in(PlantillaDocumentoTrabajador::FORMATOS_PAPEL_DISPONIBLES),
            ],
            'activo' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fuente_nombre' => $this->input('fuente_nombre', 'dejavu_sans'),
            'fuente_tamano' => $this->input('fuente_tamano', 12),
            'color_texto' => $this->input('color_texto', '#111827'),
            'formato_papel' => $this->input('formato_papel', PlantillaDocumentoTrabajador::FORMATO_PAPEL_LETTER),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function allowedVariables(): array
    {
        return [
            'fecha',
            'trabajador_nombre',
            'trabajador_rut',
            'contratista_nombre',
            'contratista_rut',
            'fecha_firma',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedVariablesForDisplay(): array
    {
        return array_map(
            static fn (string $variable): string => '{{'.$variable.'}}',
            self::allowedVariables(),
        );
    }
}
