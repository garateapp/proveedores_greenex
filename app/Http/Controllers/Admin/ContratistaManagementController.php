<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contratista;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContratistaManagementController extends Controller
{
    /**
     * Display a listing of contratistas.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $estadoFilter = $request->input('estado');

        $contratistas = Contratista::query()
            ->withCount('users')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('rut', 'like', "%{$search}%")
                        ->orWhere('razon_social', 'like', "%{$search}%")
                        ->orWhere('nombre_fantasia', 'like', "%{$search}%");
                });
            })
            ->when($estadoFilter, function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->orderBy('razon_social')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/contratistas/index', [
            'contratistas' => $contratistas->through(fn ($contratista) => [
                'id' => $contratista->id,
                'rut' => $contratista->rut,
                'razon_social' => $contratista->razon_social,
                'nombre_fantasia' => $contratista->nombre_fantasia,
                'email' => $contratista->email,
                'telefono' => $contratista->telefono,
                'estado' => $contratista->estado,
                'users_count' => $contratista->users_count,
                'created_at' => $contratista->created_at->format('d/m/Y'),
            ]),
            'filters' => [
                'search' => $search,
                'estado' => $estadoFilter,
            ],
            'estados' => [
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'bloqueado', 'label' => 'Bloqueado'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new contratista.
     */
    public function create(): Response
    {
        return Inertia::render('admin/contratistas/create');
    }

    /**
     * Store a newly created contratista.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rut' => ['required', 'string', 'max:12', 'unique:contratistas,rut', 'regex:/^\d{7,8}-[\dkK]$/'],
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_fantasia' => ['nullable', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'comuna' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        Contratista::create($validated);

        return redirect()->route('admin.contratistas.index')
            ->with('success', 'Contratista creado exitosamente.');
    }

    /**
     * Display the specified contratista.
     */
    public function show(Contratista $contratista): Response
    {
        $contratista->load(['users']);

        $stats = [
            'total_users' => $contratista->users()->count(),
            'total_trabajadores' => Trabajador::forContratista($contratista->id)->count(),
            'trabajadores_activos' => Trabajador::forContratista($contratista->id)->active()->count(),
        ];

        return Inertia::render('admin/contratistas/show', [
            'contratista' => [
                'id' => $contratista->id,
                'rut' => $contratista->rut,
                'razon_social' => $contratista->razon_social,
                'nombre_fantasia' => $contratista->nombre_fantasia,
                'direccion' => $contratista->direccion,
                'comuna' => $contratista->comuna,
                'region' => $contratista->region,
                'telefono' => $contratista->telefono,
                'email' => $contratista->email,
                'estado' => $contratista->estado,
                'observaciones' => $contratista->observaciones,
                'created_at' => $contratista->created_at->format('d/m/Y H:i'),
            ],
            'users' => $contratista->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
            ]),
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified contratista.
     */
    public function edit(Contratista $contratista): Response
    {
        return Inertia::render('admin/contratistas/edit', [
            'contratista' => [
                'id' => $contratista->id,
                'rut' => $contratista->rut,
                'razon_social' => $contratista->razon_social,
                'nombre_fantasia' => $contratista->nombre_fantasia,
                'direccion' => $contratista->direccion,
                'comuna' => $contratista->comuna,
                'region' => $contratista->region,
                'telefono' => $contratista->telefono,
                'email' => $contratista->email,
                'estado' => $contratista->estado,
                'observaciones' => $contratista->observaciones,
            ],
            'estados' => [
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'bloqueado', 'label' => 'Bloqueado'],
            ],
        ]);
    }

    /**
     * Update the specified contratista.
     */
    public function update(Request $request, Contratista $contratista): RedirectResponse
    {
        $validated = $request->validate([
            'rut' => ['required', 'string', 'max:12', Rule::unique('contratistas')->ignore($contratista->id), 'regex:/^\d{7,8}-[\dkK]$/'],
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_fantasia' => ['nullable', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'comuna' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'estado' => ['required', 'string', 'in:activo,inactivo,bloqueado'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $contratista->update($validated);

        return redirect()->route('admin.contratistas.index')
            ->with('success', 'Contratista actualizado exitosamente.');
    }

    /**
     * Remove the specified contratista.
     */
    public function destroy(Contratista $contratista): RedirectResponse
    {
        // Check if contratista has associated users
        if ($contratista->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el contratista porque tiene usuarios asociados.');
        }

        // Check if contratista has trabajadores
        if (Trabajador::forContratista($contratista->id)->count() > 0) {
            return back()->with('error', 'No se puede eliminar el contratista porque tiene trabajadores registrados.');
        }

        $contratista->delete();

        return redirect()->route('admin.contratistas.index')
            ->with('success', 'Contratista eliminado exitosamente.');
    }
}
