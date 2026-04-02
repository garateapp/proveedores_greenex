<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Faena;
use App\Models\Trabajador;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsistenciaController extends Controller
{
    /**
     * Display registration form.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        $query = Trabajador::with('faenas')->active();

        if (! $user->isAdmin()) {
            $query->forContratista($user->contratista_id);
        }

        $trabajadores = $query->get();
        $faenas = Faena::active()->get();

        return Inertia::render('asistencias/create', [
            'trabajadores' => $trabajadores,
            'faenas' => $faenas,
        ]);
    }

    /**
     * Store asistencia registration (create only, no update/delete).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'trabajador_id' => ['required', 'exists:trabajadores,id'],
            'tipo' => ['required', 'in:entrada,salida'],
            'faena_id' => ['nullable', 'exists:faenas,id'],
            'fecha_hora' => ['required', 'date'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'ubicacion_texto' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'sincronizado' => ['boolean'],
        ]);

        $trabajador = Trabajador::findOrFail($validated['trabajador_id']);

        // Verify user can register for this trabajador
        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        Asistencia::create([
            'trabajador_id' => $validated['trabajador_id'],
            'faena_id' => $validated['faena_id'] ?? null,
            'contratista_id' => $trabajador->contratista_id,
            'tipo' => $validated['tipo'],
            'fecha_hora' => $validated['fecha_hora'],
            'latitud' => $validated['latitud'] ?? null,
            'longitud' => $validated['longitud'] ?? null,
            'ubicacion_texto' => $validated['ubicacion_texto'] ?? null,
            'registrado_por' => $user->id,
            'observaciones' => $validated['observaciones'] ?? null,
            'sincronizado' => $validated['sincronizado'] ?? true,
            'sincronizado_at' => ($validated['sincronizado'] ?? true) ? now() : null,
        ]);

        return back()->with('success', 'Asistencia registrada exitosamente.');
    }

    /**
     * Store multiple asistencias (bulk registration from offline mode).
     */
    public function storeBulk(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'asistencias' => ['required', 'array'],
            'asistencias.*.trabajador_id' => ['required', 'exists:trabajadores,id'],
            'asistencias.*.tipo' => ['required', 'in:entrada,salida'],
            'asistencias.*.faena_id' => ['nullable', 'exists:faenas,id'],
            'asistencias.*.fecha_hora' => ['required', 'date'],
            'asistencias.*.latitud' => ['nullable', 'numeric'],
            'asistencias.*.longitud' => ['nullable', 'numeric'],
            'asistencias.*.ubicacion_texto' => ['nullable', 'string'],
            'asistencias.*.observaciones' => ['nullable', 'string'],
        ]);

        $created = 0;
        $errors = [];

        foreach ($validated['asistencias'] as $index => $data) {
            try {
                $trabajador = Trabajador::find($data['trabajador_id']);

                // Verify access
                if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
                    $errors[] = "Fila {$index}: Sin permiso para registrar este trabajador";

                    continue;
                }

                Asistencia::create([
                    'trabajador_id' => $data['trabajador_id'],
                    'faena_id' => $data['faena_id'] ?? null,
                    'contratista_id' => $trabajador->contratista_id,
                    'tipo' => $data['tipo'],
                    'fecha_hora' => $data['fecha_hora'],
                    'latitud' => $data['latitud'] ?? null,
                    'longitud' => $data['longitud'] ?? null,
                    'ubicacion_texto' => $data['ubicacion_texto'] ?? null,
                    'registrado_por' => $user->id,
                    'observaciones' => $data['observaciones'] ?? null,
                    'sincronizado' => true,
                    'sincronizado_at' => now(),
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors[] = "Fila {$index}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'errors' => $errors,
        ]);
    }

    /**
     * Display asistencias list (read-only, for reports).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Asistencia::with(['trabajador', 'faena', 'registradoPor'])
            ->orderBy('fecha_hora', 'desc');

        // Filter by contratista if not admin
        if (! $user->isAdmin()) {
            $query->forContratista($user->contratista_id);
        }

        // Filters
        if ($trabajadorId = $request->input('trabajador_id')) {
            $query->forTrabajador($trabajadorId);
        }

        if ($faenaId = $request->input('faena_id')) {
            $query->forFaena($faenaId);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('fecha_hora', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('fecha_hora', '<=', $endDate);
        }

        if ($tipo = $request->input('tipo')) {
            $query->byTipo($tipo);
        }

        $asistencias = $query->paginate(50)->withQueryString();

        return Inertia::render('asistencias/index', [
            'asistencias' => $asistencias,
            'filters' => $request->only(['trabajador_id', 'faena_id', 'start_date', 'end_date', 'tipo']),
        ]);
    }

    /**
     * Export asistencias for DT compliance.
     */
    public function export(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'contratista_id' => ['nullable', 'exists:contratistas,id'],
            'format' => ['required', 'in:csv,excel'],
        ]);

        $query = Asistencia::with(['trabajador', 'faena'])
            ->betweenDates($validated['start_date'], $validated['end_date'])
            ->orderBy('fecha_hora');

        if (! $user->isAdmin()) {
            $query->forContratista($user->contratista_id);
        } elseif (isset($validated['contratista_id'])) {
            $query->forContratista($validated['contratista_id']);
        }

        $asistencias = $query->get();

        // Generate CSV
        $filename = 'asistencias_'.date('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $columns = [
            'trabajador_rut',
            'trabajador_nombre',
            'tipo',
            'fecha_hora',
            'faena',
            'ubicacion',
            'registrado_por',
        ];

        $callback = function () use ($asistencias, $columns) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($file, $columns, ';');

            // Data
            foreach ($asistencias as $asistencia) {
                fputcsv($file, [
                    $asistencia->trabajador->documento,
                    $asistencia->trabajador->nombre_completo,
                    $asistencia->tipo,
                    $asistencia->fecha_hora->format('Y-m-d H:i:s'),
                    $asistencia->faena?->nombre ?? 'N/A',
                    $asistencia->ubicacion_texto ?? 'N/A',
                    $asistencia->registradoPor->name,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
