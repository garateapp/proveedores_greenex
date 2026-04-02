<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $plantilla->nombre }}</title>
    @php
        $isA4 = $plantilla->dompdfPaperSize() === 'a4';
        $pageCssSize = $isA4 ? 'A4' : 'Letter';
        $pageTopBottomMargin = $isA4 ? 16 : 14;
        $pageLeftRightMargin = $isA4 ? 14 : 12;
    @endphp
    <style>
        @page {
            size: {{ $pageCssSize }} portrait;
            margin: {{ $pageTopBottomMargin }}mm {{ $pageLeftRightMargin }}mm;
        }
        * {
            box-sizing: border-box;
        }
        html,
        body {
            width: 100%;
        }
        body {
            font-family: "{{ $fontFamily }}", sans-serif;
            color: {{ $textColor }};
            font-size: {{ $fontSize }}px;
            line-height: 1.5;
            margin: 0;
            overflow: hidden;
        }
        .page {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }
        .header {
            margin-bottom: 18px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 10px;
        }
        .title {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 2px;
        }
        .subtitle {
            font-size: 11px;
            color: {{ $textColor }};
            margin: 0;
        }
        .content {
            margin-top: 14px;
            width: 100%;
            max-width: 100%;
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
        }
        .content * {
            max-width: 100% !important;
        }
        .content p,
        .content li,
        .content div,
        .content span {
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal !important;
        }
        .content pre,
        .content code {
            white-space: pre-wrap !important;
            overflow-wrap: break-word !important;
            word-wrap: break-word !important;
            word-break: break-all !important;
        }
        .content table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .content th,
        .content td {
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal !important;
            vertical-align: top;
        }
        .content img,
        .content svg,
        .content canvas {
            max-width: 100% !important;
            width: auto !important;
            height: auto !important;
        }
        .signature-wrapper {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px solid #d1d5db;
        }
        .signature-image {
            width: 210px;
            max-width: 100%;
            height: 80px;
            object-fit: contain;
            border-bottom: 1px solid {{ $textColor }};
            display: block;
            margin-bottom: 8px;
        }
        .meta {
            color: {{ $textColor }};
            font-size: 11px;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <p class="title">{{ $plantilla->nombre }}</p>
            <p class="subtitle">
                Tipo de documento: {{ $tipoDocumento->nombre }} ({{ $tipoDocumento->codigo }})
            </p>
        </div>

        <div class="content">
            {!! $contenidoHtml !!}
        </div>

        <div class="signature-wrapper">
            <img class="signature-image" src="{{ $signatureDataUrl }}" alt="Firma del trabajador" />
            <p class="meta">Firma del trabajador: {{ $trabajador->nombre_completo }} ({{ $trabajador->documento }})</p>
            <p class="meta">Fecha de firma: {{ $signedAt->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
