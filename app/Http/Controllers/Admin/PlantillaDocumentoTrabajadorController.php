<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlantillaDocumentoTrabajadorRequest;
use App\Models\PlantillaDocumentoTrabajador;
use App\Models\TipoDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlantillaDocumentoTrabajadorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $estado = $request->has('activo') ? $request->boolean('activo') : null;

        $plantillas = PlantillaDocumentoTrabajador::query()
            ->with('tipoDocumento:id,nombre,codigo')
            ->withCount([
                'documentosTrabajador as documentos_firmados_count' => fn ($query) => $query->where('origen', 'firma_digital'),
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('nombre', 'like', "%{$search}%")
                        ->orWhereHas('tipoDocumento', function ($tipoDocumentoQuery) use ($search) {
                            $tipoDocumentoQuery->where('nombre', 'like', "%{$search}%")
                                ->orWhere('codigo', 'like', "%{$search}%");
                        });
                });
            })
            ->when(! is_null($estado), fn ($query) => $query->where('activo', $estado))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/plantillas-documentos-trabajador/index', [
            'plantillas' => $plantillas->through(fn (PlantillaDocumentoTrabajador $plantilla) => [
                'id' => $plantilla->id,
                'nombre' => $plantilla->nombre,
                'tipo_documento_nombre' => $plantilla->tipoDocumento?->nombre,
                'tipo_documento_codigo' => $plantilla->tipoDocumento?->codigo,
                'activo' => $plantilla->activo,
                'documentos_firmados_count' => (int) $plantilla->documentos_firmados_count,
                'updated_at' => $plantilla->updated_at?->format('d/m/Y H:i'),
            ]),
            'filters' => [
                'search' => $search,
                'activo' => $estado,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('admin/plantillas-documentos-trabajador/create', [
            'tiposDocumentos' => $this->tiposDocumentosTrabajador(),
            'availableVariables' => PlantillaDocumentoTrabajadorRequest::allowedVariablesForDisplay(),
            'fontOptions' => $this->fontOptions(),
            'paperOptions' => $this->paperOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlantillaDocumentoTrabajadorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        PlantillaDocumentoTrabajador::query()->create([
            ...$validated,
            'creado_por' => $request->user()->id,
            'actualizado_por' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.plantillas-documentos-trabajador.index')
            ->with('success', 'Plantilla creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PlantillaDocumentoTrabajador $plantillaDocumentoTrabajador): Response
    {
        return Inertia::render('admin/plantillas-documentos-trabajador/edit', [
            'plantilla' => [
                'id' => $plantillaDocumentoTrabajador->id,
                'nombre' => $plantillaDocumentoTrabajador->nombre,
                'tipo_documento_id' => $plantillaDocumentoTrabajador->tipo_documento_id,
                'contenido_html' => $plantillaDocumentoTrabajador->contenido_html,
                'fuente_nombre' => $plantillaDocumentoTrabajador->fuente_nombre,
                'fuente_tamano' => $plantillaDocumentoTrabajador->fuente_tamano,
                'color_texto' => $plantillaDocumentoTrabajador->color_texto,
                'formato_papel' => $plantillaDocumentoTrabajador->formato_papel,
                'activo' => $plantillaDocumentoTrabajador->activo,
            ],
            'tiposDocumentos' => $this->tiposDocumentosTrabajador(),
            'availableVariables' => PlantillaDocumentoTrabajadorRequest::allowedVariablesForDisplay(),
            'fontOptions' => $this->fontOptions(),
            'paperOptions' => $this->paperOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        PlantillaDocumentoTrabajadorRequest $request,
        PlantillaDocumentoTrabajador $plantillaDocumentoTrabajador,
    ): RedirectResponse {
        $validated = $request->validated();

        $plantillaDocumentoTrabajador->update([
            ...$validated,
            'actualizado_por' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.plantillas-documentos-trabajador.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlantillaDocumentoTrabajador $plantillaDocumentoTrabajador): RedirectResponse
    {
        $hasSignedDocuments = $plantillaDocumentoTrabajador->documentosTrabajador()
            ->where('origen', 'firma_digital')
            ->exists();

        if ($hasSignedDocuments) {
            return back()->with('error', 'No se puede eliminar una plantilla que ya fue utilizada en firmas.');
        }

        $plantillaDocumentoTrabajador->delete();

        return redirect()
            ->route('admin.plantillas-documentos-trabajador.index')
            ->with('success', 'Plantilla eliminada correctamente.');
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function tiposDocumentosTrabajador(): array
    {
        return TipoDocumento::query()
            ->where('es_documento_trabajador', true)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo'])
            ->map(fn (TipoDocumento $tipoDocumento) => [
                'value' => $tipoDocumento->id,
                'label' => $tipoDocumento->nombre.' ('.$tipoDocumento->codigo.')',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function fontOptions(): array
    {
        return collect(PlantillaDocumentoTrabajador::FUENTES_DISPONIBLES)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function paperOptions(): array
    {
        return [
            [
                'value' => PlantillaDocumentoTrabajador::FORMATO_PAPEL_A4,
                'label' => 'A4',
            ],
            [
                'value' => PlantillaDocumentoTrabajador::FORMATO_PAPEL_LETTER,
                'label' => 'Letter',
            ],
        ];
    }
}
