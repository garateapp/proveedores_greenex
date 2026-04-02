<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoFaenaRequest;
use App\Models\TipoFaena;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TipoFaenaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $estado = $request->has('activo') ? $request->boolean('activo') : null;

        $tipos = TipoFaena::query()
            ->withCount(['faenas', 'tiposDocumento'])
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

        return Inertia::render('admin/tipo-faenas/index', [
            'tipos' => $tipos->through(fn (TipoFaena $tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'codigo' => $tipo->codigo,
                'descripcion' => $tipo->descripcion,
                'activo' => $tipo->activo,
                'faenas_count' => $tipo->faenas_count,
                'tipos_documento_count' => $tipo->tipos_documento_count,
                'updated_at' => $tipo->updated_at?->format('d/m/Y'),
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
        return Inertia::render('admin/tipo-faenas/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TipoFaenaRequest $request): RedirectResponse
    {
        TipoFaena::create($request->validated());

        return redirect()->route('tipo-faenas.index')
            ->with('success', 'Tipo de faena creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoFaena $tipo_faena): Response
    {
        return Inertia::render('admin/tipo-faenas/edit', [
            'tipo' => [
                'id' => $tipo_faena->id,
                'nombre' => $tipo_faena->nombre,
                'codigo' => $tipo_faena->codigo,
                'descripcion' => $tipo_faena->descripcion,
                'activo' => $tipo_faena->activo,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TipoFaenaRequest $request, TipoFaena $tipo_faena): RedirectResponse
    {
        $tipo_faena->update($request->validated());

        return redirect()->route('tipo-faenas.index')
            ->with('success', 'Tipo de faena actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoFaena $tipo_faena): RedirectResponse
    {
        if ($tipo_faena->faenas()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un tipo con faenas asociadas.');
        }

        if ($tipo_faena->tiposDocumento()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un tipo asociado a tipos de documento.');
        }

        $tipo_faena->delete();

        return redirect()->route('tipo-faenas.index')
            ->with('success', 'Tipo de faena eliminado correctamente.');
    }
}
