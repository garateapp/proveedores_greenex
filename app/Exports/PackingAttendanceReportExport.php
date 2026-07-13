<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PackingAttendanceReportExport
{
    private const HEADER_FILL = '1F4E79';

    private const HEADER_FONT = 'FFFFFF';

    private const ACCENT_FILL = 'D6E4F0';

    private const COLORS = [
        'app_control' => 'C6EFCE',
        'app_sin_control' => 'FFEB9C',
        'control_sin_app' => 'FFC7CE',
    ];

    public function __construct(
        private array $report,
        private string $date,
    ) {}

    public function build(): Xlsx
    {
        $spreadsheet = new Spreadsheet;

        $this->buildResumenSheet($spreadsheet);
        $this->buildDetalleSheet($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);

        return new Xlsx($spreadsheet);
    }

    private function buildResumenSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen Ejecutivo');

        $sheet->setCellValue('A1', "Reporte Asistencia Packing - {$this->date}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:F1');

        $row = 3;

        $sheet->setCellValue("A{$row}", 'Total');
        $sheet->setCellValue("B{$row}", $this->report['summary']['total']);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", 'App + control');
        $sheet->setCellValue("B{$row}", $this->report['summary']['app_control']);
        $this->applyCellFill($sheet, "B{$row}", self::COLORS['app_control']);
        $row++;

        $sheet->setCellValue("A{$row}", 'App sin control');
        $sheet->setCellValue("B{$row}", $this->report['summary']['app_sin_control']);
        $this->applyCellFill($sheet, "B{$row}", self::COLORS['app_sin_control']);
        $row++;

        $sheet->setCellValue("A{$row}", 'Control sin app');
        $sheet->setCellValue("B{$row}", $this->report['summary']['control_sin_app']);
        $this->applyCellFill($sheet, "B{$row}", self::COLORS['control_sin_app']);
        $row++;

        $sheet->setCellValue("A{$row}", 'Marcaciones multiples');
        $sheet->setCellValue("B{$row}", $this->report['summary']['marcaciones_multiples']);
        $row += 2;

        $this->writeTotalsTable(
            sheet: $sheet,
            startRow: $row,
            title: 'Resumen por turno',
            headers: ['Turno', 'Horario', 'Total', 'App+Control', 'App sin control', 'Control sin app', 'Multiples'],
            rows: $this->report['totals_by_turno']->map(fn (array $item): array => [
                $item['turno_nombre'],
                $item['turno_inicio']?->format('H:i').' - '.$item['turno_fin']?->format('H:i'),
                $item['total'],
                $item['app_control'],
                $item['app_sin_control'],
                $item['control_sin_app'],
                $item['marcaciones_multiples'],
            ])->all(),
        );

        $row = $sheet->getHighestRow() + 2;

        $this->writeTotalsTable(
            sheet: $sheet,
            startRow: $row,
            title: 'Resumen por contratista/departamento',
            headers: ['Grupo', 'Total', 'App+Control', 'App sin control', 'Control sin app', 'Multiples'],
            rows: $this->report['totals_by_group']->map(fn (array $item): array => [
                $item['group_label'],
                $item['total'],
                $item['app_control'],
                $item['app_sin_control'],
                $item['control_sin_app'],
                $item['marcaciones_multiples'],
            ])->all(),
        );

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
    }

    private function buildDetalleSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Detalle');

        $headers = [
            'Turno',
            'Horario turno',
            'Trabajador',
            'Documento',
            'Contratista',
            'Depto. control',
            'Primera entrada',
            'Ultima salida',
            'Estado',
            'Ubicación',
            'Marcaciones',
        ];

        $headerRow = 1;

        foreach ($headers as $colIndex => $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue("{$col}{$headerRow}", $header);
        }

        $sheet->getStyle("A{$headerRow}:K{$headerRow}")
            ->getFont()
            ->setBold(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::HEADER_FONT));
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::HEADER_FILL));
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $currentRow = 2;

        foreach ($this->report['rows'] as $rowData) {
            $horario = '';
            if ($rowData['turno_inicio'] instanceof \Illuminate\Support\Carbon) {
                $horario = $rowData['turno_inicio']->format('H:i').' - '.$rowData['turno_fin']?->format('H:i');
            }

            $marcacionesText = $rowData['marcaciones']
                ->map(fn (array $m): string => sprintf(
                    '%s @ %s',
                    $m['marcado_en']?->format('H:i'),
                    $m['ubicacion'] ?? '-',
                ))
                ->implode("\n");

            $sheet->setCellValue("A{$currentRow}", $rowData['turno_nombre']);
            $sheet->setCellValue("B{$currentRow}", $horario);
            $sheet->setCellValue("C{$currentRow}", $rowData['nombre']);
            $sheet->setCellValue("D{$currentRow}", $rowData['documento']);
            $sheet->setCellValue("E{$currentRow}", $rowData['contratista']);
            $sheet->setCellValue("F{$currentRow}", $rowData['departamento_control']);
            $sheet->setCellValue("G{$currentRow}", $rowData['primera_entrada']?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("H{$currentRow}", $rowData['ultima_salida']?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("I{$currentRow}", $rowData['status_label']);

            $ubicacionesText = implode(', ', $rowData['ubicaciones'] ?? []);
            $sheet->setCellValue("J{$currentRow}", $ubicacionesText !== '' ? $ubicacionesText : null);

            if ($marcacionesText !== '') {
                $sheet->setCellValue("K{$currentRow}", $marcacionesText);
                $sheet->getStyle("K{$currentRow}")->getAlignment()->setWrapText(true);
            }

            $statusColor = self::COLORS[$rowData['status']] ?? null;

            if ($statusColor !== null) {
                $this->applyCellFill($sheet, "I{$currentRow}", $statusColor);
            }

            $currentRow++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getColumnDimension('K')->setWidth(40);
        $sheet->getStyle("A1:K{$currentRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function writeTotalsTable(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $startRow, string $title, array $headers, array $rows): void
    {
        $sheet->setCellValue("A{$startRow}", $title);
        $sheet->getStyle("A{$startRow}")->getFont()->setBold(true)->setSize(12);
        $headerRow = $startRow + 1;

        foreach ($headers as $colIndex => $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue("{$col}{$headerRow}", $header);
        }

        $lastCol = chr(65 + count($headers) - 1);
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")
            ->getFont()
            ->setBold(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::HEADER_FONT));
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::HEADER_FILL));

        $currentRow = $headerRow + 1;

        foreach ($rows as $rowData) {
            foreach ($rowData as $colIndex => $value) {
                $col = chr(65 + $colIndex);
                $sheet->setCellValue("{$col}{$currentRow}", $value);
            }

            $currentRow++;
        }

        $sheet->getStyle("A{$headerRow}:{$lastCol}".($currentRow - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function applyCellFill(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $cell, string $color): void
    {
        $sheet->getStyle($cell)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color));
    }
}
