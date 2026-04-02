<?php

namespace App\Http\Controllers;

use App\Models\Contratista;
use App\Models\Faena;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrabajadorController extends Controller
{
    /**
     * Display a listing of trabajadores.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $requiredTipoIds = TipoDocumento::query()
            ->where('es_documento_trabajador', true)
            ->where('es_obligatorio', true)
            ->where('activo', true)
            ->pluck('id');
        $requiredCount = $requiredTipoIds->count();

        $query = Trabajador::with('contratista:id,razon_social,nombre_fantasia')
            ->withCount([
                'documentosTrabajador as documentos_obligatorios_count' => function ($query) use ($requiredTipoIds) {
                    $query->whereIn('tipo_documento_id', $requiredTipoIds);
                },
            ])
            ->orderBy('created_at', 'desc');

        // Filter by contratista if not admin
        if (! $user->isAdmin()) {
            $query->forContratista($user->contratista_id);
        }

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%")
                    ->orWhere('documento', 'like', "%{$search}%");
            });
        }

        // Estado filter
        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        $trabajadores = $query->paginate(15)->withQueryString()->through(
            fn (Trabajador $trabajador) => [
                'id' => $trabajador->id,
                'documento' => $trabajador->documento,
                'nombre' => $trabajador->nombre,
                'apellido' => $trabajador->apellido,
                'email' => $trabajador->email,
                'telefono' => $trabajador->telefono,
                'estado' => $trabajador->estado,
                'fecha_ingreso' => $trabajador->fecha_ingreso,
                'contratista' => [
                    'id' => $trabajador->contratista?->id,
                    'razon_social' => $trabajador->contratista?->razon_social,
                    'nombre_fantasia' => $trabajador->contratista?->nombre_fantasia,
                ],
                'created_at' => $trabajador->created_at,
                'documentos_obligatorios_total' => $requiredCount,
                'documentos_obligatorios_cargados' => $requiredCount === 0
                    ? 0
                    : min((int) $trabajador->documentos_obligatorios_count, $requiredCount),
                'documentos_obligatorios_pendientes' => $requiredCount === 0
                    ? 0
                    : max(
                        $requiredCount - min((int) $trabajador->documentos_obligatorios_count, $requiredCount),
                        0,
                    ),
                'documentos_obligatorios_porcentaje' => $requiredCount === 0
                    ? 100
                    : (int) round(
                        (min((int) $trabajador->documentos_obligatorios_count, $requiredCount) / $requiredCount) * 100,
                    ),
                'documentos_obligatorios_completos' => $requiredCount === 0
                    ? true
                    : $trabajador->documentos_obligatorios_count >= $requiredCount,
            ],
        );
        $contratistas = [];

        if ($user->isAdmin()) {
            $contratistas = Contratista::query()
                ->where('estado', 'activo')
                ->orderBy('razon_social')
                ->get(['id', 'razon_social'])
                ->map(fn ($c) => [
                    'value' => $c->id,
                    'label' => $c->razon_social,
                ]);
        }

        return Inertia::render('trabajadores/index', [
            'trabajadores' => $trabajadores,
            'filters' => $request->only(['search', 'estado']),
            'contratistas' => $contratistas,
        ]);
    }

    /**
     * Show the form for creating a new trabajador.
     */
    public function create(): Response
    {
        $contratistas = [];

        if (request()->user()->isAdmin()) {
            $contratistas = \App\Models\Contratista::query()
                ->where('estado', 'activo')
                ->orderBy('razon_social')
                ->get(['id', 'razon_social'])
                ->map(fn ($c) => [
                    'value' => $c->id,
                    'label' => $c->razon_social,
                ]);
        }

        return Inertia::render('trabajadores/create', [
            'contratistas' => $contratistas,
        ]);
    }

    /**
     * Store a newly created trabajador.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'documento' => ['required', 'string', 'regex:/^[0-9]{7,8}-[0-9kK]$/', 'unique:trabajadores,documento'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'fecha_ingreso' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
        ]);

        // Validate RUT
        if (! Trabajador::validateRut($validated['documento'])) {
            return back()->withErrors(['documento' => 'El RUT ingresado no es válido.']);
        }

        // Extract ID from documento
        $id = Trabajador::extractIdFromDocumento($validated['documento']);

        // Check if ID already exists
        if (Trabajador::find($id)) {
            return back()->withErrors(['documento' => 'Ya existe un trabajador con este RUT.']);
        }

        Trabajador::create([
            'id' => $id,
            'documento' => $validated['documento'],
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'contratista_id' => $user->isAdmin() ? $request->input('contratista_id') : $user->contratista_id,
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'fecha_ingreso' => $validated['fecha_ingreso'] ?? now(),
            'observaciones' => $validated['observaciones'],
            'estado' => 'activo',
        ]);

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador registrado exitosamente.');
    }

    /**
     * Display the specified trabajador.
     */
    public function show(string $id): Response
    {
        $trabajador = Trabajador::with(['contratista', 'faenas'])->findOrFail($id);

        // Check access
        $user = request()->user();
        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        return Inertia::render('trabajadores/show', [
            'trabajador' => $trabajador,
        ]);
    }

    /**
     * Show the form for editing the specified trabajador.
     */
    public function edit(string $id): Response
    {
        $trabajador = Trabajador::with('documentosTrabajador.tipoDocumento')->findOrFail($id);

        $user = request()->user();
        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        $tipoFaenaIds = $trabajador->faenas()
            ->wherePivotNull('fecha_desasignacion')
            ->pluck('faenas.tipo_faena_id')
            ->filter()
            ->unique();

        $tiposDocumentosQuery = \App\Models\TipoDocumento::active()
            ->where('es_documento_trabajador', true)
            ->orderBy('nombre');

        if ($tipoFaenaIds->isEmpty()) {
            $tiposDocumentosQuery->whereRaw('1 = 0');
        } else {
            $tiposDocumentosQuery->whereHas('tiposFaena', function ($query) use ($tipoFaenaIds) {
                $query->whereIn('tipo_faenas.id', $tipoFaenaIds);
            });
        }

        $tiposDocumentos = $tiposDocumentosQuery
            ->get(['id', 'nombre', 'codigo', 'formatos_permitidos', 'tamano_maximo_kb']);

        $contratistas = [];
        if ($user->isAdmin()) {
            $contratistas = Contratista::query()
                ->where('estado', 'activo')
                ->orderBy('razon_social')
                ->get(['id', 'razon_social'])
                ->map(fn (Contratista $contratista) => [
                    'value' => $contratista->id,
                    'label' => $contratista->razon_social,
                ]);
        }

        $faenasDisponibles = $this->resolveAvailableFaenasForUser($user, $trabajador->contratista_id)
            ->map(fn (Faena $faena) => [
                'id' => $faena->id,
                'nombre' => $faena->nombre,
                'codigo' => $faena->codigo,
                'tipo_faena' => $faena->tipoFaena?->nombre,
            ])
            ->values();

        $faenaIdsAsignadas = $trabajador->faenas()
            ->wherePivotNull('fecha_desasignacion')
            ->pluck('faenas.id')
            ->map(fn ($faenaId) => (int) $faenaId)
            ->values()
            ->all();

        return Inertia::render('trabajadores/edit', [
            'trabajador' => $trabajador,
            'tiposDocumentos' => $tiposDocumentos,
            'sinFaenaActiva' => $tipoFaenaIds->isEmpty(),
            'contratistas' => $contratistas,
            'faenasDisponibles' => $faenasDisponibles,
            'faenaIdsAsignadas' => $faenaIdsAsignadas,
            'documentosTrabajador' => $trabajador->documentosTrabajador->map(fn ($documento) => [
                'id' => $documento->id,
                'tipo_documento_id' => $documento->tipo_documento_id,
                'tipo_documento_nombre' => $documento->tipoDocumento?->nombre,
                'archivo_nombre_original' => $documento->archivo_nombre_original,
                'created_at' => $documento->created_at?->format('d/m/Y'),
            ]),
        ]);
    }

    /**
     * Update the specified trabajador.
     */
    public function update(Request $request, string $id)
    {
        $trabajador = Trabajador::findOrFail($id);

        $user = $request->user();
        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'estado' => ['required', 'in:activo,inactivo'],
            'fecha_ingreso' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
            'contratista_id' => ['nullable', 'exists:contratistas,id'],
            'faena_ids' => ['nullable', 'array'],
            'faena_ids.*' => ['integer', 'exists:faenas,id'],
        ]);

        $targetContratistaId = $trabajador->contratista_id;
        if ($user->isAdmin()) {
            $targetContratistaId = (int) ($validated['contratista_id'] ?? $trabajador->contratista_id);
        }

        if (! $user->isAdmin()) {
            $targetContratistaId = (int) $user->contratista_id;
        }

        $trabajador->update([
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'estado' => $validated['estado'],
            'fecha_ingreso' => $validated['fecha_ingreso'],
            'observaciones' => $validated['observaciones'],
            'contratista_id' => $targetContratistaId,
        ]);

        $faenaIds = collect($validated['faena_ids'] ?? [])
            ->map(fn ($faenaId) => (int) $faenaId)
            ->values();

        $this->syncTrabajadorFaenas($user, $trabajador, $targetContratistaId, $faenaIds->all());

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador actualizado exitosamente.');
    }

    /**
     * Remove the specified trabajador.
     */
    public function destroy(string $id)
    {
        $trabajador = Trabajador::findOrFail($id);

        // Check access
        $user = request()->user();
        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        $trabajador->delete();

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador eliminado exitosamente.');
    }

    /**
     * Toggle trabajador estado (activo/inactivo) rápidamente.
     */
    public function toggleEstado(Request $request, string $id)
    {
        $trabajador = Trabajador::findOrFail($id);
        $user = $request->user();

        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        $validated = $request->validate([
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $trabajador->update([
            'estado' => $validated['estado'],
        ]);

        return back()->with('success', 'Estado actualizado');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Faena>
     */
    private function resolveAvailableFaenasForUser(User $user, int $contratistaId)
    {
        $query = Faena::query()
            ->active()
            ->with('tipoFaena:id,nombre')
            ->orderBy('nombre');

        if (! $user->isAdmin()) {
            $query->whereHas('contratistas', function ($faenaQuery) use ($contratistaId) {
                $faenaQuery->where('contratistas.id', $contratistaId);
            });
        }

        return $query->get(['faenas.id', 'faenas.nombre', 'faenas.codigo', 'faenas.tipo_faena_id']);
    }

    /**
     * Sync active faena assignments for a trabajador.
     *
     * @param  array<int, int>  $selectedFaenaIds
     */
    private function syncTrabajadorFaenas(User $user, Trabajador $trabajador, int $contratistaId, array $selectedFaenaIds): void
    {
        $selectedFaenas = Faena::query()
            ->active()
            ->whereIn('id', $selectedFaenaIds)
            ->get();

        if ($selectedFaenas->count() !== count($selectedFaenaIds)) {
            abort(403);
        }

        if (! $user->isAdmin()) {
            $isAllowed = Faena::query()
                ->active()
                ->whereIn('id', $selectedFaenaIds)
                ->whereHas('contratistas', function ($query) use ($contratistaId) {
                    $query->where('contratistas.id', $contratistaId);
                })
                ->count() === count($selectedFaenaIds);

            if (! $isAllowed) {
                abort(403);
            }
        }

        if ($user->isAdmin() && ! empty($selectedFaenaIds)) {
            foreach ($selectedFaenaIds as $faenaId) {
                Faena::query()->findOrFail($faenaId)
                    ->contratistas()
                    ->syncWithoutDetaching([$contratistaId]);
            }
        }

        $activeFaenaIds = $trabajador->faenas()
            ->wherePivotNull('fecha_desasignacion')
            ->pluck('faenas.id')
            ->map(fn ($faenaId) => (int) $faenaId)
            ->all();

        $faenaIdsToUnassign = array_diff($activeFaenaIds, $selectedFaenaIds);
        foreach ($faenaIdsToUnassign as $faenaIdToUnassign) {
            $trabajador->faenas()->updateExistingPivot($faenaIdToUnassign, [
                'fecha_desasignacion' => now(),
            ]);
        }

        foreach ($selectedFaenaIds as $selectedFaenaId) {
            $alreadyAssigned = $trabajador->faenas()
                ->where('faena_id', $selectedFaenaId)
                ->wherePivotNull('fecha_desasignacion')
                ->exists();

            if ($alreadyAssigned) {
                continue;
            }

            $wasAssignedBefore = $trabajador->faenas()
                ->where('faena_id', $selectedFaenaId)
                ->wherePivotNotNull('fecha_desasignacion')
                ->exists();

            if ($wasAssignedBefore) {
                $trabajador->faenas()->updateExistingPivot($selectedFaenaId, [
                    'fecha_asignacion' => now(),
                    'fecha_desasignacion' => null,
                ]);

                continue;
            }

            $trabajador->faenas()->attach($selectedFaenaId, [
                'fecha_asignacion' => now(),
                'fecha_desasignacion' => null,
            ]);
        }
    }
}
