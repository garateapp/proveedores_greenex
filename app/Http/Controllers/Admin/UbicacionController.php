<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UbicacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Ubicacion::with(['padre', 'hijosActivos'])
            ->withCount('hijos')
            ->orderBy('orden');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%'.$request->search.'%')
                    ->orWhere('codigo', 'like', '%'.$request->search.'%')
                    ->orWhere('descripcion', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activa')) {
            $query->where('activa', $request->boolean('activa'));
        }

        $ubicaciones = $query->get();

        $ubicacionesPrincipales = Ubicacion::principales()->get();

        return Inertia::render('admin/ubicaciones/index', [
            'ubicaciones' => $ubicaciones,
            'ubicacionesPrincipales' => $ubicacionesPrincipales,
            'filters' => $request->only(['search', 'tipo', 'activa']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'padre_id' => 'nullable|exists:ubicaciones,id',
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|unique:ubicaciones,codigo',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:principal,secundaria',
            'orden' => 'nullable|integer|min:0',
        ]);

        $validated['orden'] = $validated['orden'] ?? 0;
        $validated['activa'] = true;

        // Si tiene padre, automáticamente es secundaria
        if ($validated['padre_id']) {
            $validated['tipo'] = 'secundaria';
        }

        Ubicacion::create($validated);

        return redirect()->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación creada exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ubicacion $ubicacion): RedirectResponse
    {
        $validated = $request->validate([
            'padre_id' => 'nullable|exists:ubicaciones,id|different:id',
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|unique:ubicaciones,codigo,'.$ubicacion->id,
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:principal,secundaria',
            'orden' => 'nullable|integer|min:0',
            'activa' => 'boolean',
        ]);

        // No permitir que una ubicación sea hija de sí misma
        if ($validated['padre_id'] == $ubicacion->id) {
            $validated['padre_id'] = null;
        }

        // Si tiene padre, automáticamente es secundaria
        if ($validated['padre_id']) {
            $validated['tipo'] = 'secundaria';
        }

        $ubicacion->update($validated);

        return redirect()->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ubicacion $ubicacion): RedirectResponse
    {
        // Verificar si tiene hijos
        if ($ubicacion->hijos()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar una ubicación que tiene sub-ubicaciones.');
        }

        $ubicacion->delete();

        return redirect()->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación eliminada exitosamente.');
    }

    /**
     * Toggle activa status
     */
    public function toggleActiva(Ubicacion $ubicacion): RedirectResponse
    {
        $ubicacion->update(['activa' => ! $ubicacion->activa]);

        return redirect()->back()
            ->with('success', 'Estado de ubicación actualizado.');
    }
}
