<?php

namespace App\Actions\Asistencia;

use RuntimeException;
use Smalot\PdfParser\Parser;

class ExtractCotizacionesFonasaAction
{
    /**
     * @var string
     */
    private const ROW_PATTERN = '/^\s*(\d+)\s+(\d{1,2}\.\d{3}\.\d{3}-[\dkK])(.*?)\t(\d{1,2})\s+(?:AFP|IPS)\b/iu';

    /**
     * @var string
     */
    private const PERIOD_PATTERN = '/PERIODO DE REMUNERACI[ÓO]N\s+(\d{1,2})\s+(\d{4})/u';

    public function __construct(private readonly Parser $parser) {}

    /**
     * @return array{
     *     periodo: array{mes: int|null, ano: int|null},
     *     rows: list<array{
     *         numero: int,
     *         rut: string,
     *         apellido_paterno: string,
     *         apellido_materno: string,
     *         nombres: string,
     *         dias_trabajados: int
     *     }>
     * }
     */
    public function extractFromPath(string $pdfPath): array
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException('No se encontró el archivo PDF a procesar.');
        }

        try {
            $pdf = $this->parser->parseFile($pdfPath);
        } catch (\Throwable $exception) {
            throw new RuntimeException('El archivo no pudo ser interpretado como un PDF válido.', previous: $exception);
        }

        $periodMonth = null;
        $periodYear = null;
        $rows = [];

        foreach ($pdf->getPages() as $page) {
            $text = $page->getText();

            if ($periodMonth === null && $periodYear === null && preg_match(self::PERIOD_PATTERN, $text, $periodMatch)) {
                $periodMonth = (int) $periodMatch[1];
                $periodYear = (int) $periodMatch[2];
            }

            if (! str_contains($text, 'ANEXOS: Detalle de Cotizaciones')) {
                continue;
            }

            $lines = preg_split('/\R/u', $text) ?: [];

            foreach ($lines as $line) {
                $row = $this->parseRowLine($line);

                if ($row === null) {
                    continue;
                }

                $rows[] = $row;
            }
        }

        usort($rows, static fn (array $left, array $right): int => $left['numero'] <=> $right['numero']);

        return [
            'periodo' => [
                'mes' => $periodMonth,
                'ano' => $periodYear,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     numero: int,
     *     rut: string,
     *     apellido_paterno: string,
     *     apellido_materno: string,
     *     nombres: string,
     *     dias_trabajados: int
     * }|null
     */
    private function parseRowLine(string $line): ?array
    {
        if (! preg_match(self::ROW_PATTERN, $line, $match)) {
            return null;
        }

        $personData = $this->extractPersonData($match[3]);

        if ($personData === null) {
            return null;
        }

        return [
            'numero' => (int) $match[1],
            'rut' => mb_strtoupper(trim($match[2])),
            'apellido_paterno' => $personData['apellido_paterno'],
            'apellido_materno' => $personData['apellido_materno'],
            'nombres' => $personData['nombres'],
            'dias_trabajados' => (int) $match[4],
        ];
    }

    /**
     * @return array{apellido_paterno: string, apellido_materno: string, nombres: string}|null
     */
    private function extractPersonData(string $personColumns): ?array
    {
        $normalizedColumns = trim($personColumns);
        $segments = str_contains($normalizedColumns, "\t")
            ? preg_split('/\t+/u', $normalizedColumns)
            : preg_split('/\s{2,}/u', $normalizedColumns);

        if (! is_array($segments)) {
            return null;
        }

        $segments = array_values(array_filter(
            array_map(
                static fn ($value): string => preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '',
                $segments
            ),
            static fn (string $value): bool => $value !== ''
        ));

        if (count($segments) < 2) {
            return null;
        }

        if (count($segments) === 2) {
            return [
                'apellido_paterno' => $segments[0],
                'apellido_materno' => '',
                'nombres' => $segments[1],
            ];
        }

        return [
            'apellido_paterno' => $segments[0],
            'apellido_materno' => $segments[1],
            'nombres' => implode(' ', array_slice($segments, 2)),
        ];
    }
}
