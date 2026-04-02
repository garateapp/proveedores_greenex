<?php

namespace App\Http\Controllers;

use App\Http\Requests\CentroCargaContratistaDocumentoRequest;
use App\Models\Contratista;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoController extends Controller
{
    /**
     * Display a listing of documentos.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $selectedContratistaId = $request->input('contratista_id');
        $selectedTrabajadorId = $request->input('trabajador_id');

        $query = Documento::with(['tipoDocumento', 'contratista'])
            ->whereHas('tipoDocumento', function ($tipoDocumentoQuery) {
                $tipoDocumentoQuery->where('es_documento_trabajador', false);
            })
            ->orderBy('periodo_ano', 'desc')
            ->orderBy('periodo_mes', 'desc');

        // Filter by contratista if not admin
        if (! $user->isAdmin()) {
            $query->forContratista($user->contratista_id);
        } elseif ($selectedContratistaId) {
            $query->where('contratista_id', (int) $selectedContratistaId);
        }

        if ($selectedTrabajadorId) {
            $trabajador = Trabajador::query()
                ->select(['id', 'contratista_id'])
                ->when(
                    ! $user->isAdmin(),
                    fn ($trabajadorQuery) => $trabajadorQuery->where('contratista_id', $user->contratista_id),
                )
                ->find((string) $selectedTrabajadorId);

            if (! $trabajador) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('contratista_id', $trabajador->contratista_id);
            }
        }

        // Filter by tipo_documento
        if ($tipoDocumentoId = $request->input('tipo_documento_id')) {
            $query->where('tipo_documento_id', $tipoDocumentoId);
        }

        // Filter by estado
        if ($estado = $request->input('estado')) {
            $query->byEstado($estado);
        }

        // Filter by period
        if ($ano = $request->input('ano')) {
            $query->where('periodo_ano', $ano);
        }

        $documentos = $query->paginate(15)->withQueryString();

        $tiposDocumentos = TipoDocumento::active()
            ->where('es_documento_trabajador', false)
            ->get();

        $contratistas = [];
        if ($user->isAdmin()) {
            $contratistas = Contratista::query()
                ->where('estado', 'activo')
                ->orderBy('razon_social')
                ->get(['id', 'razon_social', 'nombre_fantasia'])
                ->map(fn (Contratista $contratista) => [
                    'value' => (string) $contratista->id,
                    'label' => $contratista->nombre_fantasia ?: $contratista->razon_social,
                ])
                ->values()
                ->all();
        }

        $trabajadores = Trabajador::query()
            ->select(['id', 'documento', 'nombre', 'apellido', 'contratista_id'])
            ->active()
            ->when(
                ! $user->isAdmin(),
                fn ($trabajadorQuery) => $trabajadorQuery->where('contratista_id', $user->contratista_id),
            )
            ->when(
                $user->isAdmin() && $selectedContratistaId,
                fn ($trabajadorQuery) => $trabajadorQuery->where('contratista_id', (int) $selectedContratistaId),
            )
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->limit(200)
            ->get()
            ->map(fn (Trabajador $trabajador) => [
                'value' => $trabajador->id,
                'label' => "{$trabajador->nombre_completo} ({$trabajador->documento})",
                'contratista_id' => (string) $trabajador->contratista_id,
            ])
            ->values()
            ->all();

        return Inertia::render('documentos/index', [
            'documentos' => $documentos,
            'tiposDocumentos' => $tiposDocumentos,
            'contratistas' => $contratistas,
            'trabajadores' => $trabajadores,
            'filters' => $request->only(['tipo_documento_id', 'estado', 'ano', 'contratista_id', 'trabajador_id']),
        ]);
    }

    /**
     * Display approval queue for admin users.
     */
    public function approvals(Request $request): Response
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            abort(403);
        }

        $query = Documento::with(['tipoDocumento', 'contratista'])
            ->where('estado', 'pendiente_validacion')
            ->whereHas('tipoDocumento', function ($tipoDocumentoQuery) {
                $tipoDocumentoQuery->where('es_documento_trabajador', false);
            })
            ->orderByDesc('created_at');

        if ($tipoDocumentoId = $request->input('tipo_documento_id')) {
            $query->where('tipo_documento_id', $tipoDocumentoId);
        }

        if ($contratistaId = $request->input('contratista_id')) {
            $query->where('contratista_id', $contratistaId);
        }

        if ($ano = $request->input('ano')) {
            $query->where('periodo_ano', $ano);
        }

        $documentos = $query->paginate(15)->withQueryString();

        $tiposDocumentos = TipoDocumento::query()
            ->active()
            ->where('es_documento_trabajador', false)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        $contratistas = Contratista::query()
            ->orderBy('razon_social')
            ->get(['id', 'razon_social', 'nombre_fantasia'])
            ->map(fn (Contratista $contratista) => [
                'value' => (string) $contratista->id,
                'label' => $contratista->nombre_fantasia ?: $contratista->razon_social,
            ])
            ->values()
            ->all();

        return Inertia::render('documentos/aprobaciones', [
            'documentos' => $documentos,
            'tiposDocumentos' => $tiposDocumentos,
            'contratistas' => $contratistas,
            'filters' => $request->only(['tipo_documento_id', 'contratista_id', 'ano']),
        ]);
    }

    /**
     * Show the form for uploading a new documento.
     */
    public function create(): Response
    {
        $tiposDocumentos = TipoDocumento::active()
            ->where('es_documento_trabajador', false)
            ->get();
        $contratistas = [];

        if (request()->user()->isAdmin()) {
            $contratistas = Contratista::query()
                ->where('estado', 'activo')
                ->orderBy('razon_social')
                ->get(['id', 'razon_social'])
                ->map(fn ($c) => [
                    'value' => $c->id,
                    'label' => $c->razon_social,
                ]);
        }

        return Inertia::render('documentos/create', [
            'tiposDocumentos' => $tiposDocumentos,
            'contratistas' => $contratistas,
        ]);
    }

    /**
     * Store a newly uploaded documento.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'tipo_documento_id' => ['required', 'exists:tipo_documentos,id'],
            'periodo_ano' => ['required', 'integer', 'min:2020', 'max:'.date('Y')],
            'periodo_mes' => ['nullable', 'integer', 'min:1', 'max:12'],
            'archivo' => ['required', 'file', 'max:10240'], // Max 10MB
            'observaciones' => ['nullable', 'string'],
        ]);

        $tipoDocumento = TipoDocumento::active()
            ->where('es_documento_trabajador', false)
            ->findOrFail($validated['tipo_documento_id']);

        // Validate file extension
        $file = $request->file('archivo');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $tipoDocumento->formatos_permitidos ?? [])) {
            return back()->withErrors([
                'archivo' => 'Formato de archivo no permitido. Formatos aceptados: '.implode(', ', $tipoDocumento->formatos_permitidos ?? []),
            ]);
        }

        // Validate file size
        $fileSizeKb = $file->getSize() / 1024;
        if ($fileSizeKb > $tipoDocumento->tamano_maximo_kb) {
            return back()->withErrors([
                'archivo' => 'El archivo excede el tamaño máximo permitido de '.$tipoDocumento->tamano_maximo_kb.'KB',
            ]);
        }

        // Validate CSV structure if it's LRE
        if ($tipoDocumento->codigo === 'LRE' && $extension === 'csv') {
            $validation = $this->validateLreStructure($file);
            if (! $validation['valid']) {
                return back()->withErrors(['archivo' => $validation['message']]);
            }
        }

        $contratistaId = $user->isAdmin() ? $request->input('contratista_id') : $user->contratista_id;

        if ($this->hasContratistaPeriodoDuplicate(
            contratistaId: (int) $contratistaId,
            tipoDocumento: $tipoDocumento,
            periodoAno: (int) $validated['periodo_ano'],
            periodoMes: isset($validated['periodo_mes']) ? (int) $validated['periodo_mes'] : null,
        )) {
            return back()->withErrors([
                'periodo_mes' => 'Ya existe un documento de este tipo para el período seleccionado.',
            ]);
        }

        $this->ensureFileIsReadableAndNotCorrupted($file);

        // Store file
        $path = $file->store('documentos/'.$contratistaId.'/'.$tipoDocumento->codigo, 'private');

        // Calculate fecha_vencimiento
        $fechaVencimiento = null;
        if ($tipoDocumento->dias_vencimiento && $validated['periodo_mes']) {
            $fechaVencimiento = now()
                ->setYear($validated['periodo_ano'])
                ->setMonth($validated['periodo_mes'])
                ->endOfMonth()
                ->addDays($tipoDocumento->dias_vencimiento);
        }

        Documento::create([
            'contratista_id' => $contratistaId,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => $validated['periodo_ano'],
            'periodo_mes' => $validated['periodo_mes'],
            'archivo_nombre_original' => $file->getClientOriginalName(),
            'archivo_ruta' => $path,
            'archivo_tamano_kb' => round($fileSizeKb),
            'estado' => $tipoDocumento->requiere_validacion ? 'pendiente_validacion' : 'aprobado',
            'observaciones' => $validated['observaciones'],
            'fecha_vencimiento' => $fechaVencimiento,
            'cargado_por' => $user->id,
        ]);

        return redirect()->route('documentos.index')->with('success', 'Documento cargado exitosamente.');
    }

    /**
     * Store uploaded documento from Centro de Carga de contratistas.
     */
    public function storeFromCentroCargaContratista(CentroCargaContratistaDocumentoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $contratista = Contratista::query()->findOrFail((int) $validated['contratista_id']);

        $this->authorizeContratistaAccess($request, $contratista);

        $tipoDocumento = TipoDocumento::query()
            ->active()
            ->where('es_documento_trabajador', false)
            ->findOrFail((int) $validated['tipo_documento_id']);

        $file = $request->file('archivo');
        $extension = strtolower($file->getClientOriginalExtension());
        $formatosPermitidos = collect($tipoDocumento->formatos_permitidos ?? [])
            ->map(fn ($format) => strtolower((string) $format))
            ->values()
            ->all();

        if (! in_array($extension, $formatosPermitidos, true)) {
            throw ValidationException::withMessages([
                'archivo' => 'Formato de archivo no permitido. Formatos aceptados: '.implode(', ', $formatosPermitidos),
            ]);
        }

        $this->ensureFileIsReadableAndNotCorrupted($file);

        $fileSizeKb = (float) $file->getSize() / 1024;
        if ($fileSizeKb > $tipoDocumento->tamano_maximo_kb) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo excede el tamano maximo permitido de '.$tipoDocumento->tamano_maximo_kb.'KB.',
            ]);
        }

        $periodoMes = $validated['periodo_mes'] ?? null;

        if ($this->hasContratistaPeriodoDuplicate(
            contratistaId: $contratista->id,
            tipoDocumento: $tipoDocumento,
            periodoAno: (int) $validated['periodo_ano'],
            periodoMes: $periodoMes !== null ? (int) $periodoMes : null,
        )) {
            throw ValidationException::withMessages([
                'tipo_documento_id' => 'Este tipo de documento ya fue cargado para el período seleccionado.',
            ]);
        }

        $path = $file->store(
            'documentos/'.$contratista->id.'/'.$tipoDocumento->codigo,
            'private',
        );

        $fechaVencimiento = $validated['expiry_date'] ?? null;
        if (! $fechaVencimiento && $tipoDocumento->dias_vencimiento && $periodoMes) {
            $fechaVencimiento = now()
                ->setYear((int) $validated['periodo_ano'])
                ->setMonth((int) $periodoMes)
                ->endOfMonth()
                ->addDays($tipoDocumento->dias_vencimiento)
                ->toDateString();
        }

        $documento = Documento::query()->create([
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => (int) $validated['periodo_ano'],
            'periodo_mes' => $periodoMes !== null ? (int) $periodoMes : null,
            'archivo_nombre_original' => $file->getClientOriginalName(),
            'archivo_ruta' => $path,
            'archivo_tamano_kb' => (int) round($fileSizeKb),
            'estado' => $tipoDocumento->requiere_validacion ? 'pendiente_validacion' : 'aprobado',
            'observaciones' => null,
            'fecha_vencimiento' => $fechaVencimiento,
            'cargado_por' => (int) $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Documento cargado exitosamente.',
            'data' => [
                'id' => $documento->id,
                'contratista_id' => $documento->contratista_id,
                'tipo_documento_id' => $documento->tipo_documento_id,
                'archivo_nombre_original' => $documento->archivo_nombre_original,
                'periodo_ano' => $documento->periodo_ano,
                'periodo_mes' => $documento->periodo_mes,
                'fecha_vencimiento' => $documento->fecha_vencimiento?->toDateString(),
            ],
        ], 201);
    }

    /**
     * Download the documento file.
     */
    public function download(Documento $documento)
    {
        $user = request()->user();

        // Check access
        if (! $user->isAdmin() && $documento->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        return Storage::disk('private')->download(
            $documento->archivo_ruta,
            $documento->archivo_nombre_original
        );
    }

    /**
     * Preview documento inline in browser.
     */
    public function preview(Documento $documento): StreamedResponse
    {
        $user = request()->user();

        if (! $user->isAdmin() && $documento->contratista_id !== $user->contratista_id) {
            abort(403);
        }

        $disk = Storage::disk('private');
        if (! $disk->exists($documento->archivo_ruta)) {
            abort(404);
        }

        $stream = $disk->readStream($documento->archivo_ruta);
        if ($stream === false) {
            abort(404);
        }

        $fileName = str_replace('"', '', $documento->archivo_nombre_original);
        $mimeType = $disk->mimeType($documento->archivo_ruta) ?: 'application/octet-stream';

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Approve a documento (admin only).
     */
    public function approve(Request $request, Documento $documento)
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            abort(403);
        }

        $documento->update([
            'estado' => 'aprobado',
            'validado_por' => $user->id,
            'validado_at' => now(),
            'motivo_rechazo' => null,
        ]);

        return back()->with('success', 'Documento aprobado exitosamente.');
    }

    /**
     * Reject a documento (admin only).
     */
    public function reject(Request $request, Documento $documento)
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'motivo_rechazo' => ['required', 'string'],
        ]);

        $documento->update([
            'estado' => 'rechazado',
            'validado_por' => $user->id,
            'validado_at' => now(),
            'motivo_rechazo' => $validated['motivo_rechazo'],
        ]);

        return back()->with('success', 'Documento rechazado.');
    }

    /**
     * Validate LRE CSV structure.
     */
    private function validateLreStructure($file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return ['valid' => false, 'message' => 'No se pudo abrir el archivo'];
        }

        // Read header
        $header = fgetcsv($handle, 0, ';');

        if ($header === false) {
            fclose($handle);

            return ['valid' => false, 'message' => 'El archivo está vacío'];
        }

        // Required columns for LRE (según Mi DT)
        $requiredColumns = ['rut', 'nombres', 'apellido_paterno', 'apellido_materno', 'fecha_ingreso'];

        $headerLower = array_map('strtolower', array_map('trim', $header));

        foreach ($requiredColumns as $required) {
            if (! in_array($required, $headerLower)) {
                fclose($handle);

                return ['valid' => false, 'message' => "Falta columna requerida: {$required}"];
            }
        }

        fclose($handle);

        return ['valid' => true];
    }

    private function authorizeContratistaAccess(Request $request, Contratista $contratista): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $contratista->id !== $user->contratista_id) {
            abort(403);
        }
    }

    private function ensureFileIsReadableAndNotCorrupted(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo no es valido o esta corrupto.',
            ]);
        }

        if (($file->getSize() ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo parece estar vacio o dañado.',
            ]);
        }
    }

    private function hasContratistaPeriodoDuplicate(
        int $contratistaId,
        TipoDocumento $tipoDocumento,
        int $periodoAno,
        ?int $periodoMes,
    ): bool {
        if ($tipoDocumento->permite_multiples_en_mes) {
            return false;
        }

        return Documento::query()
            ->where('contratista_id', $contratistaId)
            ->where('tipo_documento_id', $tipoDocumento->id)
            ->where('periodo_ano', $periodoAno)
            ->when(
                $periodoMes === null,
                fn ($query) => $query->whereNull('periodo_mes'),
                fn ($query) => $query->where('periodo_mes', $periodoMes),
            )
            ->exists();
    }
}
