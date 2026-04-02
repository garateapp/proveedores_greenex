<?php

namespace App\Http\Controllers;

use App\Models\Contratista;
use App\Models\EstadoPago;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstadoPagoController extends Controller
{
    /**
     * Display a listing of estados de pago.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = EstadoPago::with(['contratista', 'actualizadoPor'])
            ->orderBy('created_at', 'desc');

        // Filter by contratista if not admin
        if (! $user->isAdmin()) {
            $query->forContratista($user->contratista_id);
        }

        // Filter by estado
        if ($estado = $request->input('estado')) {
            $query->byEstado($estado);
        }

        // Filter by año
        if ($ano = $request->input('ano')) {
            $query->whereYear('fecha_documento', $ano);
        }

        $estadosPago = $query->paginate(15)->withQueryString();

        return Inertia::render('estados-pago/index', [
            'estadosPago' => $estadosPago,
            'filters' => $request->only(['estado', 'ano']),
        ]);
    }

    /**
     * Show form to create a new estado de pago (admin only).
     */
    public function create(): Response
    {
        $user = request()->user();

        if (! $user->isAdmin()) {
            abort(403);
        }

        $contratistas = Contratista::query()
            ->where('estado', 'activo')
            ->orderBy('razon_social')
            ->get(['id', 'razon_social'])
            ->map(fn ($c) => [
                'value' => $c->id,
                'label' => $c->razon_social,
            ]);

        return Inertia::render('estados-pago/create', [
            'contratistas' => $contratistas,
        ]);
    }

    /**
     * Display the specified estado de pago with historial.
     */
    public function show(EstadoPago $estadoPago): Response
    {
        $user = request()->user();

        // Check access
        if (! $user->isAdmin() && $estadoPago->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        $estadoPago->load(['contratista', 'historial.usuario']);

        return Inertia::render('estados-pago/show', [
            'estadoPago' => $estadoPago,
        ]);
    }

    /**
     * Store a newly created estado de pago (admin only).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'contratista_id' => ['required', 'exists:contratistas,id'],
            'numero_documento' => ['required', 'string', 'max:255'],
            'fecha_documento' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
            'fecha_pago_estimada' => ['nullable', 'date'],
        ]);

        EstadoPago::create([
            ...$validated,
            'estado' => 'recibido',
            'actualizado_por' => $user->id,
        ]);

        return redirect()->route('estados-pago.index')->with('success', 'Estado de pago registrado exitosamente.');
    }

    /**
     * Update estado (admin only).
     */
    public function updateEstado(Request $request, EstadoPago $estadoPago)
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'estado' => ['required', 'in:recibido,en_revision,aprobado_pago,retenido,pagado,rechazado'],
            'observaciones' => ['nullable', 'string'],
            'motivo_retencion' => ['required_if:estado,retenido', 'nullable', 'string'],
            'fecha_pago_estimada' => ['nullable', 'date'],
            'fecha_pago_real' => ['required_if:estado,pagado', 'nullable', 'date'],
        ]);

        $estadoPago->update([
            ...$validated,
            'actualizado_por' => $user->id,
        ]);

        return back()->with('success', 'Estado actualizado exitosamente.');
    }
}
