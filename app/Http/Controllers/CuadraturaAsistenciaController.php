<?php

namespace App\Http\Controllers;

use App\Actions\Asistencia\ComparePdfAsistenciaWithGreenexnetAction;
use App\Actions\Asistencia\ExtractCotizacionesFonasaAction;
use App\Http\Requests\CuadraturaAsistenciaRequest;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CuadraturaAsistenciaController extends Controller
{
    public function index(ComparePdfAsistenciaWithGreenexnetAction $compareAction): Response
    {
        $entidadesPayload = $this->resolveEntidadesPayload($compareAction);

        return Inertia::render('herramientas/cuadratura-asistencia', [
            'rows' => [],
            'summary' => null,
            'comparisonSummary' => null,
            'entidades' => $entidadesPayload['entidades'],
            'entidadesError' => $entidadesPayload['error'],
            'filters' => [
                'entidad_id' => null,
                'mes' => null,
            ],
        ]);
    }

    public function extract(
        CuadraturaAsistenciaRequest $request,
        ExtractCotizacionesFonasaAction $extractCotizacionesFonasa,
        ComparePdfAsistenciaWithGreenexnetAction $compareAction
    ): Response {
        $validated = $request->validated();
        $pdfFile = $request->file('archivo');
        $pdfPath = $pdfFile?->getRealPath();
        $entidadId = (int) $validated['entidad_id'];
        $mes = (int) $validated['mes'];

        if (! is_string($pdfPath) || $pdfPath === '') {
            throw ValidationException::withMessages([
                'archivo' => 'No fue posible acceder al archivo seleccionado.',
            ]);
        }

        try {
            $extraction = $extractCotizacionesFonasa->extractFromPath($pdfPath);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'archivo' => $exception->getMessage(),
            ]);
        }

        if ($extraction['rows'] === []) {
            throw ValidationException::withMessages([
                'archivo' => 'No se encontraron filas de detalle en el anexo de cotizaciones.',
            ]);
        }

        try {
            $comparison = $compareAction->compare($extraction['rows'], $entidadId, $mes);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'archivo' => $exception->getMessage(),
            ]);
        }

        $entidadesPayload = $this->resolveEntidadesPayload($compareAction);
        $entidadNombre = collect($entidadesPayload['entidades'])->firstWhere('id', $entidadId)['nombre'] ?? null;

        return Inertia::render('herramientas/cuadratura-asistencia', [
            'rows' => $comparison['rows'],
            'summary' => [
                'nombre_archivo' => $pdfFile->getClientOriginalName(),
                'periodo_mes' => $extraction['periodo']['mes'],
                'periodo_ano' => $extraction['periodo']['ano'],
                'total_registros' => count($extraction['rows']),
                'entidad_id' => $entidadId,
                'entidad_nombre' => $entidadNombre,
                'mes_consultado' => $mes,
            ],
            'comparisonSummary' => $comparison['summary'],
            'entidades' => $entidadesPayload['entidades'],
            'entidadesError' => $entidadesPayload['error'],
            'filters' => [
                'entidad_id' => $entidadId,
                'mes' => $mes,
            ],
        ]);
    }

    /**
     * @return array{entidades: list<array{id: int, nombre: string}>, error: string|null}
     */
    private function resolveEntidadesPayload(ComparePdfAsistenciaWithGreenexnetAction $compareAction): array
    {
        try {
            return [
                'entidades' => $compareAction->getEntidadesTipoDos(),
                'error' => null,
            ];
        } catch (\RuntimeException $exception) {
            return [
                'entidades' => [],
                'error' => $exception->getMessage(),
            ];
        }
    }
}
