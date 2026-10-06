<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GaratePass\AssignAdminTarjetaQrAction;
use App\Enums\PerfilTarjetaQr;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminTarjetaQrRequest;
use App\Http\Requests\TarjetaQrRequest;
use App\Models\TarjetaQr;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PackingTarjetaController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $estado = $request->input('estado');
        $perfil = $request->input('perfil');

        $tarjetas = $this->tarjetasQuery($search, $estado, $perfil)
            ->get()
            ->map(fn (TarjetaQr $tarjeta): array => [
                'id' => $tarjeta->id,
                'numero_serie' => $tarjeta->numero_serie,
                'codigo_qr' => $tarjeta->codigo_qr,
                'estado' => $tarjeta->estado,
                'perfil' => $tarjeta->perfil?->value,
                'administrador' => $tarjeta->admin === null ? null : [
                    'id' => $tarjeta->admin->id,
                    'nombre' => $tarjeta->admin->name,
                    'centro_costo' => $tarjeta->admin->centroCosto?->codigo,
                ],
                'observaciones' => $tarjeta->observaciones,
                'trabajador_actual' => $tarjeta->asignacionActiva?->trabajador === null ? null : [
                    'id' => $tarjeta->asignacionActiva->trabajador->id,
                    'nombre_completo' => $tarjeta->asignacionActiva->trabajador->nombre_completo,
                    'contratista' => $tarjeta->asignacionActiva->trabajador->contratista?->razon_social,
                    'asignada_en' => $tarjeta->asignacionActiva->asignada_en?->format('Y-m-d H:i:s'),
                ],
            ])
            ->values();

        $trabajadores = Trabajador::query()
            ->with('contratista')
            ->active()
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get()
            ->map(fn (Trabajador $trabajador): array => [
                'id' => $trabajador->id,
                'nombre_completo' => $trabajador->nombre_completo,
                'documento' => $trabajador->documento,
                'contratista' => $trabajador->contratista?->razon_social,
            ])
            ->values();

        $administradores = User::query()
            ->where('role', UserRole::Admin->value)
            ->with('centroCosto')
            ->orderBy('name')
            ->get()
            ->map(fn (User $usuario): array => [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
                'centro_costo' => $usuario->centroCosto?->codigo,
            ])
            ->values();

        return Inertia::render('admin/packing/tarjetas/index', [
            'tarjetas' => $tarjetas,
            'trabajadores' => $trabajadores,
            'administradores' => $administradores,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
                'perfil' => $perfil,
            ],
            'estados' => [
                ['value' => 'disponible', 'label' => 'Disponible'],
                ['value' => 'asignada', 'label' => 'Asignada'],
                ['value' => 'bloqueada', 'label' => 'Bloqueada'],
                ['value' => 'baja', 'label' => 'Baja'],
            ],
            'perfiles' => array_map(
                fn (PerfilTarjetaQr $perfilTarjeta): array => [
                    'value' => $perfilTarjeta->value,
                    'label' => $perfilTarjeta->label(),
                ],
                PerfilTarjetaQr::cases(),
            ),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->input('search', ''));
        $estado = $request->input('estado');
        $tarjetas = $this->tarjetasQuery($search, $estado)->get();
        $filename = 'packing_tarjetas_'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $columns = [
            'numero_serie',
            'codigo_qr',
            'estado',
            'trabajador_rut',
            'trabajador_nombre',
            'contratista',
            'asignada_en',
            'observaciones',
        ];

        $callback = function () use ($tarjetas, $columns): void {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns, ';');

            foreach ($tarjetas as $tarjeta) {
                $trabajador = $tarjeta->asignacionActiva?->trabajador;

                fputcsv($file, [
                    $tarjeta->numero_serie,
                    $tarjeta->codigo_qr,
                    $tarjeta->estado,
                    $trabajador?->documento ?? 'N/A',
                    $trabajador?->nombre_completo ?? 'N/A',
                    $trabajador?->contratista?->razon_social ?? 'N/A',
                    $tarjeta->asignacionActiva?->asignada_en?->format('Y-m-d H:i:s') ?? 'N/A',
                    $tarjeta->observaciones ?? 'N/A',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(TarjetaQrRequest $request): RedirectResponse
    {
        TarjetaQr::create($request->validated());

        return redirect('/admin/packing/tarjetas')
            ->with('success', 'Tarjeta QR creada correctamente.');
    }

    /**
     * Convierte la tarjeta en un QR de administrador del casino.
     */
    public function assignAdmin(
        AdminTarjetaQrRequest $request,
        TarjetaQr $tarjeta,
        AssignAdminTarjetaQrAction $assignAdminTarjetaQrAction,
    ): RedirectResponse {
        $administrador = User::query()->findOrFail($request->validated('admin_user_id'));

        $assignAdminTarjetaQrAction->executeAsAdmin(
            $tarjeta,
            $administrador,
            $request->user(),
            $request->validated('observaciones'),
        );

        return redirect('/admin/packing/tarjetas')
            ->with('success', "Tarjeta {$tarjeta->numero_serie} asignada a {$administrador->name}.");
    }

    /**
     * Devuelve la tarjeta al perfil de comensal, para que vuelva a emitir
     * vales de almuerzo a quien se le asigne como trabajador.
     */
    public function revokeAdmin(TarjetaQr $tarjeta, AssignAdminTarjetaQrAction $assignAdminTarjetaQrAction): RedirectResponse
    {
        if (! $tarjeta->esDeAdminCasino()) {
            return redirect('/admin/packing/tarjetas')
                ->with('error', 'La tarjeta seleccionada no es un QR de administrador.');
        }

        $assignAdminTarjetaQrAction->executeAsComensal($tarjeta);

        return redirect('/admin/packing/tarjetas')
            ->with('success', "Tarjeta {$tarjeta->numero_serie} devuelta al perfil de comensal.");
    }

    private function tarjetasQuery(string $search, mixed $estado, mixed $perfil = null): Builder
    {
        return TarjetaQr::query()
            ->with(['asignacionActiva.trabajador.contratista', 'admin.centroCosto'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('numero_serie', 'like', "%{$search}%")
                        ->orWhere('codigo_qr', 'like', "%{$search}%");
                });
            })
            ->when($estado, function ($query, $estado): void {
                $query->where('estado', $estado);
            })
            ->when($perfil, function ($query, $perfil): void {
                $query->where('perfil', $perfil);
            })
            ->orderBy('numero_serie');
    }
}
