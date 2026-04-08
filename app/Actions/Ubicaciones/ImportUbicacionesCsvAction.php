<?php

namespace App\Actions\Ubicaciones;

use App\Models\Ubicacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportUbicacionesCsvAction
{
    /**
     * @return array{imported:int}
     */
    public function execute(UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'El archivo no contiene filas para importar.',
            ]);
        }

        $this->validateRows($rows);

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $padreId = null;

                if ($row['tipo'] === 'secundaria') {
                    $padreId = Ubicacion::query()
                        ->where('codigo', $row['padre_codigo'])
                        ->value('id');
                }

                Ubicacion::query()->create([
                    'padre_id' => $padreId,
                    'nombre' => $row['nombre'],
                    'codigo' => $row['codigo'],
                    'descripcion' => $row['descripcion'] !== '' ? $row['descripcion'] : null,
                    'tipo' => $row['tipo'],
                    'orden' => (int) ($row['orden'] !== '' ? $row['orden'] : 0),
                    'activa' => true,
                ]);
            }
        });

        return [
            'imported' => count($rows),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo abrir el archivo CSV.',
            ]);
        }

        $header = fgetcsv($handle, 0, ';');

        if ($header === false) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => 'El archivo CSV está vacío.',
            ]);
        }

        $normalizedHeader = array_map(
            static fn (mixed $value): string => trim(mb_strtolower((string) $value)),
            $header,
        );

        if (isset($normalizedHeader[0])) {
            $normalizedHeader[0] = str_replace("\xEF\xBB\xBF", '', $normalizedHeader[0]);
        }

        $expectedHeader = ['codigo', 'nombre', 'descripcion', 'tipo', 'padre_codigo', 'orden'];

        if ($normalizedHeader !== $expectedHeader) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => 'La plantilla CSV no es válida. Descargue la plantilla oficial e intente nuevamente.',
            ]);
        }

        $rows = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $normalizedRow = array_map(
                static fn (mixed $value): string => trim((string) $value),
                $row,
            );

            if (count(array_filter($normalizedRow, static fn (string $value): bool => $value !== '')) === 0) {
                continue;
            }

            if (count($normalizedRow) !== count($expectedHeader)) {
                fclose($handle);

                throw ValidationException::withMessages([
                    'file' => 'Una o más filas no tienen el número de columnas esperado.',
                ]);
            }

            $rows[] = array_combine($expectedHeader, $normalizedRow);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function validateRows(array $rows): void
    {
        $existingCodes = Ubicacion::query()
            ->pluck('codigo')
            ->map(static fn (string $codigo): string => mb_strtolower($codigo))
            ->all();

        $existingLookup = array_fill_keys($existingCodes, true);
        $csvLookup = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $validator = Validator::make($row, [
                'codigo' => ['required', 'string', 'max:255'],
                'nombre' => ['required', 'string', 'max:255'],
                'descripcion' => ['nullable', 'string'],
                'tipo' => ['required', 'in:principal,secundaria'],
                'padre_codigo' => ['nullable', 'string', 'max:255'],
                'orden' => ['nullable', 'integer', 'min:0'],
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => "Fila {$rowNumber}: ".implode(', ', $validator->errors()->all()),
                ]);
            }

            $codigo = mb_strtolower($row['codigo']);

            if (isset($existingLookup[$codigo])) {
                throw ValidationException::withMessages([
                    'file' => "Fila {$rowNumber}: el código {$row['codigo']} ya existe.",
                ]);
            }

            if (isset($csvLookup[$codigo])) {
                throw ValidationException::withMessages([
                    'file' => "Fila {$rowNumber}: el código {$row['codigo']} está repetido dentro del archivo.",
                ]);
            }

            if ($row['tipo'] === 'principal' && $row['padre_codigo'] !== '') {
                throw ValidationException::withMessages([
                    'file' => "Fila {$rowNumber}: una ubicación principal no puede tener padre_codigo.",
                ]);
            }

            if ($row['tipo'] === 'secundaria' && $row['padre_codigo'] === '') {
                throw ValidationException::withMessages([
                    'file' => "Fila {$rowNumber}: una ubicación secundaria requiere padre_codigo.",
                ]);
            }

            if ($row['tipo'] === 'secundaria') {
                $padreCodigo = mb_strtolower($row['padre_codigo']);
                $padreExiste = isset($existingLookup[$padreCodigo]) || isset($csvLookup[$padreCodigo]);

                if (! $padreExiste) {
                    throw ValidationException::withMessages([
                        'file' => "Fila {$rowNumber}: el padre_codigo {$row['padre_codigo']} no existe.",
                    ]);
                }
            }

            $csvLookup[$codigo] = true;
        }
    }
}
