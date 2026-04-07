<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TarjetaQrRequest;
use App\Models\TarjetaQr;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackingTarjetaController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $estado = $request->input('estado');

        $tarjetas = TarjetaQr::query()
            ->with(['asignacionActiva.trabajador.contratista'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('numero_serie', 'like', "%{$search}%")
                        ->orWhere('codigo_qr', 'like', "%{$search}%");
                });
            })
            ->when($estado, function ($query, $estado): void {
                $query->where('estado', $estado);
            })
            ->orderBy('numero_serie')
            ->get()
            ->map(fn (TarjetaQr $tarjeta): array => [
                'id' => $tarjeta->id,
                'numero_serie' => $tarjeta->numero_serie,
                'codigo_qr' => $tarjeta->codigo_qr,
                'estado' => $tarjeta->estado,
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

        return Inertia::render('admin/packing/tarjetas/index', [
            'tarjetas' => $tarjetas,
            'trabajadores' => $trabajadores,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
            'estados' => [
                ['value' => 'disponible', 'label' => 'Disponible'],
                ['value' => 'asignada', 'label' => 'Asignada'],
                ['value' => 'bloqueada', 'label' => 'Bloqueada'],
                ['value' => 'baja', 'label' => 'Baja'],
            ],
        ]);
    }

    public function store(TarjetaQrRequest $request): RedirectResponse
    {
        TarjetaQr::create($request->validated());

        return redirect('/admin/packing/tarjetas')
            ->with('success', 'Tarjeta QR creada correctamente.');
    }
}
