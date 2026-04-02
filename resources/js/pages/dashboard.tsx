import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    CalendarClock,
    CheckCircle2,
    Download,
    Eye,
    FileClock,
    FileWarning,
    FolderOpen,
    Search,
    UploadCloud,
    Users,
} from 'lucide-react';
import { type ReactNode, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardKpis {
    compliance_index_percent: number;
    required_documents_total: number;
    loaded_documents_total: number;
    documents_expiring_15_days_total: number;
    documents_expiring_30_days_total: number;
    critical_alerts_total: number;
    expired_documents_total: number;
    workers_missing_critical_total: number;
    recent_uploads_24h_total: number;
    workers_total: number;
}

interface ComplianceByAreaItem {
    id: number;
    nombre: string;
    workers_total: number;
    required_total: number;
    loaded_total: number;
    missing_total: number;
    compliance_percent: number;
    expired_documents_total: number;
    critical_alerts_total: number;
}

interface ExpirationTimelineItem {
    key: string;
    label: string;
    count: number;
}

interface ComplianceByCompanyItem {
    id: number;
    nombre: string;
    workers_total: number;
    required_total: number;
    loaded_total: number;
    missing_total: number;
    compliance_percent: number;
}

interface WorkerDocumentItem {
    id: number;
    tipo_documento: string;
    codigo: string;
    archivo_nombre_original: string;
    archivo_tamano_kb: number;
    fecha_vencimiento: string | null;
    cargado_at: string | null;
}

interface DashboardWorkerRow {
    id: string;
    documento: string;
    nombre_completo: string;
    contratista: {
        id: number;
        nombre: string;
    };
    areas: string[];
    area_ids: number[];
    area_principal: string;
    required_total: number;
    loaded_required_total: number;
    missing_required_total: number;
    critical_missing_total: number;
    critical_missing_names: string[];
    expired_documents_total: number;
    expiring_soon_total: number;
    compliance_percent: number;
    status: 'al_dia' | 'incompleto' | 'vencido';
    documentos: WorkerDocumentItem[];
}

interface SelectOption {
    value: string;
    label: string;
}

interface FilterOptions {
    status: SelectOption[];
    areas: SelectOption[];
    empresas: SelectOption[];
}

interface Stats {
    total_contratistas?: number;
    total_trabajadores?: number;
    documentos_por_aprobar?: number;
    alertas_activas?: number;
}

interface Props {
    stats?: Stats;
    kpis: DashboardKpis;
    compliance_by_area: ComplianceByAreaItem[];
    expirations_timeline: ExpirationTimelineItem[];
    workers: DashboardWorkerRow[];
    filter_options: FilterOptions;
}

const statusConfig: Record<
    DashboardWorkerRow['status'],
    {
        label: string;
        dotClass: string;
        badgeClass: string;
    }
> = {
    al_dia: {
        label: 'Al día',
        dotClass: 'bg-[var(--brand-green)]',
        badgeClass: 'bg-[var(--brand-green)] text-[var(--primary-foreground)]',
    },
    incompleto: {
        label: 'Incompleto',
        dotClass: 'bg-[var(--brand-orange)]',
        badgeClass: 'bg-[var(--brand-orange)] text-white',
    },
    vencido: {
        label: 'Vencido/Crítico',
        dotClass: 'bg-destructive',
        badgeClass: 'bg-destructive text-white',
    },
};

function formatDate(value: string | null): string {
    if (!value) {
        return 'Sin fecha';
    }

    return new Date(value).toLocaleDateString('es-CL');
}

function isExpired(value: string | null): boolean {
    if (!value) {
        return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const target = new Date(value);
    target.setHours(0, 0, 0, 0);

    return target < today;
}

function KpiCard({
    title,
    value,
    subtitle,
    icon,
}: {
    title: string;
    value: string;
    subtitle: string;
    icon: ReactNode;
}) {
    return (
        <Card className="border-[var(--brand-green)]/20 bg-gradient-to-br from-white/90 via-white/80 to-[var(--brand-lime)]/10">
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between gap-3">
                    <CardTitle className="text-sm font-semibold text-foreground">
                        {title}
                    </CardTitle>
                    <div className="rounded-lg border border-[var(--brand-green)]/25 bg-[var(--brand-lime)]/12 p-2 text-[var(--brand-green)]">
                        {icon}
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-3xl font-bold tracking-tight text-foreground">
                    {value}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">{subtitle}</p>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    stats,
    kpis,
    compliance_by_area,
    expirations_timeline,
    workers,
    filter_options,
}: Props) {
    const [statusFilter, setStatusFilter] = useState('all');
    const [areaFilter, setAreaFilter] = useState('all');
    const [companyFilter, setCompanyFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [selectedWorker, setSelectedWorker] =
        useState<DashboardWorkerRow | null>(null);
    const [selectedWorkerDocumento, setSelectedWorkerDocumento] =
        useState<WorkerDocumentItem | null>(null);

    const timelineMax = useMemo(
        () => Math.max(1, ...expirations_timeline.map((item) => item.count)),
        [expirations_timeline],
    );

    const filteredWorkers = useMemo(() => {
        const normalizedSearch = search.trim().toLowerCase();

        return workers.filter((worker) => {
            if (statusFilter !== 'all' && worker.status !== statusFilter) {
                return false;
            }

            if (areaFilter !== 'all' && !worker.areas.includes(areaFilter)) {
                return false;
            }

            if (
                companyFilter !== 'all' &&
                worker.contratista.id.toString() !== companyFilter
            ) {
                return false;
            }

            if (!normalizedSearch) {
                return true;
            }

            const searchable = [
                worker.nombre_completo,
                worker.documento,
                worker.contratista.nombre,
                worker.area_principal,
            ]
                .join(' ')
                .toLowerCase();

            return searchable.includes(normalizedSearch);
        });
    }, [areaFilter, companyFilter, search, statusFilter, workers]);

    const filteredSummary = useMemo(() => {
        return {
            workers: filteredWorkers.length,
            critical: filteredWorkers.filter(
                (worker) => worker.status === 'vencido',
            ).length,
            expiring: filteredWorkers.filter(
                (worker) => worker.expiring_soon_total > 0,
            ).length,
        };
    }, [filteredWorkers]);

    const complianceByCompany = useMemo<ComplianceByCompanyItem[]>(() => {
        const companyMap = new Map<number, ComplianceByCompanyItem>();

        workers.forEach((worker) => {
            const companyId = worker.contratista.id;
            const companyName = worker.contratista.nombre || 'Sin contratista';

            const current = companyMap.get(companyId);
            if (!current) {
                companyMap.set(companyId, {
                    id: companyId,
                    nombre: companyName,
                    workers_total: 1,
                    required_total: worker.required_total,
                    loaded_total: worker.loaded_required_total,
                    missing_total: worker.missing_required_total,
                    compliance_percent: 0,
                });
                return;
            }

            current.workers_total += 1;
            current.required_total += worker.required_total;
            current.loaded_total += worker.loaded_required_total;
            current.missing_total += worker.missing_required_total;
        });

        return Array.from(companyMap.values())
            .map((company) => ({
                ...company,
                compliance_percent:
                    company.required_total > 0
                        ? Number(
                              (
                                  (company.loaded_total /
                                      company.required_total) *
                                  100
                              ).toFixed(1),
                          )
                        : 0,
            }))
            .sort(
                (a, b) =>
                    a.compliance_percent - b.compliance_percent ||
                    b.workers_total - a.workers_total ||
                    a.nombre.localeCompare(b.nombre, 'es'),
            );
    }, [workers]);

    const clearFilters = (): void => {
        setStatusFilter('all');
        setAreaFilter('all');
        setCompanyFilter('all');
        setSearch('');
    };

    const onAreaBarClick = (areaName: string): void => {
        setAreaFilter((previous) => (previous === areaName ? 'all' : areaName));
    };

    const onCompanyBarClick = (companyId: string): void => {
        setCompanyFilter((previous) =>
            previous === companyId ? 'all' : companyId,
        );
    };

    const buildDocumentoTrabajadorPreviewUrl = (documentoId: number): string =>
        `/documentos-trabajadores/${documentoId}/preview`;

    const buildDocumentoTrabajadorDownloadUrl = (documentoId: number): string =>
        `/documentos-trabajadores/${documentoId}/download`;

    const adminStats = {
        total_contratistas: 0,
        total_trabajadores: 0,
        documentos_por_aprobar: 0,
        alertas_activas: 0,
        ...stats,
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard de Cumplimiento" />

            <div className="space-y-6">
                <section className="relative overflow-hidden rounded-2xl border border-[var(--brand-green)]/25 bg-gradient-to-r from-white/90 via-white/80 to-[var(--brand-lime)]/12 p-6 shadow-[0_20px_60px_-45px_rgba(3,140,52,0.55)]">
                    <div className="absolute -top-24 -right-12 size-56 rounded-full bg-[var(--brand-orange)]/15 blur-3xl" />
                    <div className="absolute -bottom-20 -left-8 size-52 rounded-full bg-[var(--brand-green)]/20 blur-3xl" />
                    <div className="relative flex flex-wrap items-start justify-between gap-4">
                        <div className="space-y-1">
                            <h1 className="text-3xl font-bold tracking-tight">
                                Mando Integral Documental
                            </h1>
                            <p className="max-w-3xl text-sm text-muted-foreground">
                                Monitoreo en tiempo real del cumplimiento
                                documental de toda la dotación. Click en barras
                                de faena para filtrar automáticamente la tabla de
                                control.
                            </p>
                            <div className="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                <Badge variant="outline">
                                    {adminStats.total_contratistas} contratistas
                                    activos
                                </Badge>
                                <Badge variant="outline">
                                    {adminStats.documentos_por_aprobar}{' '}
                                    pendientes de validación
                                </Badge>
                                <Badge variant="outline">
                                    {adminStats.alertas_activas} alertas activas
                                </Badge>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Link href="/centro-carga">
                                <Button variant="outline">
                                    <UploadCloud className="mr-2 size-4" />
                                    Centro de Carga Trabajadores
                                </Button>
                            </Link>
                            <Link href="/centro-carga-contratistas">
                                <Button className="bg-gradient-to-r from-[var(--brand-green)] via-[var(--brand-forest)] to-[var(--brand-orange)] text-[var(--primary-foreground)]">
                                    <FolderOpen className="mr-2 size-4" />
                                    Centro de Carga Contratistas
                                </Button>
                            </Link>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <KpiCard
                        title="Índice de Cumplimiento"
                        value={`${kpis.compliance_index_percent.toFixed(1)}%`}
                        subtitle={`${kpis.loaded_documents_total}/${kpis.required_documents_total} documentos requeridos`}
                        icon={<CheckCircle2 className="size-4" />}
                    />
                    <KpiCard
                        title="Documentos por Vencer"
                        value={`${kpis.documents_expiring_15_days_total} / ${kpis.documents_expiring_30_days_total}`}
                        subtitle="Próximos 15 y 30 días"
                        icon={<FileClock className="size-4" />}
                    />
                    <KpiCard
                        title="Alertas Críticas"
                        value={String(kpis.critical_alerts_total)}
                        subtitle={`${kpis.expired_documents_total} vencidos · ${kpis.workers_missing_critical_total} con críticos faltantes`}
                        icon={<AlertTriangle className="size-4" />}
                    />
                    <KpiCard
                        title="Cargas Recientes"
                        value={String(kpis.recent_uploads_24h_total)}
                        subtitle="Documentos procesados últimas 24h"
                        icon={<Users className="size-4" />}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                    <Card className="border-[var(--brand-green)]/22">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BarChart3 className="size-5 text-[var(--brand-green)]" />
                                Cumplimiento por Faena
                            </CardTitle>
                            <CardDescription>
                                Porcentaje documental cargado por Faenas activas.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {compliance_by_area.length === 0 ? (
                                <div className="rounded-xl border border-border/70 bg-muted/25 px-4 py-8 text-center text-sm text-muted-foreground">
                                    No hay Faenas activas para mostrar.
                                </div>
                            ) : (
                                compliance_by_area.map((area) => (
                                    <button
                                        key={area.id}
                                        type="button"
                                        onClick={() =>
                                            onAreaBarClick(area.nombre)
                                        }
                                        className={`w-full rounded-xl border px-3 py-3 text-left transition ${
                                            areaFilter === area.nombre
                                                ? 'border-[var(--brand-orange)] bg-[var(--brand-orange)]/10'
                                                : 'border-border/70 bg-white/70 hover:border-[var(--brand-green)]/40 hover:bg-[var(--brand-lime)]/10'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between gap-2 text-sm">
                                            <p className="font-semibold text-foreground">
                                                {area.nombre}
                                            </p>
                                            <p className="font-semibold text-[var(--brand-green)]">
                                                {area.compliance_percent.toFixed(
                                                    1,
                                                )}
                                                %
                                            </p>
                                        </div>
                                        <div className="mt-2 h-2 overflow-hidden rounded-full bg-muted/70">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-[var(--brand-green)] via-[var(--brand-lime)] to-[var(--brand-orange)] transition-all"
                                                style={{
                                                    width: `${Math.max(4, area.compliance_percent)}%`,
                                                }}
                                            />
                                        </div>
                                        <div className="mt-2 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                            <span>
                                                {area.workers_total}{' '}
                                                trabajadores
                                            </span>
                                            <span>
                                                {area.loaded_total}/
                                                {area.required_total} docs
                                            </span>
                                            <span>
                                                {area.missing_total} faltantes
                                            </span>
                                        </div>
                                    </button>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card className="border-[var(--brand-orange)]/25">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CalendarClock className="size-5 text-[var(--brand-orange-strong)]" />
                                    Heatmap de Vencimientos
                                </CardTitle>
                                <CardDescription>
                                    Proyección de vencimientos para próximos 3
                                    meses.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {expirations_timeline.map((point) => {
                                    const strength = Math.max(
                                        10,
                                        Math.round(
                                            (point.count / timelineMax) * 100,
                                        ),
                                    );

                                    return (
                                        <div
                                            key={point.key}
                                            className="rounded-xl border border-border/70 bg-white/70 px-3 py-2"
                                        >
                                            <div className="flex items-center justify-between text-sm">
                                                <p className="font-medium text-foreground">
                                                    {point.label}
                                                </p>
                                                <p className="font-semibold text-[var(--brand-orange-strong)]">
                                                    {point.count}
                                                </p>
                                            </div>
                                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-muted/70">
                                                <div
                                                    className="h-full rounded-full bg-gradient-to-r from-[var(--brand-lime)] to-[var(--brand-orange)]"
                                                    style={{
                                                        width: `${strength}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </CardContent>
                        </Card>

                        <Card className="border-[var(--brand-green)]/25">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Users className="size-5 text-[var(--brand-green)]" />
                                    Avance de Documentos por Contratista
                                </CardTitle>
                                <CardDescription>
                                    Click en una barra para filtrar la tabla por
                                    empresa.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {complianceByCompany.length === 0 ? (
                                    <div className="rounded-xl border border-border/70 bg-muted/25 px-4 py-8 text-center text-sm text-muted-foreground">
                                        No hay contratistas con trabajadores
                                        activos.
                                    </div>
                                ) : (
                                    complianceByCompany.map((company) => (
                                        <button
                                            key={`company-${company.id}`}
                                            type="button"
                                            onClick={() =>
                                                onCompanyBarClick(
                                                    company.id.toString(),
                                                )
                                            }
                                            className={`w-full rounded-lg border px-3 py-2 text-left transition ${
                                                companyFilter ===
                                                company.id.toString()
                                                    ? 'border-[var(--brand-orange)] bg-[var(--brand-orange)]/10'
                                                    : 'border-border/60 bg-white/70 hover:border-[var(--brand-green)]/40'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between gap-2 text-xs">
                                                <span className="truncate font-semibold">
                                                    {company.nombre}
                                                </span>
                                                <span className="font-semibold text-[var(--brand-green)]">
                                                    {company.compliance_percent.toFixed(
                                                        1,
                                                    )}
                                                    %
                                                </span>
                                            </div>
                                            <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted/70">
                                                <div
                                                    className="h-full rounded-full bg-gradient-to-r from-[var(--brand-green)] via-[var(--brand-lime)] to-[var(--brand-orange)]"
                                                    style={{
                                                        width: `${Math.max(4, company.compliance_percent)}%`,
                                                    }}
                                                />
                                            </div>
                                            <div className="mt-2 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                                <span>
                                                    {company.workers_total}{' '}
                                                    trabajadores
                                                </span>
                                                <span>
                                                    {company.loaded_total}/
                                                    {company.required_total}{' '}
                                                    docs
                                                </span>
                                                <span>
                                                    {company.missing_total}{' '}
                                                    faltantes
                                                </span>
                                            </div>
                                        </button>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card className="border-destructive/25">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <FileWarning className="size-5 text-destructive" />
                                    Documentos Vencidos por Faena
                                </CardTitle>
                                <CardDescription>
                                    Click en una barra para filtrar la tabla.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {compliance_by_area.map((area) => {
                                    const maxAlerts = Math.max(
                                        1,
                                        ...compliance_by_area.map(
                                            (row) => row.critical_alerts_total,
                                        ),
                                    );
                                    const width = Math.max(
                                        6,
                                        Math.round(
                                            (area.critical_alerts_total /
                                                maxAlerts) *
                                                100,
                                        ),
                                    );

                                    return (
                                        <button
                                            key={`critical-${area.id}`}
                                            type="button"
                                            onClick={() =>
                                                onAreaBarClick(area.nombre)
                                            }
                                            className={`w-full rounded-lg border px-3 py-2 text-left transition ${
                                                areaFilter === area.nombre
                                                    ? 'border-destructive/60 bg-destructive/10'
                                                    : 'border-border/60 bg-white/70 hover:border-destructive/40'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="font-semibold">
                                                    {area.nombre}
                                                </span>
                                                <span>
                                                    {area.critical_alerts_total}
                                                </span>
                                            </div>
                                            <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted/70">
                                                <div
                                                    className="h-full rounded-full bg-destructive/80"
                                                    style={{
                                                        width: `${width}%`,
                                                    }}
                                                />
                                            </div>
                                        </button>
                                    );
                                })}
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <section className="space-y-4">
                    <Card className="border-[var(--brand-green)]/25">
                        <CardHeader>
                            <CardTitle>
                                Tabla de Control de Trabajadores
                            </CardTitle>
                            <CardDescription>
                                Semáforo documental por trabajador y acceso
                                rápido al expediente.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                                <div className="relative xl:col-span-2">
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        className="pl-9"
                                        placeholder="Buscar por trabajador, RUT, empresa o Faena"
                                    />
                                </div>

                                <Select
                                    value={statusFilter}
                                    onValueChange={setStatusFilter}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {filter_options.status.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={areaFilter}
                                    onValueChange={setAreaFilter}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Faena" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todas las Faenas
                                        </SelectItem>
                                        {filter_options.areas.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={companyFilter}
                                    onValueChange={setCompanyFilter}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Empresa" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todas las empresas
                                        </SelectItem>
                                        {filter_options.empresas.map(
                                            (option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-wrap items-center gap-2 text-xs">
                                <Badge variant="outline">
                                    {filteredSummary.workers} visibles
                                </Badge>
                                <Badge variant="outline">
                                    {filteredSummary.critical} en rojo
                                </Badge>
                                <Badge variant="outline">
                                    {filteredSummary.expiring} por vencer
                                </Badge>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={clearFilters}
                                >
                                    Limpiar filtros
                                </Button>
                            </div>

                            <div className="rounded-xl border border-border/70 bg-white/70 p-2">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Trabajador</TableHead>
                                            <TableHead>Faena</TableHead>
                                            <TableHead>Empresa</TableHead>
                                            <TableHead>Cumplimiento</TableHead>
                                            <TableHead>Riesgo</TableHead>
                                            <TableHead>Semáforo</TableHead>
                                            <TableHead className="text-right">
                                                Acción
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {filteredWorkers.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={7}
                                                    className="py-8 text-center text-sm text-muted-foreground"
                                                >
                                                    Sin resultados para los
                                                    filtros actuales.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            filteredWorkers.map((worker) => {
                                                const status =
                                                    statusConfig[worker.status];

                                                return (
                                                    <TableRow key={worker.id}>
                                                        <TableCell>
                                                            <div className="space-y-0.5">
                                                                <p className="font-semibold">
                                                                    {
                                                                        worker.nombre_completo
                                                                    }
                                                                </p>
                                                                <p className="text-xs text-muted-foreground">
                                                                    {
                                                                        worker.documento
                                                                    }
                                                                </p>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            {
                                                                worker.area_principal
                                                            }
                                                        </TableCell>
                                                        <TableCell className="max-w-[220px] truncate">
                                                            {
                                                                worker
                                                                    .contratista
                                                                    .nombre
                                                            }
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="space-y-1">
                                                                <p className="text-sm font-semibold">
                                                                    {worker.compliance_percent.toFixed(
                                                                        1,
                                                                    )}
                                                                    %
                                                                </p>
                                                                <p className="text-xs text-muted-foreground">
                                                                    {
                                                                        worker.loaded_required_total
                                                                    }
                                                                    /
                                                                    {
                                                                        worker.required_total
                                                                    }
                                                                </p>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="space-y-1 text-xs">
                                                                <p>
                                                                    Faltantes:{' '}
                                                                    {
                                                                        worker.missing_required_total
                                                                    }
                                                                </p>
                                                                <p>
                                                                    Por vencer:{' '}
                                                                    {
                                                                        worker.expiring_soon_total
                                                                    }
                                                                </p>
                                                                <p>
                                                                    Vencidos:{' '}
                                                                    {
                                                                        worker.expired_documents_total
                                                                    }
                                                                </p>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center gap-2">
                                                                <span
                                                                    className={`size-2.5 rounded-full ${status.dotClass}`}
                                                                />
                                                                <Badge
                                                                    className={
                                                                        status.badgeClass
                                                                    }
                                                                >
                                                                    {
                                                                        status.label
                                                                    }
                                                                </Badge>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => {
                                                                    setSelectedWorker(
                                                                        worker,
                                                                    );
                                                                    setSelectedWorkerDocumento(
                                                                        null,
                                                                    );
                                                                }}
                                                            >
                                                                Ver Expediente
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </section>
            </div>

            <Dialog
                open={selectedWorker !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedWorker(null);
                        setSelectedWorkerDocumento(null);
                    }
                }}
            >
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Expediente documental</DialogTitle>
                        <DialogDescription>
                            {selectedWorker
                                ? `${selectedWorker.nombre_completo} · ${selectedWorker.documento}`
                                : 'Detalle documental'}
                        </DialogDescription>
                    </DialogHeader>

                    {selectedWorker && (
                        <div className="space-y-4">
                            <div className="grid gap-3 rounded-xl border border-border/70 bg-muted/20 p-3 md:grid-cols-3">
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Empresa
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {selectedWorker.contratista.nombre}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Faena
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {selectedWorker.area_principal}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Estado
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {
                                            statusConfig[selectedWorker.status]
                                                .label
                                        }
                                    </p>
                                </div>
                            </div>

                            {selectedWorker.critical_missing_names.length >
                                0 && (
                                <div className="rounded-xl border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                                    <p className="font-semibold">
                                        Documentos críticos faltantes:
                                    </p>
                                    <p>
                                        {selectedWorker.critical_missing_names.join(
                                            ', ',
                                        )}
                                    </p>
                                </div>
                            )}

                            <div className="max-h-[420px] space-y-2 overflow-y-auto pr-1">
                                {selectedWorker.documentos.length === 0 ? (
                                    <div className="rounded-xl border border-border/70 bg-muted/25 px-4 py-8 text-center text-sm text-muted-foreground">
                                        No hay documentos cargados para este
                                        trabajador.
                                    </div>
                                ) : (
                                    selectedWorker.documentos.map(
                                        (documento) => {
                                            const expired = isExpired(
                                                documento.fecha_vencimiento,
                                            );

                                            return (
                                                <div
                                                    key={documento.id}
                                                    className="rounded-xl border border-border/70 bg-white/80 px-3 py-3"
                                                >
                                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                                        <div className="space-y-0.5">
                                                            <p className="text-sm font-semibold text-foreground">
                                                                {
                                                                    documento.tipo_documento
                                                                }
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {
                                                                    documento.codigo
                                                                }{' '}
                                                                ·{' '}
                                                                {
                                                                    documento.archivo_nombre_original
                                                                }
                                                            </p>
                                                        </div>
                                                        <Badge
                                                            variant={
                                                                expired
                                                                    ? 'destructive'
                                                                    : 'outline'
                                                            }
                                                        >
                                                            {expired
                                                                ? 'Vencido'
                                                                : 'Vigente'}
                                                        </Badge>
                                                    </div>
                                                    <div className="mt-2 grid gap-2 text-xs text-muted-foreground md:grid-cols-3">
                                                        <p>
                                                            Tamaño:{' '}
                                                            {
                                                                documento.archivo_tamano_kb
                                                            }{' '}
                                                            KB
                                                        </p>
                                                        <p>
                                                            Vencimiento:{' '}
                                                            {formatDate(
                                                                documento.fecha_vencimiento,
                                                            )}
                                                        </p>
                                                        <p>
                                                            Carga:{' '}
                                                            {formatDate(
                                                                documento.cargado_at,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <div className="mt-3 flex flex-wrap items-center gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            type="button"
                                                            onClick={() =>
                                                                setSelectedWorkerDocumento(
                                                                    documento,
                                                                )
                                                            }
                                                        >
                                                            <Eye className="mr-2 size-4" />
                                                            Ver documento
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            asChild
                                                        >
                                                            <a
                                                                href={buildDocumentoTrabajadorDownloadUrl(
                                                                    documento.id,
                                                                )}
                                                            >
                                                                <Download className="mr-2 size-4" />
                                                                Descargar
                                                            </a>
                                                        </Button>
                                                    </div>
                                                </div>
                                            );
                                        },
                                    )
                                )}
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={selectedWorkerDocumento !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedWorkerDocumento(null);
                    }
                }}
            >
                <DialogContent className="max-w-6xl">
                    <DialogHeader>
                        <DialogTitle>Visor de documento</DialogTitle>
                        <DialogDescription>
                            {selectedWorkerDocumento?.archivo_nombre_original ??
                                'Documento'}
                        </DialogDescription>
                    </DialogHeader>

                    {selectedWorkerDocumento && (
                        <div className="space-y-3">
                            <div className="flex justify-end">
                                <Button variant="outline" asChild>
                                    <a
                                        href={buildDocumentoTrabajadorDownloadUrl(
                                            selectedWorkerDocumento.id,
                                        )}
                                    >
                                        <Download className="mr-2 size-4" />
                                        Descargar
                                    </a>
                                </Button>
                            </div>

                            <div className="h-[70vh] overflow-hidden rounded-lg border border-border/70 bg-muted/15">
                                <iframe
                                    src={buildDocumentoTrabajadorPreviewUrl(
                                        selectedWorkerDocumento.id,
                                    )}
                                    title={`Vista previa documento trabajador ${selectedWorkerDocumento.id}`}
                                    className="h-full w-full"
                                />
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Si el navegador no soporta este formato, usa el
                                botón Descargar.
                            </p>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
