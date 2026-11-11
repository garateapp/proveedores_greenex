<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrabajadorImportRequest;
use App\Models\TarjetaQr;
use App\Models\TarjetaQrAsignacion;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TrabajadorImportController extends Controller
{
    /**
     * Import trabajadores from CSV file.
     */
    public function import(TrabajadorImportRequest $request)
    {
        $user = $request->user();

        $contratistaId = $user->isAdmin()
            ? (int) $request->input('contratista_id')
            : $user->contratista_id;

        if ($contratistaId === null) {
            return back()->withErrors([
                'contratista_id' => 'No se pudo determinar el contratista para la importación.',
            ]);
        }

        $faenaId = $request->input('faena_id');

        if ($faenaId && ! $user->isAdmin()) {
            $faenaBelongsToContratista = \App\Models\Faena::query()
                ->active()
                ->where('id', $faenaId)
                ->whereHas('contratistas', fn ($q) => $q->where('contratistas.id', $contratistaId))
                ->exists();

            if (! $faenaBelongsToContratista) {
                return back()->withErrors([
                    'faena_id' => 'La faena seleccionada no está disponible para su contratista.',
                ]);
            }
        }

        $file = $request->file('file');

        try {
            $data = $this->parseFile($file);

            if ($data === []) {
                return back()->withErrors([
                    'file' => 'El archivo no contiene filas válidas para importar.',
                ]);
            }

            $hasQrColumn = isset($data[0]['codigo_qr']);

            if ($hasQrColumn && ! $user->isAdmin()) {
                abort(403, 'Solo los administradores pueden asignar tarjetas QR durante la importación.');
            }

            $errors = [];
            $tarjetaQrErrors = [];
            $imported = 0;
            $skipped = 0;

            DB::transaction(function () use ($data, $contratistaId, $faenaId, $hasQrColumn, $user, &$errors, &$tarjetaQrErrors, &$imported, &$skipped) {
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
                    $trabajador = Trabajador::create([
                        'id' => $row['id'],
                        'documento' => $row['documento'],
                        'nombre' => $row['nombre'],
                        'apellido' => $row['apellido'],
                        'contratista_id' => $contratistaId,
                        'estado' => 'activo',
                        'fecha_ingreso' => now(),
                    ]);

                    if ($faenaId) {
                        $trabajador->faenas()->attach((int) $faenaId, [
                            'fecha_asignacion' => now(),
                            'fecha_desasignacion' => null,
                        ]);
                    }

                    // Assign tarjeta QR if column exists and value present
                    if ($hasQrColumn && ! empty($row['codigo_qr'])) {
                        $this->assignTarjetaQr($trabajador, $row['codigo_qr'], $user->id, $rowNumber, $tarjetaQrErrors);
                    }

                    $imported++;
                }
            });

            if ($imported === 0) {
                return back()
                    ->withErrors([
                        'file' => 'No se pudo importar ningún trabajador. Revise el archivo y corrija las filas omitidas.',
                    ])
                    ->with('import_errors', $errors)
                    ->with('tarjeta_qr_errors', $tarjetaQrErrors);
            }

            $message = "Importación completada: {$imported} trabajadores importados";
            if ($skipped > 0) {
                $message .= ", {$skipped} omitidos";
            }

            return back()->with([
                'success' => $message,
                'import_errors' => $errors,
                'tarjeta_qr_errors' => $tarjetaQrErrors,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Error al procesar el archivo: '.$e->getMessage()]);
        }
    }

    /**
     * Assign a tarjeta QR to a trabajador.
     *
     * @param  array<string, mixed>  $tarjetaQrErrors
     */
    private function assignTarjetaQr(Trabajador $trabajador, string $codigoQr, int $asignadaPor, int $rowNumber, array &$tarjetaQrErrors): void
    {
        $tarjeta = TarjetaQr::where('codigo_qr', $codigoQr)->first();

        if (! $tarjeta) {
            $tarjetaQrErrors[] = [
                'fila' => $rowNumber,
                'codigo_qr' => $codigoQr,
                'trabajador' => $trabajador->id,
                'motivo' => 'Tarjeta QR no encontrada',
            ];

            return;
        }

        if ($tarjeta->estado !== 'activa') {
            $tarjetaQrErrors[] = [
                'fila' => $rowNumber,
                'codigo_qr' => $codigoQr,
                'trabajador' => $trabajador->id,
                'motivo' => 'Tarjeta QR no está activa (estado: '.$tarjeta->estado.')',
            ];

            return;
        }

        if ($tarjeta->perfil !== \App\Enums\PerfilTarjetaQr::Comensal) {
            $tarjetaQrErrors[] = [
                'fila' => $rowNumber,
                'codigo_qr' => $codigoQr,
                'trabajador' => $trabajador->id,
                'motivo' => 'Perfil de tarjeta no válido para asignación a trabajador ('.$tarjeta->perfil->value.')',
            ];

            return;
        }

        $asignacionActiva = $tarjeta->asignacionActiva;

        if ($asignacionActiva) {
            $tarjetaQrErrors[] = [
                'fila' => $rowNumber,
                'codigo_qr' => $codigoQr,
                'trabajador' => $trabajador->id,
                'motivo' => 'Tarjeta QR ya asignada a otro trabajador',
            ];

            return;
        }

        TarjetaQrAsignacion::create([
            'tarjeta_qr_id' => $tarjeta->id,
            'trabajador_id' => $trabajador->id,
            'asignada_por' => $asignadaPor,
            'asignada_en' => now(),
            'observaciones' => 'Asignada por importación masiva de trabajadores',
        ]);
    }

    /**
     * Parse CSV file to array.
     */
    private function parseFile($file): array
    {
        $extension = $file->getClientOriginalExtension();

        if ($extension === 'csv') {
            return $this->parseCsv($file);
        }

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
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($header[0] ?? ''));
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
