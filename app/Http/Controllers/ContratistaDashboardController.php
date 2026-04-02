<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Asistencia;
use App\Models\Contratista;
use App\Models\Documento;
use App\Models\DocumentoTrabajador;
use App\Models\EstadoPago;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContratistaDashboardController extends Controller
{
    /**
     * Display the contratista dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return Inertia::render('dashboard', $this->buildAdminDashboardPayload());
        }

        $contratista = $user->contratista;

        $stats = [
            'personal_activo' => Trabajador::forContratista($contratista->id)->active()->count(),
            'documentos_pendientes' => $this->countDocumentosPendientes($contratista->id),
            'documentos_vencidos' => Documento::forContratista($contratista->id)->expired()->count(),
            'horas_trabajadas_mes' => $this->getHorasTrabajadas($contratista->id),
            'pagos_pendientes' => EstadoPago::forContratista($contratista->id)->pendingPayment()->count(),
            'estado_cumplimiento' => $this->getEstadoCumplimiento($contratista->id),
        ];

        $alertas = Alerta::forContratista($contratista->id)
            ->unread()
            ->orderBy('prioridad', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($alerta) => [
                'id' => $alerta->id,
                'tipo' => $alerta->tipo,
                'titulo' => $alerta->titulo,
                'mensaje' => $alerta->mensaje,
                'prioridad' => $alerta->prioridad,
                'created_at' => $alerta->created_at->format('Y-m-d H:i'),
            ]);

        $estadosPago = EstadoPago::forContratista($contratista->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($estado) => [
                'id' => $estado->id,
                'numero_documento' => $estado->numero_documento,
                'fecha_documento' => $estado->fecha_documento->format('d/m/Y'),
                'monto' => number_format($estado->monto, 0, ',', '.'),
                'estado' => $estado->estado,
                'fecha_pago_estimada' => $estado->fecha_pago_estimada?->format('d/m/Y'),
            ]);

        return Inertia::render('contratistas/dashboard', [
            'contratista' => [
                'id' => $contratista->id,
                'rut' => $contratista->rut,
                'razon_social' => $contratista->razon_social,
                'nombre_fantasia' => $contratista->nombre_fantasia,
                'estado' => $contratista->estado,
            ],
            'stats' => $stats,
            'alertas' => $alertas,
            'estadosPago' => $estadosPago,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminDashboardPayload(): array
    {
        $requiredMetadata = $this->buildRequiredDocumentMetadata();
        $requiredByTipoFaena = $requiredMetadata['required_by_tipo_faena'];
        $requiredNames = $requiredMetadata['required_names'];
        $criticalRequiredIds = $requiredMetadata['critical_required_tipo_ids'];

        $workers = Trabajador::query()
            ->select([
                'id',
                'documento',
                'nombre',
                'apellido',
                'contratista_id',
            ])
            ->with([
                'contratista:id,razon_social,nombre_fantasia',
                'faenas' => function ($query) {
                    $query
                        ->select(['faenas.id', 'faenas.nombre', 'faenas.tipo_faena_id'])
                        ->where('faenas.estado', 'activa')
                        ->wherePivotNull('fecha_desasignacion')
                        ->orderBy('faenas.nombre');
                },
                'documentosTrabajador' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'trabajador_id',
                            'tipo_documento_id',
                            'archivo_nombre_original',
                            'archivo_tamano_kb',
                            'fecha_vencimiento',
                            'created_at',
                        ])
                        ->with('tipoDocumento:id,nombre,codigo')
                        ->orderByDesc('created_at');
                },
            ])
            ->active()
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        $now = now();
        $in15Days = $now->copy()->addDays(15)->endOfDay();
        $in30Days = $now->copy()->addDays(30)->endOfDay();
        $recentThreshold = $now->copy()->subDay();
        $timelineEnd = $now->copy()->addMonthsNoOverflow(2)->endOfMonth();

        $timelineBuckets = collect(range(0, 2))
            ->mapWithKeys(function (int $offset) use ($now) {
                $month = $now->copy()->addMonthsNoOverflow($offset);
                $key = $month->format('Y-m');

                return [
                    $key => [
                        'key' => $key,
                        'label' => $month->translatedFormat('M Y'),
                        'count' => 0,
                    ],
                ];
            })
            ->all();

        $rows = [];
        $areaStats = [];
        $empresas = [];

        $totalRequiredDocuments = 0;
        $totalLoadedRequiredDocuments = 0;
        $documentsExpiringIn15Days = 0;
        $documentsExpiringIn30Days = 0;
        $expiredDocumentsTotal = 0;
        $workersMissingCriticalTotal = 0;
        $recentUploads24hTotal = 0;

        foreach ($workers as $worker) {
            $activeFaenas = $worker->faenas;
            $areaIds = $activeFaenas->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $areaNames = $activeFaenas->pluck('nombre')->map(fn ($name) => (string) $name)->values()->all();
            $areaPrincipal = $areaNames[0] ?? 'Sin área asignada';

            $requiredForWorker = $activeFaenas
                ->flatMap(fn ($faena) => $requiredByTipoFaena[$faena->tipo_faena_id] ?? [])
                ->unique()
                ->values();

            $requiredTotal = $requiredForWorker->count();
            $documents = $worker->documentosTrabajador;
            $documentsByTipo = $documents->keyBy('tipo_documento_id');

            $loadedRequiredTotal = $requiredForWorker
                ->filter(fn (int $tipoId) => $documentsByTipo->has($tipoId))
                ->count();

            $missingRequiredIds = $requiredForWorker
                ->filter(fn (int $tipoId) => ! $documentsByTipo->has($tipoId))
                ->values();

            $criticalMissingIds = $missingRequiredIds
                ->filter(fn (int $tipoId) => in_array($tipoId, $criticalRequiredIds, true))
                ->values();

            $criticalMissingNames = $criticalMissingIds
                ->map(fn (int $tipoId) => $requiredNames[$tipoId] ?? "Tipo {$tipoId}")
                ->values()
                ->all();

            $workerExpiredDocuments = 0;
            $workerExpiringSoon = 0;

            foreach ($documents as $document) {
                if ($document->created_at && $document->created_at->gte($recentThreshold)) {
                    $recentUploads24hTotal++;
                }

                if (! $document->fecha_vencimiento) {
                    continue;
                }

                $expiryDate = $document->fecha_vencimiento->copy()->startOfDay();
                if ($expiryDate->lt($now->copy()->startOfDay())) {
                    $workerExpiredDocuments++;
                }

                if ($expiryDate->gte($now->copy()->startOfDay()) && $expiryDate->lte($in15Days)) {
                    $documentsExpiringIn15Days++;
                }

                if ($expiryDate->gte($now->copy()->startOfDay()) && $expiryDate->lte($in30Days)) {
                    $documentsExpiringIn30Days++;
                    $workerExpiringSoon++;
                }

                if ($expiryDate->gte($now->copy()->startOfDay()) && $expiryDate->lte($timelineEnd)) {
                    $bucketKey = $expiryDate->format('Y-m');
                    if (isset($timelineBuckets[$bucketKey])) {
                        $timelineBuckets[$bucketKey]['count']++;
                    }
                }
            }

            $missingRequiredTotal = $missingRequiredIds->count();
            $criticalMissingTotal = $criticalMissingIds->count();

            $status = $this->resolveWorkerStatus(
                requiredTotal: $requiredTotal,
                missingRequiredTotal: $missingRequiredTotal,
                criticalMissingTotal: $criticalMissingTotal,
                expiredDocumentsTotal: $workerExpiredDocuments,
                expiringSoonTotal: $workerExpiringSoon,
            );

            if ($criticalMissingTotal > 0) {
                $workersMissingCriticalTotal++;
            }

            $expiredDocumentsTotal += $workerExpiredDocuments;
            $totalRequiredDocuments += $requiredTotal;
            $totalLoadedRequiredDocuments += $loadedRequiredTotal;

            $contratistaNombre = $worker->contratista?->nombre_fantasia ?: $worker->contratista?->razon_social ?: 'Sin contratista';
            if ($worker->contratista_id) {
                $empresas[(int) $worker->contratista_id] = [
                    'id' => (int) $worker->contratista_id,
                    'nombre' => $contratistaNombre,
                ];
            }

            foreach ($activeFaenas as $faena) {
                $areaKey = (int) $faena->id;
                $requiredForArea = collect($requiredByTipoFaena[$faena->tipo_faena_id] ?? [])->unique()->values();
                $requiredForAreaTotal = $requiredForArea->count();
                $loadedForAreaTotal = $requiredForArea
                    ->filter(fn (int $tipoId) => $documentsByTipo->has($tipoId))
                    ->count();

                if (! isset($areaStats[$areaKey])) {
                    $areaStats[$areaKey] = [
                        'id' => $areaKey,
                        'nombre' => (string) $faena->nombre,
                        'workers_total' => 0,
                        'required_total' => 0,
                        'loaded_total' => 0,
                        'expired_documents_total' => 0,
                        'workers_with_critical_missing' => 0,
                    ];
                }

                $areaStats[$areaKey]['workers_total']++;
                $areaStats[$areaKey]['required_total'] += $requiredForAreaTotal;
                $areaStats[$areaKey]['loaded_total'] += $loadedForAreaTotal;
                $areaStats[$areaKey]['expired_documents_total'] += $workerExpiredDocuments;
                if ($criticalMissingTotal > 0) {
                    $areaStats[$areaKey]['workers_with_critical_missing']++;
                }
            }

            if ($activeFaenas->isEmpty()) {
                $areaKey = 0;
                if (! isset($areaStats[$areaKey])) {
                    $areaStats[$areaKey] = [
                        'id' => $areaKey,
                        'nombre' => 'Sin área asignada',
                        'workers_total' => 0,
                        'required_total' => 0,
                        'loaded_total' => 0,
                        'expired_documents_total' => 0,
                        'workers_with_critical_missing' => 0,
                    ];
                }

                $areaStats[$areaKey]['workers_total']++;
                $areaStats[$areaKey]['expired_documents_total'] += $workerExpiredDocuments;
                if ($criticalMissingTotal > 0) {
                    $areaStats[$areaKey]['workers_with_critical_missing']++;
                }
            }

            $rows[] = [
                'id' => $worker->id,
                'documento' => $worker->documento,
                'nombre_completo' => $worker->nombre_completo,
                'contratista' => [
                    'id' => (int) ($worker->contratista_id ?? 0),
                    'nombre' => $contratistaNombre,
                ],
                'areas' => $areaNames,
                'area_ids' => $areaIds,
                'area_principal' => $areaPrincipal,
                'required_total' => $requiredTotal,
                'loaded_required_total' => $loadedRequiredTotal,
                'missing_required_total' => $missingRequiredTotal,
                'critical_missing_total' => $criticalMissingTotal,
                'critical_missing_names' => $criticalMissingNames,
                'expired_documents_total' => $workerExpiredDocuments,
                'expiring_soon_total' => $workerExpiringSoon,
                'compliance_percent' => $requiredTotal > 0
                    ? round(($loadedRequiredTotal / $requiredTotal) * 100, 1)
                    : 0,
                'status' => $status,
                'documentos' => $documents
                    ->map(fn (DocumentoTrabajador $document) => [
                        'id' => $document->id,
                        'tipo_documento' => $document->tipoDocumento?->nombre ?? 'Tipo no definido',
                        'codigo' => $document->tipoDocumento?->codigo ?? '-',
                        'archivo_nombre_original' => $document->archivo_nombre_original,
                        'archivo_tamano_kb' => $document->archivo_tamano_kb,
                        'fecha_vencimiento' => $document->fecha_vencimiento?->toDateString(),
                        'cargado_at' => $document->created_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        $workers = collect($rows)
            ->sortBy([
                fn (array $row) => $this->statusWeight($row['status']),
                fn (array $row) => $row['nombre_completo'],
            ])
            ->values()
            ->all();

        $complianceByArea = collect($areaStats)
            ->map(function (array $area) {
                $requiredTotal = (int) $area['required_total'];
                $loadedTotal = (int) $area['loaded_total'];
                $compliance = $requiredTotal > 0 ? round(($loadedTotal / $requiredTotal) * 100, 1) : 0.0;

                return [
                    'id' => $area['id'],
                    'nombre' => $area['nombre'],
                    'workers_total' => (int) $area['workers_total'],
                    'required_total' => $requiredTotal,
                    'loaded_total' => $loadedTotal,
                    'missing_total' => max(0, $requiredTotal - $loadedTotal),
                    'compliance_percent' => $compliance,
                    'expired_documents_total' => (int) $area['expired_documents_total'],
                    'critical_alerts_total' => (int) $area['expired_documents_total'] + (int) $area['workers_with_critical_missing'],
                ];
            })
            ->sortBy('nombre')
            ->values()
            ->all();

        $totalWorkers = count($workers);
        $criticalAlertsTotal = $expiredDocumentsTotal + $workersMissingCriticalTotal;
        $complianceIndexPercent = $totalRequiredDocuments > 0
            ? round(($totalLoadedRequiredDocuments / $totalRequiredDocuments) * 100, 1)
            : 0.0;

        return [
            'stats' => $this->getAdminStats(),
            'kpis' => [
                'compliance_index_percent' => $complianceIndexPercent,
                'required_documents_total' => $totalRequiredDocuments,
                'loaded_documents_total' => $totalLoadedRequiredDocuments,
                'documents_expiring_15_days_total' => $documentsExpiringIn15Days,
                'documents_expiring_30_days_total' => $documentsExpiringIn30Days,
                'critical_alerts_total' => $criticalAlertsTotal,
                'expired_documents_total' => $expiredDocumentsTotal,
                'workers_missing_critical_total' => $workersMissingCriticalTotal,
                'recent_uploads_24h_total' => $recentUploads24hTotal,
                'workers_total' => $totalWorkers,
            ],
            'compliance_by_area' => $complianceByArea,
            'expirations_timeline' => array_values($timelineBuckets),
            'workers' => $workers,
            'filter_options' => [
                'status' => [
                    ['value' => 'all', 'label' => 'Todos'],
                    ['value' => 'al_dia', 'label' => 'Al día'],
                    ['value' => 'incompleto', 'label' => 'Incompleto'],
                    ['value' => 'vencido', 'label' => 'Vencido'],
                ],
                'areas' => array_map(
                    fn (array $area) => ['value' => $area['nombre'], 'label' => $area['nombre']],
                    $complianceByArea,
                ),
                'empresas' => collect($empresas)
                    ->sortBy('nombre')
                    ->values()
                    ->map(fn (array $empresa) => [
                        'value' => (string) $empresa['id'],
                        'label' => $empresa['nombre'],
                    ])
                    ->all(),
            ],
        ];
    }

    /**
     * @return array{
     *     required_by_tipo_faena: array<int, array<int, int>>,
     *     critical_required_tipo_ids: array<int, int>,
     *     required_names: array<int, string>
     * }
     */
    private function buildRequiredDocumentMetadata(): array
    {
        $requiredTipos = TipoDocumento::query()
            ->active()
            ->where('es_documento_trabajador', true)
            ->where('es_obligatorio', true)
            ->with('tiposFaena:id')
            ->get([
                'id',
                'nombre',
                'codigo',
                'descripcion',
                'instrucciones',
            ]);

        $requiredByTipoFaena = [];
        $requiredNames = [];
        $criticalRequiredTipoIds = [];

        foreach ($requiredTipos as $tipoDocumento) {
            $requiredNames[(int) $tipoDocumento->id] = (string) $tipoDocumento->nombre;

            $normalizedSource = Str::of(collect([
                $tipoDocumento->nombre,
                $tipoDocumento->codigo,
                $tipoDocumento->descripcion,
                $tipoDocumento->instrucciones,
            ])->filter()->implode(' '))
                ->lower()
                ->ascii()
                ->value();

            if (Str::contains($normalizedSource, ['cedula', 'identidad', 'carnet', 'seguro'])) {
                $criticalRequiredTipoIds[] = (int) $tipoDocumento->id;
            }

            foreach ($tipoDocumento->tiposFaena as $tipoFaena) {
                $tipoFaenaId = (int) $tipoFaena->id;
                $requiredByTipoFaena[$tipoFaenaId] ??= [];
                $requiredByTipoFaena[$tipoFaenaId][] = (int) $tipoDocumento->id;
            }
        }

        foreach ($requiredByTipoFaena as $tipoFaenaId => $tipoIds) {
            $requiredByTipoFaena[(int) $tipoFaenaId] = collect($tipoIds)->unique()->values()->all();
        }

        return [
            'required_by_tipo_faena' => $requiredByTipoFaena,
            'critical_required_tipo_ids' => collect($criticalRequiredTipoIds)->unique()->values()->all(),
            'required_names' => $requiredNames,
        ];
    }

    private function resolveWorkerStatus(
        int $requiredTotal,
        int $missingRequiredTotal,
        int $criticalMissingTotal,
        int $expiredDocumentsTotal,
        int $expiringSoonTotal
    ): string {
        if ($expiredDocumentsTotal > 0 || $criticalMissingTotal > 0) {
            return 'vencido';
        }

        if ($requiredTotal === 0 || $missingRequiredTotal > 0 || $expiringSoonTotal > 0) {
            return 'incompleto';
        }

        return 'al_dia';
    }

    private function statusWeight(string $status): int
    {
        return match ($status) {
            'vencido' => 0,
            'incompleto' => 1,
            default => 2,
        };
    }

    /**
     * Get admin stats.
     */
    private function getAdminStats(): array
    {
        return [
            'total_contratistas' => Contratista::query()->where('estado', 'activo')->count(),
            'total_trabajadores' => Trabajador::active()->count(),
            'documentos_por_aprobar' => Documento::byEstado('pendiente_validacion')->count(),
            'alertas_activas' => Alerta::unread()->count(),
        ];
    }

    /**
     * Count pending documents for current period.
     */
    private function countDocumentosPendientes(int $contratistaId): int
    {
        $tiposObligatorios = TipoDocumento::obligatory()->active()->pluck('id');
        $currentYear = now()->year;
        $currentMonth = now()->month - 1;

        $cargados = Documento::forContratista($contratistaId)
            ->where('periodo_ano', $currentYear)
            ->where('periodo_mes', $currentMonth)
            ->whereIn('tipo_documento_id', $tiposObligatorios)
            ->count();

        return $tiposObligatorios->count() - $cargados;
    }

    /**
     * Get worked hours for current month.
     */
    private function getHorasTrabajadas(int $contratistaId): float
    {
        $trabajadores = Trabajador::forContratista($contratistaId)->pluck('id');
        $totalHours = 0;

        foreach ($trabajadores as $trabajadorId) {
            $days = now()->daysInMonth;
            for ($day = 1; $day <= $days; $day++) {
                $date = now()->setDay($day)->format('Y-m-d');
                $totalHours += Asistencia::calculateWorkedHours($trabajadorId, $date);
            }
        }

        return round($totalHours, 2);
    }

    /**
     * Get compliance status for the contratista.
     */
    private function getEstadoCumplimiento(int $contratistaId): array
    {
        $pendientes = $this->countDocumentosPendientes($contratistaId);
        $vencidos = Documento::forContratista($contratistaId)->expired()->count();

        if ($vencidos > 0) {
            return [
                'estado' => 'bloqueado',
                'porcentaje' => 0,
                'mensaje' => "Tiene {$vencidos} documento(s) vencido(s)",
            ];
        }

        if ($pendientes > 0) {
            return [
                'estado' => 'pendiente',
                'porcentaje' => 50,
                'mensaje' => "Tiene {$pendientes} documento(s) pendiente(s) de carga",
            ];
        }

        return [
            'estado' => 'al_dia',
            'porcentaje' => 100,
            'mensaje' => 'Todos los documentos están al día',
        ];
    }
}
