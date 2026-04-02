<?php

namespace App\Http\Controllers;

use App\Http\Requests\CentroCargaDocumentoRequest;
use App\Http\Requests\DocumentoTrabajadorRequest;
use App\Models\DocumentoTrabajador;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoTrabajadorController extends Controller
{
    /**
     * Store a newly uploaded documento for trabajador.
     */
    public function store(DocumentoTrabajadorRequest $request, Trabajador $trabajador): RedirectResponse
    {
        $validated = $request->validated();
        $this->authorizeTrabajadorAccess($request->user(), $trabajador);

        $this->storeDocumentoTrabajador(
            trabajador: $trabajador,
            tipoDocumentoId: (int) $validated['tipo_documento_id'],
            file: $request->file('archivo'),
            cargadoPor: (int) $request->user()->id,
            expiryDate: null,
        );

        return back()->with('success', 'Documento del trabajador cargado exitosamente.');
    }

    /**
     * Store uploaded documento from Centro de Carga flow.
     */
    public function storeFromCentroCarga(CentroCargaDocumentoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $trabajador = Trabajador::query()->findOrFail((string) $validated['trabajador_id']);

        $this->authorizeTrabajadorAccess($request->user(), $trabajador);

        $documento = $this->storeDocumentoTrabajador(
            trabajador: $trabajador,
            tipoDocumentoId: (int) $validated['tipo_documento_id'],
            file: $request->file('archivo'),
            cargadoPor: (int) $request->user()->id,
            expiryDate: $validated['expiry_date'] ?? null,
        );

        return response()->json([
            'message' => 'Documento cargado exitosamente.',
            'data' => [
                'id' => $documento->id,
                'trabajador_id' => $documento->trabajador_id,
                'tipo_documento_id' => $documento->tipo_documento_id,
                'archivo_nombre_original' => $documento->archivo_nombre_original,
                'fecha_vencimiento' => $documento->fecha_vencimiento?->toDateString(),
            ],
        ], 201);
    }

    /**
     * Download an uploaded documento for trabajador.
     */
    public function download(DocumentoTrabajador $documentoTrabajador)
    {
        $this->authorizeDocumentoTrabajadorAccess(auth()->user(), $documentoTrabajador);

        return Storage::disk('private')->download(
            $documentoTrabajador->archivo_ruta,
            $documentoTrabajador->archivo_nombre_original,
        );
    }

    /**
     * Preview an uploaded documento for trabajador.
     */
    public function preview(DocumentoTrabajador $documentoTrabajador): StreamedResponse
    {
        $this->authorizeDocumentoTrabajadorAccess(auth()->user(), $documentoTrabajador);

        $disk = Storage::disk('private');
        if (! $disk->exists($documentoTrabajador->archivo_ruta)) {
            abort(404);
        }

        $stream = $disk->readStream($documentoTrabajador->archivo_ruta);
        if ($stream === false) {
            abort(404);
        }

        $fileName = str_replace('"', '', $documentoTrabajador->archivo_nombre_original);
        $mimeType = $disk->mimeType($documentoTrabajador->archivo_ruta) ?: 'application/octet-stream';

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

    private function authorizeTrabajadorAccess(User $user, Trabajador $trabajador): void
    {
        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }
    }

    private function authorizeDocumentoTrabajadorAccess(User $user, DocumentoTrabajador $documentoTrabajador): void
    {
        $documentoTrabajador->loadMissing('trabajador:id,contratista_id');

        if (! $documentoTrabajador->trabajador) {
            abort(404);
        }

        if (! $user->isAdmin() && $documentoTrabajador->trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }
    }

    private function storeDocumentoTrabajador(
        Trabajador $trabajador,
        int $tipoDocumentoId,
        UploadedFile $file,
        int $cargadoPor,
        ?string $expiryDate
    ): DocumentoTrabajador {
        $tipoDocumento = TipoDocumento::query()
            ->active()
            ->where('es_documento_trabajador', true)
            ->findOrFail($tipoDocumentoId);

        $tipoFaenaIds = $trabajador->faenas()
            ->wherePivotNull('fecha_desasignacion')
            ->pluck('faenas.tipo_faena_id')
            ->filter()
            ->unique();

        if ($tipoFaenaIds->isEmpty()) {
            throw ValidationException::withMessages([
                'tipo_documento_id' => 'El trabajador no tiene una faena activa con tipo definido. Debe asignarlo a una faena activa para poder cargar documentos.',
            ]);
        }

        $isTipoDocumentoPermitido = $tipoDocumento->tiposFaena()
            ->whereIn('tipo_faenas.id', $tipoFaenaIds)
            ->exists();

        if (! $isTipoDocumentoPermitido) {
            throw ValidationException::withMessages([
                'tipo_documento_id' => 'El tipo de documento no aplica para el tipo de faena del trabajador.',
            ]);
        }

        if ($this->hasTrabajadorDocumentoDuplicate(
            trabajadorId: $trabajador->id,
            tipoDocumento: $tipoDocumento,
        )) {
            throw ValidationException::withMessages([
                'tipo_documento_id' => 'Este tipo de documento ya fue cargado para el trabajador.',
            ]);
        }

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

        $path = $file->store(
            'documentos-trabajadores/'.$trabajador->id.'/'.$tipoDocumento->codigo,
            'private',
        );

        return DocumentoTrabajador::query()->create([
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'origen' => 'carga_manual',
            'archivo_nombre_original' => $file->getClientOriginalName(),
            'archivo_ruta' => $path,
            'archivo_tamano_kb' => (int) round($fileSizeKb),
            'fecha_vencimiento' => $expiryDate,
            'cargado_por' => $cargadoPor,
        ]);
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

    private function hasTrabajadorDocumentoDuplicate(
        string $trabajadorId,
        TipoDocumento $tipoDocumento,
    ): bool {
        if ($tipoDocumento->permite_multiples_en_mes) {
            return false;
        }

        return DocumentoTrabajador::query()
            ->where('trabajador_id', $trabajadorId)
            ->where('tipo_documento_id', $tipoDocumento->id)
            ->where('origen', 'carga_manual')
            ->exists();
    }
}
