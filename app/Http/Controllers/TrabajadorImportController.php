<?php

namespace App\Http\Controllers;

use App\Models\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TrabajadorImportController extends Controller
{
    /**
     * Import trabajadores from Excel/CSV file.
     */
    public function import(Request $request)
    {
        $user = $request->user();
        $rules = [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ];

        if ($user->isAdmin()) {
            $rules['contratista_id'] = ['required', 'integer', 'exists:contratistas,id'];
        }

        $request->validate($rules);

        $contratistaId = $user->isAdmin()
            ? (int) $request->input('contratista_id')
            : $user->contratista_id;

        if ($contratistaId === null) {
            return back()->withErrors([
                'contratista_id' => 'No se pudo determinar el contratista para la importación.',
            ]);
        }

        $file = $request->file('file');

        try {
            $data = $this->parseFile($file);

            $errors = [];
            $imported = 0;
            $skipped = 0;

            DB::transaction(function () use ($data, $contratistaId, &$errors, &$imported, &$skipped) {
                foreach ($data as $index => $row) {
                    $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header

                    // Validate row
                    $validator = Validator::make($row, [
                        'id' => ['required', 'string', 'regex:/^[0-9]{7,8}$/'],
                        'nombre' => ['required', 'string', 'max:255'],
                        'apellido' => ['required', 'string', 'max:255'],
                        'documento' => ['required', 'string', 'regex:/^[0-9]{7,8}-[0-9kK]$/'],
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Fila {$rowNumber}: ".implode(', ', $validator->errors()->all());
                        $skipped++;

                        continue;
                    }

                    // Validate RUT
                    if (! Trabajador::validateRut($row['documento'])) {
                        $errors[] = "Fila {$rowNumber}: RUT inválido ({$row['documento']})";
                        $skipped++;

                        continue;
                    }

                    // Check if ID matches documento
                    $expectedId = Trabajador::extractIdFromDocumento($row['documento']);
                    if ($row['id'] !== $expectedId) {
                        $errors[] = "Fila {$rowNumber}: El ID no corresponde al RUT";
                        $skipped++;

                        continue;
                    }

                    // Check if already exists
                    if (Trabajador::find($row['id'])) {
                        $errors[] = "Fila {$rowNumber}: Trabajador ya existe (RUT: {$row['documento']})";
                        $skipped++;

                        continue;
                    }

                    // Create trabajador
                    Trabajador::create([
                        'id' => $row['id'],
                        'documento' => $row['documento'],
                        'nombre' => $row['nombre'],
                        'apellido' => $row['apellido'],
                        'contratista_id' => $contratistaId,
                        'estado' => 'activo',
                        'fecha_ingreso' => now(),
                    ]);

                    $imported++;
                }
            });

            $message = "Importación completada: {$imported} trabajadores importados";
            if ($skipped > 0) {
                $message .= ", {$skipped} omitidos";
            }

            return back()->with([
                'success' => $message,
                'import_errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Error al procesar el archivo: '.$e->getMessage()]);
        }
    }

    /**
     * Parse Excel/CSV file to array.
     */
    private function parseFile($file): array
    {
        $extension = $file->getClientOriginalExtension();

        if ($extension === 'csv') {
            return $this->parseCsv($file);
        }

        // For Excel files, we'd use a library like PhpSpreadsheet
        // For now, we'll just support CSV
        throw new \Exception('Solo se soportan archivos CSV en esta versión');
    }

    /**
     * Parse CSV file.
     */
    private function parseCsv($file): array
    {
        $data = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \Exception('No se pudo abrir el archivo');
        }

        // Read header
        $header = fgetcsv($handle, 1000, ';');

        if ($header === false) {
            throw new \Exception('El archivo está vacío');
        }

        // Normalize header keys
        $header = array_map('strtolower', $header);
        $header = array_map('trim', $header);

        // Read data rows
        while (($row = fgetcsv($handle, 1000, ';')) !== false) {
            if (count($row) === count($header)) {
                $normalizedRow = array_map(
                    fn ($value) => is_string($value) ? trim($value) : $value,
                    $row,
                );
                $data[] = array_combine($header, $normalizedRow);
            }
        }

        fclose($handle);

        return $data;
    }

    /**
     * Download template file.
     */
    public function downloadTemplate()
    {
        $filename = 'plantilla_trabajadores.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $columns = ['id', 'nombre', 'apellido', 'documento'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write header
            fputcsv($file, $columns, ';');

            // Write example rows
            fputcsv($file, ['12345678', 'Juan', 'Pérez', '12345678-5'], ';');
            fputcsv($file, ['87654321', 'María', 'González', '87654321-3'], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
