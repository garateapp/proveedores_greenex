<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloneTurnosRequest;
use App\Http\Requests\TurnoRequest;
use App\Models\Turno;
use App\Models\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TurnoController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedDate = Carbon::parse($request->input('fecha', now()->toDateString()))->toDateString();

        $turnos = Turno::query()
            ->with(['ubicaciones.padre'])
            ->whereDate('fecha', $selectedDate)
            ->orderBy('hora_inicio')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Turno $turno) => [
                'id' => $turno->id,
                'fecha' => $turno->fecha?->toDateString(),
                'nombre' => $turno->nombre,
                'hora_inicio' => $turno->hora_inicio?->format('H:i'),
                'hora_fin' => $turno->hora_fin?->format('H:i'),
                'descripcion' => $turno->descripcion,
                'activo' => $turno->activo,
                'ubicaciones' => $turno->ubicaciones
                    ->sortBy('nombre')
                    ->values()
                    ->map(fn (Ubicacion $ubicacion) => [
                        'id' => $ubicacion->id,
                        'nombre' => $ubicacion->nombre,
                        'codigo' => $ubicacion->codigo,
                        'nombre_completo' => $ubicacion->nombre_completo,
                    ]),
            ]);

        $ubicaciones = Ubicacion::query()
            ->with('padre')
            ->where('activa', true)
            ->orderBy('tipo')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Ubicacion $ubicacion) => [
                'id' => $ubicacion->id,
                'nombre' => $ubicacion->nombre,
                'codigo' => $ubicacion->codigo,
                'tipo' => $ubicacion->tipo,
                'nombre_completo' => $ubicacion->nombre_completo,
            ]);

        return Inertia::render('admin/turnos/index', [
            'turnos' => $turnos,
            'ubicaciones' => $ubicaciones,
            'filters' => [
                'fecha' => $selectedDate,
                'fecha_anterior' => Carbon::parse($selectedDate)->subDay()->toDateString(),
            ],
        ]);
    }

    public function store(TurnoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $ubicacionIds = $validated['ubicacion_ids'];
        unset($validated['ubicacion_ids']);

        $turno = Turno::query()->create($validated);
        $turno->ubicaciones()->sync($ubicacionIds);

        return redirect()
            ->route('admin.turnos.index', ['fecha' => $turno->fecha?->toDateString()])
            ->with('success', 'Turno creado correctamente.');
    }

    public function update(TurnoRequest $request, Turno $turno): RedirectResponse
    {
        $validated = $request->validated();
        $ubicacionIds = $validated['ubicacion_ids'];
        unset($validated['ubicacion_ids']);

        $turno->update($validated);
        $turno->ubicaciones()->sync($ubicacionIds);

        return redirect()
            ->route('admin.turnos.index', ['fecha' => $turno->fecha?->toDateString()])
            ->with('success', 'Turno actualizado correctamente.');
    }

    public function destroy(Turno $turno): RedirectResponse
    {
        $fecha = $turno->fecha?->toDateString();

        $turno->delete();

        return redirect()
            ->route('admin.turnos.index', ['fecha' => $fecha])
            ->with('success', 'Turno eliminado correctamente.');
    }

    public function cloneFromDate(CloneTurnosRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $sourceDate = Carbon::parse($validated['source_date'])->toDateString();
        $targetDate = Carbon::parse($validated['target_date'])->toDateString();

        $sourceTurnos = Turno::query()
            ->with('ubicaciones:id')
            ->whereDate('fecha', $sourceDate)
            ->orderBy('hora_inicio')
            ->get();

        if ($sourceTurnos->isEmpty()) {
            return redirect()
                ->route('admin.turnos.index', ['fecha' => $targetDate])
                ->with('error', 'No hay turnos configurados en la fecha origen.');
        }

        DB::transaction(function () use ($sourceTurnos, $targetDate): void {
            foreach ($sourceTurnos as $sourceTurno) {
                $targetTurno = Turno::query()
                    ->whereDate('fecha', $targetDate)
                    ->where('nombre', $sourceTurno->nombre)
                    ->firstOrNew([
                        'fecha' => $targetDate,
                        'nombre' => $sourceTurno->nombre,
                    ]);

                $targetTurno->fill([
                    'hora_inicio' => $sourceTurno->hora_inicio?->format('H:i:s'),
                    'hora_fin' => $sourceTurno->hora_fin?->format('H:i:s'),
                    'descripcion' => $sourceTurno->descripcion,
                    'activo' => $sourceTurno->activo,
                ]);
                $targetTurno->save();

                $targetTurno->ubicaciones()->sync(
                    $sourceTurno->ubicaciones->pluck('id')->all(),
                );
            }
        });

        return redirect()
            ->route('admin.turnos.index', ['fecha' => $targetDate])
            ->with('success', "Turnos clonados desde {$sourceDate}.");
    }
}
