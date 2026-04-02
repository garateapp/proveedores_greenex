<?php

namespace App\Actions\Documentos;

use App\Models\PlantillaDocumentoTrabajador;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateSignedWorkerPdfAction
{
    /**
     * @param  array<string, string>  $variables
     * @return array{path: string, file_name: string, size_kb: int, hash: string}
     */
    public function handle(
        Trabajador $trabajador,
        TipoDocumento $tipoDocumento,
        PlantillaDocumentoTrabajador $plantilla,
        string $renderedHtml,
        string $signatureDataUrl,
        array $variables,
        CarbonInterface $signedAt,
    ): array {
        $fontFamily = $plantilla->cssFontFamily();
        $fontSize = $this->sanitizeFontSize($plantilla->fuente_tamano);
        $textColor = $this->sanitizeColor($plantilla->color_texto);

        $pdfBinary = Pdf::loadView('pdf.documento-trabajador-firmado', [
            'plantilla' => $plantilla,
            'trabajador' => $trabajador,
            'tipoDocumento' => $tipoDocumento,
            'contenidoHtml' => $renderedHtml,
            'signatureDataUrl' => $signatureDataUrl,
            'variables' => $variables,
            'signedAt' => $signedAt,
            'fontFamily' => $fontFamily,
            'fontSize' => $fontSize,
            'textColor' => $textColor,
        ])->setPaper($plantilla->dompdfPaperSize())->output();

        $fileName = $this->buildFileName($tipoDocumento->codigo, $plantilla->nombre, $signedAt);
        $path = 'documentos-trabajadores/'.$trabajador->id.'/'.$tipoDocumento->codigo.'/firmados/'.$fileName;

        Storage::disk('private')->put($path, $pdfBinary);

        return [
            'path' => $path,
            'file_name' => $fileName,
            'size_kb' => (int) ceil(strlen($pdfBinary) / 1024),
            'hash' => hash('sha256', $pdfBinary),
        ];
    }

    private function buildFileName(string $tipoDocumentoCode, string $templateName, CarbonInterface $signedAt): string
    {
        $code = Str::slug($tipoDocumentoCode, '_');
        $template = Str::slug($templateName, '_');

        if ($code === '') {
            $code = 'documento';
        }

        if ($template === '') {
            $template = 'firmado';
        }

        return "{$code}_{$template}_".$signedAt->format('Ymd_His').'.pdf';
    }

    private function sanitizeFontSize(mixed $fontSize): int
    {
        if (! is_int($fontSize)) {
            return 12;
        }

        return max(9, min(18, $fontSize));
    }

    private function sanitizeColor(mixed $color): string
    {
        if (! is_string($color)) {
            return '#111827';
        }

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1 ? $color : '#111827';
    }
}
