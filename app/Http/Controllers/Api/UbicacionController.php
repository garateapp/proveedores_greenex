<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ubicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    /**
     * Display a listing of active ubicaciones.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ubicacion::query()
            ->with(['padre', 'hijosActivos'])
            ->where('activa', true)
            ->orderBy('orden');

        // Filter by tipo if provided
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // Filter by padre_id if provided
        if ($request->filled('padre_id')) {
            $query->where('padre_id', $request->padre_id);
        }

        // Search by nombre or codigo
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%'.$request->search.'%')
                    ->orWhere('codigo', 'like', '%'.$request->search.'%');
            });
        }

        $ubicaciones = $query->get()->map(function ($ubicacion) {
            return [
                'id' => $ubicacion->id,
                'padre_id' => $ubicacion->padre_id,
                'nombre' => $ubicacion->nombre,
                'codigo' => $ubicacion->codigo,
                'descripcion' => $ubicacion->descripcion,
                'tipo' => $ubicacion->tipo,
                'orden' => $ubicacion->orden,
                'nombre_completo' => $ubicacion->nombre_completo,
                'hijos' => $ubicacion->hijosActivos->map(function ($hijo) {
                    return [
                        'id' => $hijo->id,
                        'nombre' => $hijo->nombre,
                        'codigo' => $hijo->codigo,
                        'orden' => $hijo->orden,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $ubicaciones,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'padre_id' => 'nullable|exists:ubicaciones,id',
                'nombre' => 'required|string|max:255',
                'codigo' => 'required|string|unique:ubicaciones,codigo',
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
            ]);

            $validated['tipo'] = $validated['padre_id'] ? 'secundaria' : 'principal';
            $validated['orden'] = $validated['orden'] ?? 0;
            $validated['activa'] = true;

            $ubicacion = Ubicacion::create($validated);
            $ubicacion->load(['padre']);

            return response()->json([
                'success' => true,
                'message' => 'Ubicación creada exitosamente',
                'data' => [
                    'id' => $ubicacion->id,
                    'padre_id' => $ubicacion->padre_id,
                    'nombre' => $ubicacion->nombre,
                    'codigo' => $ubicacion->codigo,
                    'descripcion' => $ubicacion->descripcion,
                    'tipo' => $ubicacion->tipo,
                    'orden' => $ubicacion->orden,
                    'nombre_completo' => $ubicacion->nombre_completo,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear ubicación: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Ubicacion $ubicacion): JsonResponse
    {
        if (! $ubicacion->activa) {
            return response()->json([
                'success' => false,
                'message' => 'Ubicación no encontrada',
            ], 404);
        }

        $ubicacion->load(['padre', 'hijosActivos']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ubicacion->id,
                'padre_id' => $ubicacion->padre_id,
                'nombre' => $ubicacion->nombre,
                'codigo' => $ubicacion->codigo,
                'descripcion' => $ubicacion->descripcion,
                'tipo' => $ubicacion->tipo,
                'orden' => $ubicacion->orden,
                'nombre_completo' => $ubicacion->nombre_completo,
                'hijos' => $ubicacion->hijosActivos->map(function ($hijo) {
                    return [
                        'id' => $hijo->id,
                        'nombre' => $hijo->nombre,
                        'codigo' => $hijo->codigo,
                        'orden' => $hijo->orden,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ubicacion $ubicacion): JsonResponse
    {
        $validated = $request->validate([
            'padre_id' => 'nullable|exists:ubicaciones,id|different:id',
            'nombre' => 'sometimes|required|string|max:255',
            'codigo' => 'sometimes|required|string|unique:ubicaciones,codigo,'.$ubicacion->id,
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer|min:0',
        ]);

        // Auto-set tipo based on padre_id
        if (isset($validated['padre_id'])) {
            $validated['tipo'] = $validated['padre_id'] ? 'secundaria' : 'principal';
        }

        $ubicacion->update($validated);
        $ubicacion->load(['padre', 'hijosActivos']);

        return response()->json([
            'success' => true,
            'message' => 'Ubicación actualizada exitosamente',
            'data' => [
                'id' => $ubicacion->id,
                'padre_id' => $ubicacion->padre_id,
                'nombre' => $ubicacion->nombre,
                'codigo' => $ubicacion->codigo,
                'descripcion' => $ubicacion->descripcion,
                'tipo' => $ubicacion->tipo,
                'orden' => $ubicacion->orden,
                'nombre_completo' => $ubicacion->nombre_completo,
                'hijos' => $ubicacion->hijosActivos->map(function ($hijo) {
                    return [
                        'id' => $hijo->id,
                        'nombre' => $hijo->nombre,
                        'codigo' => $hijo->codigo,
                        'orden' => $hijo->orden,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ubicacion $ubicacion): JsonResponse
    {
        // Check if it has children
        if ($ubicacion->hijos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una ubicación que tiene sub-ubicaciones',
            ], 422);
        }

        $ubicacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ubicación eliminada exitosamente',
        ]);
    }

    /**
     * Get ubicaciones principales (for mobile app dropdowns)
     */
    public function principales(): JsonResponse
    {
        $ubicaciones = Ubicacion::principales()
            ->with('hijosActivos')
            ->get()
            ->map(function ($ubicacion) {
                return [
                    'id' => $ubicacion->id,
                    'nombre' => $ubicacion->nombre,
                    'codigo' => $ubicacion->codigo,
                    'sub_ubicaciones' => $ubicacion->hijosActivos->map(function ($hijo) {
                        return [
                            'id' => $hijo->id,
                            'nombre' => $hijo->nombre,
                            'codigo' => $hijo->codigo,
                            'orden' => $hijo->orden,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ubicaciones,
        ]);
    }
}
