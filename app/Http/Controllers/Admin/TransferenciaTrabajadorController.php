<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contratista;
use App\Models\ContratistaTrabajadorHistorial;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransferenciaTrabajadorController extends Controller
{
    public function create(): Response
    {
        $contratistas = Contratista::query()
            ->where('estado', 'activo')
            ->orderBy('razon_social')
            ->get(['id', 'razon_social', 'nombre_fantasia'])
            ->map(fn ($c) => [
                'value' => $c->id,
                'label' => $c->razon_social.($c->nombre_fantasia ? " ({$c->nombre_fantasia})" : ''),
            ]);

        return Inertia::render('admin/contratistas/transferencia', [
            'contratistas' => $contratistas,
        ]);
    }

    /**
     * Load trabajadores for a given contratista (AJAX).
     */
    public function trabajadores(Request $request)
    {
        $request->validate([
            'contratista_id' => ['required', 'exists:contratistas,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $trabajadores = Trabajador::query()
            ->with([
                'contratista:id,razon_social',
                'faenas' => fn ($q) => $q->wherePivotNull('fecha_desasignacion'),
            ])
            ->forContratista((int) $request->input('contratista_id'))
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('documento', 'like', "%{$search}%");
                });
            })
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Trabajador $t) => [
                'id' => $t->id,
                'documento' => $t->documento,
                'nombre' => $t->nombre,
                'apellido' => $t->apellido,
                'estado' => $t->estado,
                'faenas_activas' => $t->faenas->map(fn ($f) => [
                    'id' => $f->id,
                    'nombre' => $f->nombre,
                ]),
            ]);

        return response()->json($trabajadores);
    }

    /**
     * Preview the transfer impact.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'contratista_origen_id' => ['required', 'exists:contratistas,id'],
            'contratista_destino_id' => ['required', 'exists:contratistas,id', 'different:contratista_origen_id'],
            'trabajador_ids' => ['required', 'array', 'min:1'],
            'trabajador_ids.*' => ['string', 'exists:trabajadores,id'],
        ]);

        $origen = Contratista::findOrFail((int) $validated['contratista_origen_id']);
        $destino = Contratista::findOrFail((int) $validated['contratista_destino_id']);

        $trabajadores = Trabajador::with([
            'faenas' => fn ($q) => $q->wherePivotNull('fecha_desasignacion'),
            'tarjetaQrAsignacionActiva',
        ])
            ->whereIn('id', $validated['trabajador_ids'])
            ->where('contratista_id', $origen->id)
            ->get();

        $faenasActivas = $trabajadores->pluck('faenas')->flatten()->unique('id')->values();
        $qrActivas = $trabajadores->filter(fn ($t) => $t->tarjetaQrAsignacionActiva)->count();

        // Faenas disponibles del destino para reasignación opcional
        $faenasDestino = $destino->faenas()
            ->where('estado', 'activa')
            ->orderBy('nombre')
            ->get(['faenas.id', 'faenas.nombre', 'faenas.codigo']);

        return response()->json([
            'total_trabajadores' => $trabajadores->count(),
            'trabajadores_invalidos' => count($validated['trabajador_ids']) - $trabajadores->count(),
            'faenas_activas_a_cerrar' => $faenasActivas->map(fn ($f) => [
                'id' => $f->id,
                'nombre' => $f->nombre,
                'total_trabajadores' => $trabajadores->filter(
                    fn ($t) => $t->faenas->contains('id', $f->id),
                )->count(),
            ]),
            'tarjetas_qr_a_desasignar' => $qrActivas,
            'faenas_destino_disponibles' => $faenasDestino->map(fn ($f) => [
                'value' => $f->id,
                'label' => "{$f->nombre} ({$f->codigo})",
            ]),
        ]);
    }

    /**
     * Execute the mass transfer.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contratista_origen_id' => ['required', 'exists:contratistas,id'],
            'contratista_destino_id' => ['required', 'exists:contratistas,id', 'different:contratista_origen_id'],
            'trabajador_ids' => ['required', 'array', 'min:1'],
            'trabajador_ids.*' => ['string', 'exists:trabajadores,id'],
            'faena_ids' => ['nullable', 'array'],
            'faena_ids.*' => ['integer', 'exists:faenas,id'],
            'motivo' => ['nullable', 'string', 'max:1000'],
        ]);

        $origen = Contratista::findOrFail((int) $validated['contratista_origen_id']);
        $destino = Contratista::findOrFail((int) $validated['contratista_destino_id']);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $trabajadorIds = collect($validated['trabajador_ids']);

        DB::transaction(function () use ($trabajadorIds, $origen, $destino, $user, $validated) {
            $trabajadores = Trabajador::with([
                'faenas' => fn ($q) => $q->wherePivotNull('fecha_desasignacion'),
                'tarjetaQrAsignacionActiva',
            ])
                ->whereIn('id', $trabajadorIds)
                ->where('contratista_id', $origen->id)
                ->get();

            foreach ($trabajadores as $trabajador) {
                $oldContratistaId = $trabajador->contratista_id;

                // 1. Finalizar asignaciones activas a faenas
                foreach ($trabajador->faenas as $faena) {
                    $trabajador->faenas()->updateExistingPivot($faena->id, [
                        'fecha_desasignacion' => now(),
                    ]);
                }

                // 2. Desasignar tarjeta QR activa
                if ($trabajador->tarjetaQrAsignacionActiva) {
                    $trabajador->tarjetaQrAsignacionActiva->update([
                        'desasignada_por' => $user->id,
                        'desasignada_en' => now(),
                        'observaciones' => 'Traspaso automático por cambio de contratista',
                    ]);
                }

                // 3. Actualizar contratista_id
                $trabajador->update([
                    'contratista_id' => $destino->id,
                ]);

                // 4. Asignar a faenas del destino (opcional)
                if (! empty($validated['faena_ids'])) {
                    foreach ($validated['faena_ids'] as $faenaId) {
                        $trabajador->faenas()->attach((int) $faenaId, [
                            'fecha_asignacion' => now(),
                            'fecha_desasignacion' => null,
                        ]);
                    }
                }

                // 5. Registrar historial
                ContratistaTrabajadorHistorial::create([
                    'trabajador_id' => $trabajador->id,
                    'contratista_origen_id' => $oldContratistaId,
                    'contratista_destino_id' => $destino->id,
                    'usuario_id' => $user->id,
                    'motivo' => $validated['motivo'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.contratistas.transferencia')
            ->with('success', "Traspaso completado: {$trabajadorIds->count()} trabajadores transferidos de {$origen->razon_social} a {$destino->razon_social}.");
    }
}
