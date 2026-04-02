<?php

namespace App\Actions\Asistencia;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ComparePdfAsistenciaWithGreenexnetAction
{
    /**
     * @param  list<array{
     *      numero: int,
     *      rut: string,
     *      apellido_paterno: string,
     *      apellido_materno: string,
     *      nombres: string,
     *      dias_trabajados: int
     *  }>  $pdfRows
     * @return array{
     *      rows: list<array{
     *          numero: int,
     *          rut: string,
     *          apellido_paterno: string,
     *          apellido_materno: string,
     *          nombres: string,
     *          dias_trabajados: int,
     *          dias_asistencia: int|null,
     *          diferencia: int|null,
     *          estado: string
     *      }>,
     *      summary: array{
     *          total_registros: int,
     *          total_coinciden: int,
     *          total_difieren: int,
     *          total_sin_datos: int
     *      }
     * }
     */
    public function compare(array $pdfRows, int $entidadId, int $mes): array
    {
        $ruts = array_values(array_unique(array_map(
            static fn (array $row): string => (string) $row['rut'],
            $pdfRows
        )));

        $diasPorRut = $this->fetchDiasTrabajadosByRut($ruts, $entidadId, $mes);

        $totalCoinciden = 0;
        $totalDifieren = 0;
        $totalSinDatos = 0;
        $rows = [];

        foreach ($pdfRows as $row) {
            $diasPdf = (int) $row['dias_trabajados'];
            $diasAsistencia = $diasPorRut[$row['rut']] ?? null;

            if ($diasAsistencia === null) {
                $estado = 'sin_datos';
                $diferencia = null;
                $totalSinDatos++;
            } else {
                $diferencia = $diasPdf - $diasAsistencia;
                $estado = $diferencia === 0 ? 'coincide' : 'difiere';

                if ($estado === 'coincide') {
                    $totalCoinciden++;
                } else {
                    $totalDifieren++;
                }
            }

            $rows[] = [
                ...$row,
                'dias_asistencia' => $diasAsistencia,
                'diferencia' => $diferencia,
                'estado' => $estado,
            ];
        }

        return [
            'rows' => $rows,
            'summary' => [
                'total_registros' => count($rows),
                'total_coinciden' => $totalCoinciden,
                'total_difieren' => $totalDifieren,
                'total_sin_datos' => $totalSinDatos,
            ],
        ];
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function getEntidadesTipoDos(): array
    {
        try {
            $response = $this->baseRequest(Http::acceptJson())
                ->get($this->entidadesPath(), ['tipo_id' => 2]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No fue posible obtener entidades desde greenexnet.test.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('No fue posible obtener entidades desde greenexnet.test.');
        }

        $entidades = [];

        foreach ($this->extractList($response->json()) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (int) ($item['id'] ?? 0);
            $nombre = trim((string) ($item['nombre'] ?? ''));
            $tipoId = (int) ($item['tipo_id'] ?? 2);

            if ($id <= 0 || $nombre === '' || $tipoId !== 2) {
                continue;
            }

            $entidades[] = [
                'id' => $id,
                'nombre' => $nombre,
            ];
        }

        usort($entidades, static fn (array $left, array $right): int => $left['nombre'] <=> $right['nombre']);

        return $entidades;
    }

    /**
     * @param  list<string>  $ruts
     * @return array<string, int|null>
     */
    private function fetchDiasTrabajadosByRut(array $ruts, int $entidadId, int $mes): array
    {
        $diasPorRut = [];
        $chunks = array_chunk($ruts, 30);

        foreach ($chunks as $chunkIndex => $chunk) {
            $keys = [];

            try {
                $responses = Http::pool(function (Pool $pool) use ($chunk, $entidadId, $mes, &$keys): void {
                    foreach ($chunk as $index => $rut) {
                        $key = 'rut_'.$index;
                        $keys[$key] = $rut;

                        $this->baseRequest($pool->as($key))
                            ->get($this->diasTrabajadosPath(), [
                                'rut' => $rut,
                                'entidad' => $entidadId,
                                'mes' => $mes,
                            ]);
                    }
                });
            } catch (\Throwable $exception) {
                $this->markRemainingRutsAsMissing($chunks, $chunkIndex, $diasPorRut);

                break;
            }

            $hasSuccessfulResponse = false;

            foreach ($responses as $key => $response) {
                $rut = $keys[$key] ?? null;

                if ($rut === null) {
                    continue;
                }

                if (! $response instanceof Response) {
                    $diasPorRut[$rut] = null;

                    continue;
                }

                if (! $response->successful()) {
                    $diasPorRut[$rut] = null;

                    continue;
                }

                $hasSuccessfulResponse = true;
                $diasPorRut[$rut] = $this->extractDiasTrabajados($response->json());
            }

            // Si no hubo una sola respuesta válida, evitamos repetir timeouts por cada chunk.
            if (! $hasSuccessfulResponse) {
                $this->markRemainingRutsAsMissing($chunks, $chunkIndex + 1, $diasPorRut);

                break;
            }
        }

        return $diasPorRut;
    }

    private function baseRequest(PendingRequest $request): PendingRequest
    {
        $configured = $request
            ->baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout($this->timeout())
            ->withOptions([
                'verify' => $this->verifySsl(),
                'connect_timeout' => $this->connectTimeout(),
            ]);

        $token = $this->token();

        if ($token !== '') {
            $configured = $configured->withToken($token);
        }

        return $configured;
    }

    private function baseUrl(): string
    {
        $baseUrl = rtrim((string) config('services.greenexnet.base_url', ''), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('La URL base de Greenexnet no está configurada.');
        }

        return $baseUrl;
    }

    private function entidadesPath(): string
    {
        return $this->normalizePath((string) config('services.greenexnet.entidades_path', '/api/entidads'));
    }

    private function diasTrabajadosPath(): string
    {
        return $this->normalizePath((string) config('services.greenexnet.dias_trabajados_path', '/api/attendances/dias-trabajados'));
    }

    private function token(): string
    {
        return trim((string) config('services.greenexnet.token', ''));
    }

    private function timeout(): int
    {
        return min(max((int) config('services.greenexnet.timeout', 6), 1), 8);
    }

    private function connectTimeout(): int
    {
        return max((int) config('services.greenexnet.connect_timeout', 2), 1);
    }

    private function verifySsl(): bool
    {
        return (bool) config('services.greenexnet.verify_ssl', true);
    }

    private function normalizePath(string $path): string
    {
        return '/'.ltrim($path, '/');
    }

    /**
     * @return list<mixed>
     */
    private function extractList(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        foreach (['data', 'entidades', 'results'] as $key) {
            if (isset($payload[$key])) {
                return $this->extractList($payload[$key]);
            }
        }

        return [];
    }

    private function extractDiasTrabajados(mixed $payload): ?int
    {
        if (is_numeric($payload)) {
            return (int) $payload;
        }

        if (! is_array($payload)) {
            return null;
        }

        foreach (['dias_trabajados', 'dias', 'count'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        if (isset($payload['data'])) {
            return $this->extractDiasTrabajados($payload['data']);
        }

        if (array_is_list($payload) && isset($payload[0])) {
            return $this->extractDiasTrabajados($payload[0]);
        }

        return null;
    }

    /**
     * @param  list<list<string>>  $chunks
     * @param  array<string, int|null>  $diasPorRut
     */
    private function markRemainingRutsAsMissing(array $chunks, int $startChunk, array &$diasPorRut): void
    {
        for ($chunkIndex = $startChunk; $chunkIndex < count($chunks); $chunkIndex++) {
            foreach ($chunks[$chunkIndex] as $rut) {
                $diasPorRut[$rut] = null;
            }
        }
    }
}
