<?php

namespace App\Http\Controllers;

use App\Actions\Documentos\GenerateSignedWorkerPdfAction;
use App\Http\Requests\DocumentoTrabajadorFirmaRequest;
use App\Http\Requests\PlantillaDocumentoTrabajadorRequest;
use App\Models\DocumentoTrabajador;
use App\Models\PlantillaDocumentoTrabajador;
use App\Models\Trabajador;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocumentoTrabajadorFirmaController extends Controller
{
    /**
     * Display available templates and digital signatures history.
     */
    public function index(Trabajador $trabajador): Response
    {
        $trabajador->loadMissing('contratista:id,rut,razon_social,nombre_fantasia');

        $this->authorizeSignerAccess($trabajador);

        $plantillas = $this->availableTemplatesForTrabajador($trabajador)
            ->map(fn (PlantillaDocumentoTrabajador $plantilla) => [
                'id' => $plantilla->id,
                'nombre' => $plantilla->nombre,
                'tipo_documento_nombre' => $plantilla->tipoDocumento?->nombre,
                'tipo_documento_codigo' => $plantilla->tipoDocumento?->codigo,
                'updated_at' => $plantilla->updated_at?->format('d/m/Y H:i'),
            ])
            ->values()
            ->all();

        $documentosFirmados = DocumentoTrabajador::query()
            ->where('trabajador_id', $trabajador->id)
            ->where('origen', 'firma_digital')
            ->with([
                'tipoDocumento:id,nombre,codigo',
                'plantillaDocumentoTrabajador:id,nombre',
                'firmadoPor:id,name',
            ])
            ->orderByDesc('firmado_at')
            ->limit(50)
            ->get()
            ->map(fn (DocumentoTrabajador $documento) => [
                'id' => $documento->id,
                'tipo_documento_nombre' => $documento->tipoDocumento?->nombre,
                'tipo_documento_codigo' => $documento->tipoDocumento?->codigo,
                'plantilla_nombre' => $documento->plantillaDocumentoTrabajador?->nombre,
                'firmado_por_nombre' => $documento->firmadoPor?->name,
                'firmado_at' => $documento->firmado_at?->format('d/m/Y H:i:s'),
                'preview_url' => route('documentos-trabajadores.preview', $documento),
                'download_url' => route('documentos-trabajadores.download', $documento),
            ])
            ->values()
            ->all();

        return Inertia::render('trabajadores/firmas-documentos/index', [
            'trabajador' => [
                'id' => $trabajador->id,
                'documento' => $trabajador->documento,
                'nombre_completo' => $trabajador->nombre_completo,
                'contratista_nombre' => $trabajador->contratista?->nombre_fantasia ?: $trabajador->contratista?->razon_social,
            ],
            'plantillas' => $plantillas,
            'documentosFirmados' => $documentosFirmados,
        ]);
    }

    /**
     * Show the digital signature screen for a selected template.
     */
    public function create(
        Trabajador $trabajador,
        PlantillaDocumentoTrabajador $plantillaDocumentoTrabajador,
    ): Response {
        $trabajador->loadMissing('contratista:id,rut,razon_social,nombre_fantasia');
        $plantillaDocumentoTrabajador->loadMissing('tipoDocumento:id,nombre,codigo');

        $this->authorizeSignerAccess($trabajador);
        $this->ensureTemplateAvailableForTrabajador($trabajador, $plantillaDocumentoTrabajador);

        $previewDate = now();
        $variables = $this->buildTemplateVariables($trabajador, $previewDate);
        $renderedHtml = $this->renderTemplateHtml($plantillaDocumentoTrabajador->contenido_html, $variables);

        return Inertia::render('trabajadores/firmas-documentos/create', [
            'trabajador' => [
                'id' => $trabajador->id,
                'documento' => $trabajador->documento,
                'nombre_completo' => $trabajador->nombre_completo,
            ],
            'plantilla' => [
                'id' => $plantillaDocumentoTrabajador->id,
                'nombre' => $plantillaDocumentoTrabajador->nombre,
                'tipo_documento_nombre' => $plantillaDocumentoTrabajador->tipoDocumento?->nombre,
                'tipo_documento_codigo' => $plantillaDocumentoTrabajador->tipoDocumento?->codigo,
                'rendered_html' => $renderedHtml,
                'fuente_tamano' => $plantillaDocumentoTrabajador->fuente_tamano,
                'color_texto' => $plantillaDocumentoTrabajador->color_texto,
                'formato_papel' => $plantillaDocumentoTrabajador->dompdfPaperSize(),
                'font_family' => $plantillaDocumentoTrabajador->cssFontFamily(),
            ],
            'availableVariables' => PlantillaDocumentoTrabajadorRequest::allowedVariablesForDisplay(),
        ]);
    }

    /**
     * Persist a digitally signed documento for the selected worker and template.
     */
    public function store(
        DocumentoTrabajadorFirmaRequest $request,
        Trabajador $trabajador,
        PlantillaDocumentoTrabajador $plantillaDocumentoTrabajador,
        GenerateSignedWorkerPdfAction $generateSignedWorkerPdfAction,
    ): RedirectResponse {
        $trabajador->loadMissing('contratista:id,rut,razon_social,nombre_fantasia');
        $plantillaDocumentoTrabajador->loadMissing('tipoDocumento:id,nombre,codigo,es_documento_trabajador,activo');

        $this->authorizeSignerAccess($trabajador);
        $this->ensureTemplateAvailableForTrabajador($trabajador, $plantillaDocumentoTrabajador);

        $signatureDataUrl = $request->validated('signature_data_url');
        $signedAt = now();
        $variables = $this->buildTemplateVariables($trabajador, $signedAt);
        $renderedHtml = $this->renderTemplateHtml($plantillaDocumentoTrabajador->contenido_html, $variables);

        $firmaImagenRuta = $this->storeSignatureImage(
            signatureDataUrl: $signatureDataUrl,
            trabajadorId: $trabajador->id,
            plantillaId: $plantillaDocumentoTrabajador->id,
            signedAt: $signedAt,
        );

        $pdfResult = $generateSignedWorkerPdfAction->handle(
            trabajador: $trabajador,
            tipoDocumento: $plantillaDocumentoTrabajador->tipoDocumento,
            plantilla: $plantillaDocumentoTrabajador,
            renderedHtml: $renderedHtml,
            signatureDataUrl: $signatureDataUrl,
            variables: $variables,
            signedAt: $signedAt,
        );

        DocumentoTrabajador::query()->create([
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $plantillaDocumentoTrabajador->tipo_documento_id,
            'origen' => 'firma_digital',
            'plantilla_documento_trabajador_id' => $plantillaDocumentoTrabajador->id,
            'archivo_nombre_original' => $pdfResult['file_name'],
            'archivo_ruta' => $pdfResult['path'],
            'contenido_html_snapshot' => $renderedHtml,
            'variables_snapshot' => $variables,
            'firma_imagen_ruta' => $firmaImagenRuta,
            'archivo_tamano_kb' => $pdfResult['size_kb'],
            'fecha_vencimiento' => null,
            'cargado_por' => $request->user()->id,
            'firmado_por' => $request->user()->id,
            'firmado_at' => $signedAt,
            'firma_ip' => $request->ip(),
            'firma_user_agent' => $request->userAgent(),
            'contenido_hash' => $pdfResult['hash'],
        ]);

        return redirect()
            ->route('trabajadores.firmas.index', $trabajador)
            ->with('success', 'Documento firmado y almacenado correctamente.');
    }

    private function authorizeSignerAccess(Trabajador $trabajador): void
    {
        $user = request()->user();
        $canSign = $user->isAdmin() || $user->isSupervisor();

        if (! $canSign) {
            abort(403);
        }

        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }
    }

    private function ensureTemplateAvailableForTrabajador(
        Trabajador $trabajador,
        PlantillaDocumentoTrabajador $plantillaDocumentoTrabajador,
    ): void {
        $templateIsAvailable = $this->availableTemplatesForTrabajador($trabajador)
            ->pluck('id')
            ->contains($plantillaDocumentoTrabajador->id);

        if (! $templateIsAvailable) {
            abort(404);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, PlantillaDocumentoTrabajador>
     */
    private function availableTemplatesForTrabajador(Trabajador $trabajador)
    {
        $tipoFaenaIds = $trabajador->faenas()
            ->wherePivotNull('fecha_desasignacion')
            ->pluck('faenas.tipo_faena_id')
            ->filter()
            ->unique()
            ->values();

        if ($tipoFaenaIds->isEmpty()) {
            return collect();
        }

        return PlantillaDocumentoTrabajador::query()
            ->active()
            ->with('tipoDocumento:id,nombre,codigo')
            ->whereHas('tipoDocumento', function ($query) use ($tipoFaenaIds) {
                $query->where('es_documento_trabajador', true)
                    ->where('activo', true)
                    ->whereHas('tiposFaena', function ($tipoFaenaQuery) use ($tipoFaenaIds) {
                        $tipoFaenaQuery->whereIn('tipo_faenas.id', $tipoFaenaIds);
                    });
            })
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function buildTemplateVariables(Trabajador $trabajador, CarbonInterface $signedAt): array
    {
        $contratista = $trabajador->contratista;
        $contratistaNombre = $contratista?->nombre_fantasia ?: $contratista?->razon_social;

        return [
            'fecha' => $signedAt->format('d/m/Y'),
            'trabajador_nombre' => $trabajador->nombre_completo,
            'trabajador_rut' => $trabajador->documento,
            'contratista_nombre' => $contratistaNombre ?? '',
            'contratista_rut' => $contratista?->rut ?? '',
            'fecha_firma' => $signedAt->format('d/m/Y H:i:s'),
        ];
    }

    private function renderTemplateHtml(string $templateContent, array $variables): string
    {
        $rendered = preg_replace_callback(
            '/{{\s*([a-z_]+)\s*}}/i',
            static function (array $matches) use ($variables): string {
                $variableName = strtolower($matches[1]);

                return e($variables[$variableName] ?? $matches[0]);
            },
            $templateContent,
        );

        if (! is_string($rendered)) {
            $rendered = $templateContent;
        }

        if ($this->containsHtmlTags($rendered)) {
            return $rendered;
        }

        return (string) Str::markdown($rendered, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    private function containsHtmlTags(string $content): bool
    {
        return preg_match('/<([a-z][a-z0-9]*)\b[^>]*>/i', $content) === 1;
    }

    private function storeSignatureImage(
        string $signatureDataUrl,
        string $trabajadorId,
        int $plantillaId,
        CarbonInterface $signedAt,
    ): string {
        $encodedImage = substr($signatureDataUrl, strlen('data:image/png;base64,'));
        $decodedImage = base64_decode($encodedImage, true);

        if ($decodedImage === false) {
            abort(422, 'No fue posible procesar la firma digital.');
        }

        $fileName = 'firma_'.$plantillaId.'_'.$signedAt->format('Ymd_His_u').'.png';
        $path = 'documentos-trabajadores/'.$trabajadorId.'/firmas/'.$fileName;

        Storage::disk('private')->put($path, $decodedImage);

        return $path;
    }
}
