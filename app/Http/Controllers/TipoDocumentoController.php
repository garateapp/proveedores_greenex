<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoDocumentoRequest;
use App\Models\TipoDocumento;
use App\Models\TipoFaena;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TipoDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $estado = $request->has('activo') ? $request->boolean('activo') : null;

        $tipos = TipoDocumento::query()
            ->with('tiposFaena:id,nombre')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%");
                });
            })
            ->when(! is_null($estado), fn ($query) => $query->where('activo', $estado))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/tipo-documentos/index', [
            'tipos' => $tipos->through(fn (TipoDocumento $tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'codigo' => $tipo->codigo,
                'periodicidad' => $tipo->periodicidad,
                'permite_multiples_en_mes' => $tipo->permite_multiples_en_mes,
                'es_obligatorio' => $tipo->es_obligatorio,
                'es_documento_trabajador' => $tipo->es_documento_trabajador,
                'tipos_faena' => $tipo->tiposFaena
                    ->map(fn (TipoFaena $tipoFaena) => [
                        'id' => $tipoFaena->id,
                        'nombre' => $tipoFaena->nombre,
                    ])
                    ->values()
                    ->all(),
                'activo' => $tipo->activo,
                'updated_at' => $tipo->updated_at?->format('d/m/Y'),
            ]),
            'filters' => [
                'search' => $search,
                'activo' => $estado,
            ],
            'periodicidades' => $this->periodicidades(),
            'extensiones' => $this->extensiones(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('admin/tipo-documentos/create', [
            'periodicidades' => $this->periodicidades(),
            'extensiones' => $this->extensiones(),
            'tiposFaena' => TipoFaena::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn (TipoFaena $tipo) => [
                    'value' => $tipo->id,
                    'label' => $tipo->nombre,
                ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TipoDocumentoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tipoFaenaIds = $validated['tipo_faena_ids'];
        unset($validated['tipo_faena_ids']);

        $tipoDocumento = TipoDocumento::create($validated);
        $tipoDocumento->tiposFaena()->sync($tipoFaenaIds);

        return redirect()->route('tipo-documentos.index')
            ->with('success', 'Tipo de documento creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoDocumento $tipo_documento): Response
    {
        return Inertia::render('admin/tipo-documentos/edit', [
            'tipo' => [
                'id' => $tipo_documento->id,
                'nombre' => $tipo_documento->nombre,
                'codigo' => $tipo_documento->codigo,
                'descripcion' => $tipo_documento->descripcion,
                'periodicidad' => $tipo_documento->periodicidad,
                'permite_multiples_en_mes' => $tipo_documento->permite_multiples_en_mes,
                'es_obligatorio' => $tipo_documento->es_obligatorio,
                'es_documento_trabajador' => $tipo_documento->es_documento_trabajador,
                'dias_vencimiento' => $tipo_documento->dias_vencimiento,
                'formatos_permitidos' => $tipo_documento->formatos_permitidos,
                'tamano_maximo_kb' => $tipo_documento->tamano_maximo_kb,
                'requiere_validacion' => $tipo_documento->requiere_validacion,
                'instrucciones' => $tipo_documento->instrucciones,
                'activo' => $tipo_documento->activo,
                'tipo_faena_ids' => $tipo_documento->tiposFaena()
                    ->pluck('tipo_faenas.id')
                    ->all(),
            ],
            'periodicidades' => $this->periodicidades(),
            'extensiones' => $this->extensiones(),
            'tiposFaena' => TipoFaena::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn (TipoFaena $tipo) => [
                    'value' => $tipo->id,
                    'label' => $tipo->nombre,
                ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TipoDocumentoRequest $request, TipoDocumento $tipo_documento): RedirectResponse
    {
        $validated = $request->validated();
        $tipoFaenaIds = $validated['tipo_faena_ids'];
        unset($validated['tipo_faena_ids']);

        $tipo_documento->update($validated);
        $tipo_documento->tiposFaena()->sync($tipoFaenaIds);

        return redirect()->route('tipo-documentos.index')
            ->with('success', 'Tipo de documento actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoDocumento $tipo_documento): RedirectResponse
    {
        if ($tipo_documento->documentos()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un tipo con documentos asociados.');
        }

        $tipo_documento->delete();

        return redirect()->route('tipo-documentos.index')
            ->with('success', 'Tipo de documento eliminado correctamente.');
    }

    private function periodicidades(): array
    {
        return [
            ['value' => 'mensual', 'label' => 'Mensual'],
            ['value' => 'trimestral', 'label' => 'Trimestral'],
            ['value' => 'semestral', 'label' => 'Semestral'],
            ['value' => 'anual', 'label' => 'Anual'],
            ['value' => 'unico', 'label' => 'Único'],
        ];
    }

    private function extensiones(): array
    {
        return [
            ['value' => 'pdf', 'label' => 'PDF'],
            ['value' => 'csv', 'label' => 'CSV'],
            ['value' => 'txt', 'label' => 'TXT'],
            ['value' => 'xlsx', 'label' => 'XLSX'],
            ['value' => 'docx', 'label' => 'DOCX'],
        ];
    }
}
