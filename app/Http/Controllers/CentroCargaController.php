<?php

namespace App\Http\Controllers;

use App\Models\Contratista;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CentroCargaController extends Controller
{
    /**
     * Display the Centro de Carga page.
     */
    public function index(Request $request): Response
    {
        $trabajador = null;
        $requirements = [
            'tipos_documentos' => [],
            'tipos_documentos_cargados' => [],
            'sin_faena_activa' => false,
        ];

        if ($request->filled('trabajador_id')) {
            $trabajador = Trabajador::query()
                ->select(['id', 'documento', 'nombre', 'apellido', 'estado', 'contratista_id'])
                ->findOrFail((string) $request->string('trabajador_id'));

            $this->authorizeTrabajadorAccess($request, $trabajador);
            $requirements = $this->buildRequirements($trabajador);
        }

        return Inertia::render('trabajadores/centro-carga', [
            'initialTrabajador' => $trabajador
                ? [
                    'id' => $trabajador->id,
                    'documento' => $trabajador->documento,
                    'nombre' => $trabajador->nombre,
                    'apellido' => $trabajador->apellido,
                    'nombre_completo' => $trabajador->nombre_completo,
                    'estado' => $trabajador->estado,
                ]
                : null,
            'initialRequirements' => $requirements,
        ]);
    }

    /**
     * Display Centro de Carga page for contratista documents.
     */
    public function contratistasIndex(Request $request): Response
    {
        $contratista = null;
        $requirements = [
            'tipos_documentos' => [],
            'tipos_documentos_cargados' => [],
            'sin_faena_activa' => false,
        ];

        $periodoAno = $request->filled('periodo_ano')
            ? (int) $request->integer('periodo_ano')
            : (int) now()->year;
        $periodoMes = $request->filled('periodo_mes')
            ? (int) $request->integer('periodo_mes')
            : null;

        if ($request->filled('contratista_id')) {
            $contratista = Contratista::query()
                ->select(['id', 'rut', 'razon_social', 'nombre_fantasia', 'estado'])
                ->findOrFail((int) $request->integer('contratista_id'));

            $this->authorizeContratistaAccess($request, $contratista);
            $requirements = $this->buildContratistaRequirements($contratista, $periodoAno, $periodoMes);
        }

        return Inertia::render('contratistas/centro-carga', [
            'initialContratista' => $contratista
                ? [
                    'id' => $contratista->id,
                    'rut' => $contratista->rut,
                    'razon_social' => $contratista->razon_social,
                    'nombre_fantasia' => $contratista->nombre_fantasia,
                    'nombre_mostrado' => $contratista->nombre_fantasia ?: $contratista->razon_social,
                    'estado' => $contratista->estado,
                ]
                : null,
            'initialPeriodo' => [
                'periodo_ano' => $periodoAno,
                'periodo_mes' => $periodoMes,
            ],
            'initialRequirements' => $requirements,
        ]);
    }

    /**
     * Search trabajadores by RUT, nombre or apellido.
     */
    public function searchTrabajadores(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));

        if ($search === '' || mb_strlen($search) < 2) {
            return response()->json([
                'data' => [],
            ]);
        }

        $query = Trabajador::query()
            ->select(['id', 'documento', 'nombre', 'apellido', 'estado', 'contratista_id'])
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->limit(12)
            ->where(function ($workerQuery) use ($search) {
                $workerQuery->where('documento', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%");
            });

        $user = $request->user();
        if (! $user->isAdmin()) {
            $query->where('contratista_id', $user->contratista_id);
        }

        $trabajadores = $query->get()->map(fn (Trabajador $trabajador) => [
            'id' => $trabajador->id,
            'documento' => $trabajador->documento,
            'nombre' => $trabajador->nombre,
            'apellido' => $trabajador->apellido,
            'nombre_completo' => $trabajador->nombre_completo,
            'estado' => $trabajador->estado,
        ]);

        return response()->json([
            'data' => $trabajadores,
        ]);
    }

    /**
     * Search contratistas by RUT, razón social or nombre de fantasía.
     */
    public function searchContratistas(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));

        if ($search === '' || mb_strlen($search) < 2) {
            return response()->json([
                'data' => [],
            ]);
        }

        $query = Contratista::query()
            ->select(['id', 'rut', 'razon_social', 'nombre_fantasia', 'estado'])
            ->where('estado', 'activo')
            ->orderBy('razon_social')
            ->limit(12)
            ->where(function ($contratistaQuery) use ($search) {
                $contratistaQuery->where('rut', 'like', "%{$search}%")
                    ->orWhere('razon_social', 'like', "%{$search}%")
                    ->orWhere('nombre_fantasia', 'like', "%{$search}%");
            });

        $user = $request->user();
        if (! $user->isAdmin()) {
            $query->where('id', $user->contratista_id);
        }

        $contratistas = $query->get()->map(fn (Contratista $contratista) => [
            'id' => $contratista->id,
            'rut' => $contratista->rut,
            'razon_social' => $contratista->razon_social,
            'nombre_fantasia' => $contratista->nombre_fantasia,
            'nombre_mostrado' => $contratista->nombre_fantasia ?: $contratista->razon_social,
            'estado' => $contratista->estado,
        ]);

        return response()->json([
            'data' => $contratistas,
        ]);
    }

    /**
     * Get document requirements for selected trabajador.
     */
    public function requirements(Request $request, Trabajador $trabajador): JsonResponse
    {
        $this->authorizeTrabajadorAccess($request, $trabajador);

        return response()->json($this->buildRequirements($trabajador));
    }

    /**
     * Get document requirements for selected contratista and period.
     */
    public function contratistaRequirements(Request $request, Contratista $contratista): JsonResponse
    {
        $this->authorizeContratistaAccess($request, $contratista);

        $periodoAno = $request->filled('periodo_ano')
            ? (int) $request->integer('periodo_ano')
            : (int) now()->year;
        $periodoMes = $request->filled('periodo_mes')
            ? (int) $request->integer('periodo_mes')
            : null;

        return response()->json(
            $this->buildContratistaRequirements($contratista, $periodoAno, $periodoMes),
        );
    }

    private function authorizeTrabajadorAccess(Request $request, Trabajador $trabajador): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $trabajador->contratista_id !== $user->contratista_id) {
            abort(403);
        }
    }

    private function authorizeContratistaAccess(Request $request, Contratista $contratista): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $contratista->id !== $user->contratista_id) {
            abort(403);
        }
    }

    /**
     * @return array{
     *     tipos_documentos: array<int, array{
     *         id: int,
     *         nombre: string,
     *         codigo: string,
     *         es_obligatorio: bool,
     *         permite_multiples_en_mes: bool,
     *         formatos_permitidos: array<int, string>|null,
     *         tamano_maximo_kb: int,
     *         dias_vencimiento: int|null,
     *         palabras_clave: array<int, string>
     *     }>,
     *     tipos_documentos_cargados: array<int, int>,
     *     sin_faena_activa: bool
     * }
     */
    private function buildRequirements(Trabajador $trabajador): array
    {
        $tipoFaenaIds = $trabajador->faenas()
            ->wherePivotNull('fecha_desasignacion')
            ->pluck('faenas.tipo_faena_id')
            ->filter()
            ->unique();

        $tiposDocumentosQuery = TipoDocumento::query()
            ->active()
            ->where('es_documento_trabajador', true)
            ->orderByDesc('es_obligatorio')
            ->orderBy('nombre');

        $sinFaenaActiva = $tipoFaenaIds->isEmpty();

        if ($sinFaenaActiva) {
            $tiposDocumentosQuery->whereRaw('1 = 0');
        } else {
            $tiposDocumentosQuery->whereHas('tiposFaena', function ($query) use ($tipoFaenaIds) {
                $query->whereIn('tipo_faenas.id', $tipoFaenaIds);
            });
        }

        $tiposDocumentos = $tiposDocumentosQuery
            ->get([
                'id',
                'nombre',
                'codigo',
                'descripcion',
                'instrucciones',
                'es_obligatorio',
                'permite_multiples_en_mes',
                'formatos_permitidos',
                'tamano_maximo_kb',
                'dias_vencimiento',
            ])
            ->map(fn (TipoDocumento $tipoDocumento) => [
                'id' => $tipoDocumento->id,
                'nombre' => $tipoDocumento->nombre,
                'codigo' => $tipoDocumento->codigo,
                'es_obligatorio' => (bool) $tipoDocumento->es_obligatorio,
                'permite_multiples_en_mes' => (bool) $tipoDocumento->permite_multiples_en_mes,
                'formatos_permitidos' => $tipoDocumento->formatos_permitidos,
                'tamano_maximo_kb' => (int) $tipoDocumento->tamano_maximo_kb,
                'dias_vencimiento' => $tipoDocumento->dias_vencimiento,
                'palabras_clave' => $this->buildKeywordsFromTipoDocumento($tipoDocumento),
            ])
            ->values()
            ->all();

        $tiposDocumentosCargados = $trabajador->documentosTrabajador()
            ->whereHas('tipoDocumento', function ($query) {
                $query->where('permite_multiples_en_mes', false);
            })
            ->pluck('tipo_documento_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'tipos_documentos' => $tiposDocumentos,
            'tipos_documentos_cargados' => $tiposDocumentosCargados,
            'sin_faena_activa' => $sinFaenaActiva,
        ];
    }

    /**
     * @return array{
     *     tipos_documentos: array<int, array{
     *         id: int,
     *         nombre: string,
     *         codigo: string,
     *         es_obligatorio: bool,
     *         permite_multiples_en_mes: bool,
     *         formatos_permitidos: array<int, string>|null,
     *         tamano_maximo_kb: int,
     *         dias_vencimiento: int|null,
     *         palabras_clave: array<int, string>
     *     }>,
     *     tipos_documentos_cargados: array<int, int>,
     *     sin_faena_activa: bool
     * }
     */
    private function buildContratistaRequirements(
        Contratista $contratista,
        int $periodoAno,
        ?int $periodoMes
    ): array {
        $tiposDocumentos = TipoDocumento::query()
            ->active()
            ->where('es_documento_trabajador', false)
            ->orderByDesc('es_obligatorio')
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
                'codigo',
                'descripcion',
                'instrucciones',
                'es_obligatorio',
                'permite_multiples_en_mes',
                'formatos_permitidos',
                'tamano_maximo_kb',
                'dias_vencimiento',
            ])
            ->map(fn (TipoDocumento $tipoDocumento) => [
                'id' => $tipoDocumento->id,
                'nombre' => $tipoDocumento->nombre,
                'codigo' => $tipoDocumento->codigo,
                'es_obligatorio' => (bool) $tipoDocumento->es_obligatorio,
                'permite_multiples_en_mes' => (bool) $tipoDocumento->permite_multiples_en_mes,
                'formatos_permitidos' => $tipoDocumento->formatos_permitidos,
                'tamano_maximo_kb' => (int) $tipoDocumento->tamano_maximo_kb,
                'dias_vencimiento' => $tipoDocumento->dias_vencimiento,
                'palabras_clave' => $this->buildKeywordsFromTipoDocumento($tipoDocumento),
            ])
            ->values()
            ->all();

        $tiposDocumentosCargados = Documento::query()
            ->where('contratista_id', $contratista->id)
            ->where('periodo_ano', $periodoAno)
            ->whereHas('tipoDocumento', function ($query) {
                $query->where('permite_multiples_en_mes', false);
            })
            ->when(
                $periodoMes === null,
                fn ($query) => $query->whereNull('periodo_mes'),
                fn ($query) => $query->where('periodo_mes', $periodoMes),
            )
            ->pluck('tipo_documento_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'tipos_documentos' => $tiposDocumentos,
            'tipos_documentos_cargados' => $tiposDocumentosCargados,
            'sin_faena_activa' => false,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildKeywordsFromTipoDocumento(TipoDocumento $tipoDocumento): array
    {
        $rawSource = collect([
            $tipoDocumento->nombre,
            $tipoDocumento->codigo,
            $tipoDocumento->descripcion,
            $tipoDocumento->instrucciones,
        ])->filter()->implode(' ');

        if ($rawSource === '') {
            return [];
        }

        $normalized = Str::of($rawSource)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/i', ' ')
            ->squish()
            ->value();

        if ($normalized === '') {
            return [];
        }

        $stopWords = [
            'de',
            'del',
            'la',
            'las',
            'el',
            'los',
            'para',
            'con',
            'por',
            'sin',
            'una',
            'uno',
            'que',
            'y',
            'o',
            'en',
            'al',
        ];

        $tokens = collect(explode(' ', $normalized))
            ->filter(fn (string $token) => strlen($token) >= 3)
            ->reject(fn (string $token) => in_array($token, $stopWords, true))
            ->values();

        $bigrams = [];
        for ($i = 0; $i < $tokens->count() - 1; $i++) {
            $first = $tokens->get($i);
            $second = $tokens->get($i + 1);

            if ($first === null || $second === null) {
                continue;
            }

            $bigrams[] = "{$first} {$second}";
        }

        return $tokens
            ->concat($bigrams)
            ->unique()
            ->take(40)
            ->values()
            ->all();
    }
}
