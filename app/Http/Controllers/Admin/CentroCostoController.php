<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentroCostoRequest;
use App\Models\CentroCosto;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CentroCostoController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $estado = $request->input('estado');

        $centros = CentroCosto::query()
            ->withCount(['trabajadores', 'valesAlmuerzo', 'valesLote'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('codigo', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%");
                });
            })
            ->when($estado === 'activo', fn ($query) => $query->where('activo', true))
            ->when($estado === 'inactivo', fn ($query) => $query->where('activo', false))
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (CentroCosto $centroCosto): array => [
                'id' => $centroCosto->id,
                'codigo' => $centroCosto->codigo,
                'nombre' => $centroCosto->nombre,
                'activo' => $centroCosto->activo,
                'trabajadores_count' => $centroCosto->trabajadores_count,
                'vales_almuerzo_count' => $centroCosto->vales_almuerzo_count,
                'vales_lote_count' => $centroCosto->vales_lote_count,
            ]);

        return Inertia::render('admin/centros-costo/index', [
            'centros' => $centros,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
        ]);
    }

    public function store(CentroCostoRequest $request): RedirectResponse
    {
        CentroCosto::query()->create($request->validated());

        return redirect()
            ->route('admin.centros-costo.index')
            ->with('success', 'Centro de costo creado correctamente.');
    }

    public function update(CentroCostoRequest $request, CentroCosto $centroCosto): RedirectResponse
    {
        $centroCosto->update($request->validated());

        return redirect()
            ->route('admin.centros-costo.index')
            ->with('success', 'Centro de costo actualizado correctamente.');
    }

    /**
     * Se borra lógicamente y nunca en cascada. Los vales ya emitidos son
     * historial contable y conservan su centro de costo original; lo que sí se
     * limpia son las asignaciones vigentes, porque un trabajador o administrador
     * apuntando a un centro dado de baja emitiría vales que la app no puede
     * imputar sin que nadie lo note.
     */
    public function destroy(CentroCosto $centroCosto): RedirectResponse
    {
        $trabajadoresAfectados = $centroCosto->trabajadores()->count();
        $administradoresAfectados = User::query()
            ->where('centro_costo_id', $centroCosto->id)
            ->count();

        DB::transaction(function () use ($centroCosto): void {
            Trabajador::query()
                ->where('centro_costo_id', $centroCosto->id)
                ->update(['centro_costo_id' => null]);

            User::query()
                ->where('centro_costo_id', $centroCosto->id)
                ->update(['centro_costo_id' => null]);

            $centroCosto->delete();
        });

        $mensaje = 'Centro de costo eliminado correctamente.';

        if ($trabajadoresAfectados > 0) {
            $mensaje .= " {$trabajadoresAfectados} trabajador(es) quedaron sin centro de costo.";
        }

        if ($administradoresAfectados > 0) {
            $mensaje .= " {$administradoresAfectados} administrador(es) quedaron sin centro de costo.";
        }

        return redirect()
            ->route('admin.centros-costo.index')
            ->with('success', $mensaje);
    }
}
