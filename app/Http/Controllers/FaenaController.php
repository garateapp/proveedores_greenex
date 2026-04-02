<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaenaRequest;
use App\Models\Contratista;
use App\Models\Faena;
use App\Models\TipoFaena;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaenaController extends Controller
{
    /**
     * Display a listing of faenas.
     */
    public function index(Request $request): Response
    {
        $query = Faena::with(['tipoFaena:id,nombre'])
            ->withCount('trabajadores')
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        // Estado filter
        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        $faenas = $query->paginate(15)->withQueryString();

        return Inertia::render('faenas/index', [
            'faenas' => $faenas,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    /**
     * Show the form for creating a new faena.
     */
    public function create(Request $request): Response
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        return Inertia::render('faenas/create', [
            'tiposFaena' => TipoFaena::active()
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn (TipoFaena $tipo) => [
                    'value' => $tipo->id,
                    'label' => $tipo->nombre,
                ]),
        ]);
    }

    /**
     * Store a newly created faena.
     */
    public function store(FaenaRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validated();
        unset($validated['estado']);

        Faena::create([
            ...$validated,
            'estado' => 'activa',
        ]);

        return redirect()->route('faenas.index')->with('success', 'Faena creada exitosamente.');
    }

    /**
     * Display the specified faena.
     */
    public function show(Request $request, Faena $faena): Response
    {
        $user = $request->user();
        $this->authorizeFaenaAccess($user, $faena);

        $faena->load(['trabajadores' => function ($query) {
            $query->withPivot('fecha_asignacion', 'fecha_desasignacion')
                ->wherePivotNull('fecha_desasignacion')
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->orderBy('apellido');
        }, 'tipoFaena', 'contratistas:id,razon_social,nombre_fantasia']);

        $assignedTrabajadorIds = $faena->trabajadores->pluck('id');

        $trabajadoresDisponiblesQuery = Trabajador::query()
            ->active()
            ->whereNotIn('id', $assignedTrabajadorIds)
            ->orderBy('nombre')
            ->orderBy('apellido');

        if (! $user->isAdmin() && $user->contratista_id !== null) {
            $trabajadoresDisponiblesQuery->where('contratista_id', $user->contratista_id);
        }

        $trabajadoresDisponibles = $trabajadoresDisponiblesQuery
            ->get(['id', 'documento', 'nombre', 'apellido'])
            ->map(fn (Trabajador $trabajador) => [
                'id' => $trabajador->id,
                'documento' => $trabajador->documento,
                'nombre' => $trabajador->nombre,
                'apellido' => $trabajador->apellido,
            ]);

        $contratistasDisponibles = collect();
        if ($user->isAdmin()) {
            $participatingContratistaIds = $faena->contratistas->pluck('id');
            $contratistasDisponibles = Contratista::query()
                ->where('estado', 'activo')
                ->whereNotIn('id', $participatingContratistaIds)
                ->orderBy('razon_social')
                ->get(['id', 'razon_social', 'nombre_fantasia'])
                ->map(fn (Contratista $contratista) => [
                    'id' => $contratista->id,
                    'razon_social' => $contratista->razon_social,
                    'nombre_fantasia' => $contratista->nombre_fantasia,
                    'nombre_mostrado' => $contratista->nombre_fantasia ?: $contratista->razon_social,
                ]);
        }

        return Inertia::render('faenas/show', [
            'faena' => $faena,
            'trabajadoresDisponibles' => $trabajadoresDisponibles,
            'contratistasDisponibles' => $contratistasDisponibles,
        ]);
    }

    /**
     * Show the form for editing the specified faena.
     */
    public function edit(Request $request, Faena $faena): Response
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        return Inertia::render('faenas/edit', [
            'faena' => $faena,
            'tiposFaena' => TipoFaena::active()
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn (TipoFaena $tipo) => [
                    'value' => $tipo->id,
                    'label' => $tipo->nombre,
                ]),
        ]);
    }

    /**
     * Update the specified faena.
     */
    public function update(FaenaRequest $request, Faena $faena)
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $faena->update($request->validated());

        return redirect()->route('faenas.index')->with('success', 'Faena actualizada exitosamente.');
    }

    /**
     * Remove the specified faena.
     */
    public function destroy(Faena $faena)
    {
        $faena->delete();

        return redirect()->route('faenas.index')->with('success', 'Faena eliminada exitosamente.');
    }

    /**
     * Assign trabajador to faena.
     */
    public function assignTrabajador(Request $request, Faena $faena): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canManageWorkers()) {
            abort(403);
        }

        $validated = $request->validate([
            'trabajador_id' => ['required', 'exists:trabajadores,id'],
        ]);

        $trabajador = Trabajador::query()->findOrFail($validated['trabajador_id']);

        if (! $user->isAdmin() && $user->contratista_id !== $trabajador->contratista_id) {
            abort(403);
        }

        if (! $user->isAdmin() && ! $this->faenaHasContratistaParticipant($faena, (int) $trabajador->contratista_id)) {
            abort(403);
        }

        if ($user->isAdmin()) {
            $faena->contratistas()->syncWithoutDetaching([(int) $trabajador->contratista_id]);
        }

        if (
            $faena->trabajadores()
                ->where('trabajador_id', $trabajador->id)
                ->wherePivotNull('fecha_desasignacion')
                ->exists()
        ) {
            return back()->withErrors(['trabajador_id' => 'El trabajador ya está asignado a esta faena.']);
        }

        if (
            $faena->trabajadores()
                ->where('trabajador_id', $trabajador->id)
                ->wherePivotNotNull('fecha_desasignacion')
                ->exists()
        ) {
            $faena->trabajadores()->updateExistingPivot($trabajador->id, [
                'fecha_asignacion' => now(),
                'fecha_desasignacion' => null,
            ]);
        } else {
            $faena->trabajadores()->attach($trabajador->id, [
                'fecha_asignacion' => now(),
                'fecha_desasignacion' => null,
            ]);
        }

        return back()->with('success', 'Trabajador asignado exitosamente.');
    }

    /**
     * Unassign trabajador from faena.
     */
    public function unassignTrabajador(Request $request, Faena $faena, string $trabajadorId): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canManageWorkers()) {
            abort(403);
        }

        $trabajador = Trabajador::query()->findOrFail($trabajadorId);
        if (! $user->isAdmin() && $user->contratista_id !== $trabajador->contratista_id) {
            abort(403);
        }

        if (! $user->isAdmin() && ! $this->faenaHasContratistaParticipant($faena, (int) $trabajador->contratista_id)) {
            abort(403);
        }

        $faena->trabajadores()->updateExistingPivot($trabajadorId, [
            'fecha_desasignacion' => now(),
        ]);

        return back()->with('success', 'Trabajador desasignado exitosamente.');
    }

    /**
     * Add a contratista participant to a faena.
     */
    public function storeContratista(Request $request, Faena $faena): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'contratista_id' => ['required', 'exists:contratistas,id'],
        ]);

        $faena->contratistas()->syncWithoutDetaching([(int) $validated['contratista_id']]);

        return back()->with('success', 'Contratista agregado a la faena exitosamente.');
    }

    /**
     * Remove a contratista participant from a faena.
     */
    public function destroyContratista(Request $request, Faena $faena, Contratista $contratista): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $hasActiveWorkers = $faena->trabajadores()
            ->wherePivotNull('fecha_desasignacion')
            ->where('contratista_id', $contratista->id)
            ->exists();

        if ($hasActiveWorkers) {
            return back()->withErrors([
                'contratista_id' => 'No se puede quitar el contratista porque tiene trabajadores activos en esta faena.',
            ]);
        }

        $faena->contratistas()->detach($contratista->id);

        return back()->with('success', 'Contratista removido de la faena exitosamente.');
    }

    /**
     * Ensure that the current user can operate on this faena.
     */
    private function authorizeFaenaAccess(User $user, Faena $faena): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->contratista_id === null || ! $this->faenaHasContratistaParticipant($faena, $user->contratista_id)) {
            abort(403);
        }
    }

    /**
     * Check whether a contratista participates in the given faena.
     */
    private function faenaHasContratistaParticipant(Faena $faena, int $contratistaId): bool
    {
        return $faena->contratistas()
            ->where('contratistas.id', $contratistaId)
            ->exists();
    }
}
