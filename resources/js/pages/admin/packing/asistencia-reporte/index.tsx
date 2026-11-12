import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { AppLayout } from '@/layouts/app';
import { Head, router } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    CheckCircle2,
    Clock,
    CopyCheck,
    FileDown,
    Filter,
    RefreshCcw,
    Search,
    User,
} from 'lucide-react';
import type React from 'react';
import { useMemo, useState } from 'react';

type ReportStatus = 'app_control' | 'app_sin_control' | 'control_sin_app';

interface TurnoItem {
    id: number;
    nombre: string;
    inicio: string | null;
    fin: string | null;
    ubicaciones: string[];
}

interface Summary {
    total: number;
    app_control: number;
    app_sin_control: number;
    control_sin_app: number;
    marcaciones_multiples: number;
}

interface TotalByTurno extends Summary {
    turno_id: number;
    turno_nombre: string;
    turno_inicio: string | null;
    turno_fin: string | null;
}

interface TotalByUbicacionPadre extends Summary {
    ubicacion_padre: string;
    contratistas: { contratista: string; total: number }[];
}

interface TotalByUbicacion {
    ubicacion: string;
    ubicacion_padre: string;
    total: number;
    app_control: number;
    app_sin_control: number;
    control_sin_app: number;
}

interface TotalByContratistaPadre {
    ubicacion_padre: string;
    contratista: string;
    total: number;
    app_control: number;
    app_sin_control: number;
    control_sin_app: number;
}

interface MarcacionDetail {
    id: number;
    marcado_en: string | null;
    ubicacion: string | null;
    numero_serie: string | null;
    codigo_qr: string | null;
    device_id: string | null;
    sync_batch_id: string | null;
}

interface ReportRow {
    turno_id: number;
    turno_nombre: string;
    turno_inicio: string | null;
    turno_fin: string | null;
    worker_id: string;
    documento: string;
    nombre: string;
    contratista: string | null;
    departamento_control: string | null;
    group_label: string;
    primera_entrada: string | null;
    ultima_salida: string | null;
    status: ReportStatus;
    status_label: string;
    has_multiple_marks: boolean;
    marcaciones_count: number;
    ubicaciones: string[];
    ubicacion_padre: string | null;
    vale_almuerzo: boolean;
    marcaciones: MarcacionDetail[];
}

interface StatusOption {
    value: ReportStatus | 'multiple';
    label: string;
}

interface Props {
    filters: {
        date: string;
        turno_id: string;
        status: string;
    };
    turnos: TurnoItem[];
    summary: Summary;
    totalsByTurno: TotalByTurno[];
    totalsByUbicacionPadre: TotalByUbicacionPadre[];
    totalsByContratistaPadre: TotalByContratistaPadre[];
    totalsByUbicacion: TotalByUbicacion[];
    rows: ReportRow[];
    statusOptions: StatusOption[];
}

const statusStyles: Record<ReportStatus, string> = {
    app_control:
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
    app_sin_control:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    control_sin_app:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
};

const PADRES = ['Unitec 1', 'Unitec 2', 'Unitec 3'];

const padreColor: Record<string, string> = {
    'Unitec 1': '#3b82f6',
    'Unitec 2': '#10b981',
    'Unitec 3': '#f59e0b',
    'Sin padre': '#6b7280',
};

const statusColor: Record<string, string> = {
    app_control: '#10b981',
    app_sin_control: '#f59e0b',
    control_sin_app: '#ef4444',
};

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    return value.slice(0, 16);
};

const formatTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    return value.slice(11, 19);
};

function StatusBadge({ row }: { row: ReportRow }) {
    return (
        <div className="flex flex-wrap items-center gap-1.5">
            <Badge variant="outline" className={statusStyles[row.status]}>
                {row.status === 'app_control' ? (
                    <CheckCircle2 className="h-3 w-3" />
                ) : (
                    <AlertTriangle className="h-3 w-3" />
                )}
                {row.status_label}
            </Badge>
            {row.has_multiple_marks && (
                <Badge variant="outline" className="border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300">
                    <CopyCheck className="h-3 w-3" />
                    {row.marcaciones_count} marcas
                </Badge>
            )}
        </div>
    );
}

function MarcacionesList({ marcaciones }: { marcaciones: MarcacionDetail[] }) {
    if (marcaciones.length === 0) {
        return <span className="text-muted-foreground">Sin marcacion app</span>;
    }

    return (
        <div className="flex flex-col gap-1.5">
            {marcaciones.map((marcacion) => (
                <div key={marcacion.id} className="rounded-md border bg-muted/30 px-2 py-1.5">
                    <div className="font-medium">{formatTime(marcacion.marcado_en)}</div>
                    <div className="text-xs text-muted-foreground">
                        {marcacion.ubicacion ?? 'Sin ubicacion'}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {marcacion.numero_serie ?? '-'} · {marcacion.device_id ?? '-'}
                    </div>
                </div>
            ))}
        </div>
    );
}

function ReportRowsTable({ rows, emptyLabel }: { rows: ReportRow[]; emptyLabel: string }) {
    return (
        <div className="overflow-x-auto">
            <Table>
                <TableHeader>
                        <TableRow>
                            <TableHead>Turno</TableHead>
                            <TableHead>Trabajador</TableHead>
                            <TableHead>Contratista / Depto.</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Vale almuerzo</TableHead>
                            <TableHead>Control acceso</TableHead>
                            <TableHead>Ubicación</TableHead>
                            <TableHead>Marcaciones app</TableHead>
                        </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={8} className="py-8 text-center text-muted-foreground">
                                {emptyLabel}
                            </TableCell>
                        </TableRow>
                    ) : (
                        rows.map((row) => (
                            <TableRow key={`${row.turno_id}-${row.worker_id}-${row.status}`}>
                                <TableCell>
                                    <div className="font-medium">{row.turno_nombre}</div>
                                    <div className="text-xs text-muted-foreground">
                                        {formatDateTime(row.turno_inicio)} - {formatDateTime(row.turno_fin)}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="font-medium">{row.nombre}</div>
                                    <div className="text-xs text-muted-foreground">{row.documento}</div>
                                </TableCell>
                                <TableCell>
                                    <div>{row.group_label}</div>
                                    {row.contratista && row.departamento_control && (
                                        <div className="text-xs text-muted-foreground">
                                            Control: {row.departamento_control}
                                        </div>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <StatusBadge row={row} />
                                </TableCell>
                                <TableCell className="text-center">
                                    {row.vale_almuerzo ? (
                                        <Badge variant="outline" className="text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900">
                                            <CheckCircle2 className="mr-1 h-3 w-3" />
                                            Sí
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline" className="text-muted-foreground">
                                            No
                                        </Badge>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <div className="flex flex-col gap-1 text-sm">
                                        <span>Entrada: {formatTime(row.primera_entrada)}</span>
                                        <span>Salida: {formatTime(row.ultima_salida)}</span>
                                    </div>
                                </TableCell>
                                <TableCell className="min-w-48">
                                    {row.ubicaciones.length === 0 ? (
                                        <span className="text-muted-foreground">-</span>
                                    ) : (
                                        <div className="flex flex-col gap-1">
                                            {row.ubicaciones.map((ubicacion, index) => (
                                                <span key={index} className="text-sm">
                                                    {ubicacion}
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                </TableCell>
                                <TableCell className="min-w-64">
                                    <MarcacionesList marcaciones={row.marcaciones} />
                                </TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
        </div>
    );
}

interface KpiCardProps {
    label: string;
    value: number | string;
    icon?: React.ReactNode;
    color?: string;
    subtitle?: string;
}

function KpiCard({ label, value, icon, color, subtitle }: KpiCardProps) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardDescription className="text-sm">{label}</CardDescription>
                <div className="flex items-center gap-2">
                    <CardTitle className={`text-3xl font-bold ${color ? `text-${color}` : ''}`}>
                        {icon && <span className="mr-2">{icon}</span>}
                        {value}
                    </CardTitle>
                </div>
                {subtitle && <CardDescription className="text-xs">{subtitle}</CardDescription>}
            </CardHeader>
        </Card>
    );
}

interface BarChartProps {
    title: string;
    data: { label: string; value: number }[];
    color: string;
}

function BarChart({ title, data, color }: BarChartProps) {
    const max = Math.max(0, ...data.map((d) => d.value));

    return (
        <Card className="h-full">
            <CardHeader>
                <CardTitle className="text-sm">{title}</CardTitle>
            </CardHeader>
            <CardContent className="p-4">
                <div className="flex items-end justify-between gap-2 h-[calc(100%-48px)] pb-4">
                    {data.map((item) => {
                        const percent = max > 0 ? (item.value / max) * 100 : 0;
                        return (
                            <div key={item.label} className="flex flex-col items-center gap-2">
                                <div
                                    className="w-[36px] rounded-t-md transition-origin"
                                    style={{
                                        backgroundColor: color,
                                        height: `${percent}%`,
                                        transformOrigin: 'bottom',
                                    }}
                                />
                                <div className="text-xs font-medium text-foreground">
                                    {item.value}
                                </div>
                                <div
                                    className="text-[10px] text-muted-foreground text-center leading-none"
                                    style={{ maxWidth: '36px', overflow: 'hidden', textOverflow: 'ellipsis' }}
                                >
                                    {item.label}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}

interface DonutChartProps {
    title: string;
    data: { label: string; value: number; color: string }[];
    total: number;
}

function DonutChart({ title, data, total }: DonutChartProps) {
    const cx = 110;
    const cy = 110;
    const r = 70;
    const strokeWidth = 16;

    const slices = data.map((d, i) => {
        const sweep = (d.value / total) * 360;
        const startAngle = data.slice(0, i).reduce((sum, item) => sum + (item.value / total) * 360, 0);
        const endRad = ((startAngle + sweep) * Math.PI) / 180;
        const x2 = cx + r * Math.cos(endRad);
        const y2 = cy + r * Math.sin(endRad);
        const largeArc = sweep > 180 ? 1 : 0;
        const pathD = [
            `M ${cx + r} ${cy}`,
            `A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2}`,
        ].join(' ');
        return { pathD, color: d.color, label: d.label, value: d.value };
    });

    return (
        <Card className="h-full">
            <CardHeader>
                <CardTitle className="text-sm">{title}</CardTitle>
            </CardHeader>
            <CardContent className="p-4">
                <div className="flex items-center justify-center">
                    <svg width={220} height={220} viewBox="0 0 220 220">
                        <circle cx={cx} cy={cy} r={r} fill="none" stroke="#e5e7eb" strokeWidth={strokeWidth} />
                        {slices.map((slice, i) => (
                            <path
                                key={i}
                                d={slice.pathD}
                                fill="none"
                                stroke={slice.color}
                                strokeWidth={strokeWidth}
                                strokeLinecap="round"
                            />
                        ))}
                        <text x={cx} y={cy + 6} textAnchor="middle" className="text-xs font-bold text-foreground">
                            {total}
                        </text>
                    </svg>
                    <div className="ml-4 space-y-1">
                        {data.map((d) => {
                            const pct = total > 0 ? ((d.value / total) * 100).toFixed(0) : '0';
                            return (
                                <div key={d.label} className="flex items-center gap-2">
                                    <div className="h-3 w-3 rounded-full" style={{ backgroundColor: d.color }} />
                                    <div className="text-xs text-foreground">{d.label}</div>
                                    <div className="text-xs text-muted-foreground">{pct}%</div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function PadrePanel({
    padre,
    datos,
    contratistasPadre,
    totalGlobal,
}: {
    padre: string;
    datos: TotalByUbicacionPadre;
    contratistasPadre: TotalByContratistaPadre[];
    totalGlobal: number;
}) {
    const pct = totalGlobal > 0 ? ((datos.total / totalGlobal) * 100).toFixed(0) : '0';

    return (
        <Card className="h-full">
            <CardHeader className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <div
                        className="h-8 w-8 rounded-full flex items-center justify-center text-white font-bold text-sm"
                        style={{ backgroundColor: padreColor[padre] ?? '#6b7280' }}
                    >
                        {padre.charAt(0)}
                    </div>
                    <div>
                        <CardTitle className="text-sm">{padre}</CardTitle>
                        <CardDescription className="text-xs text-muted-foreground">
                            {datos.total} personal ({pct}% del total)
                        </CardDescription>
                    </div>
                </div>
                <Badge variant="outline" className="px-2 py-1 text-xs">
                    Área operativa
                </Badge>
            </CardHeader>
            <CardContent className="p-4">
                <div className="grid gap-4 md:grid-cols-4">
                    <KpiCard label="Total" value={datos.total} icon={<User />} color="foreground" />
                    <KpiCard
                        label="App + control"
                        value={datos.app_control}
                        icon={<CheckCircle2 className="h-4 w-4" />}
                        color="emerald-600"
                    />
                    <KpiCard
                        label="App sin control"
                        value={datos.app_sin_control}
                        icon={<AlertTriangle className="h-4 w-4" />}
                        color="amber-600"
                    />
                    <KpiCard
                        label="Control sin app"
                        value={datos.control_sin_app}
                        icon={<AlertTriangle className="h-4 w-4" />}
                        color="red-600"
                    />
                </div>
                <div className="mt-6 grid gap-4 md:grid-cols-2">
                    <BarChart
                        title="Personal por contratista"
                        data={contratistasPadre.map((c) => ({ label: c.contratista, value: c.total }))}
                        color={padreColor[padre] ?? '#6b7280'}
                    />
                    <DonutChart
                        title="Distribución por estado"
                        data={[
                            {
                                label: 'App + control',
                                value: datos.app_control,
                                color: statusColor.app_control,
                            },
                            {
                                label: 'App sin control',
                                value: datos.app_sin_control,
                                color: statusColor.app_sin_control,
                            },
                            {
                                label: 'Control sin app',
                                value: datos.control_sin_app,
                                color: statusColor.control_sin_app,
                            },
                        ]}
                        total={datos.total}
                    />
                </div>
            </CardContent>
        </Card>
    );
}

export default function PackingAttendanceReportIndex({
    filters,
    turnos,
    summary,
    totalsByTurno,
    totalsByUbicacionPadre,
    totalsByContratistaPadre,
    totalsByUbicacion,
    rows,
    statusOptions,
}: Props) {
    const [date, setDate] = useState(filters.date);
    const [turnoId, setTurnoId] = useState(filters.turno_id || 'all');
    const [status, setStatus] = useState(filters.status || 'all');

    const issueRows = useMemo(
        () => rows.filter((row) => row.status !== 'app_control'),
        [rows],
    );
    const multipleRows = useMemo(
        () => rows.filter((row) => row.has_multiple_marks),
        [rows],
    );
    const enPlantaCount = useMemo(
        () => rows.filter((row) => !row.ultima_salida).length,
        [rows],
    );

    const padresData = useMemo(() => {
        const map = new Map<string, TotalByUbicacionPadre>();
        totalsByUbicacionPadre.forEach((d) => map.set(d.ubicacion_padre, d));
        return PADRES.map((p) => map.get(p) ?? { ubicacion_padre: p, total: 0, app_control: 0, app_sin_control: 0, control_sin_app: 0, marcaciones_multiples: 0, contratistas: [] });
    }, [totalsByUbicacionPadre]);

    const contratistasByPadre = useMemo(() => {
        const map = new Map<string, TotalByContratistaPadre[]>();
        totalsByContratistaPadre.forEach((c) => {
            const list = map.get(c.ubicacion_padre) ?? [];
            list.push(c);
            map.set(c.ubicacion_padre, list);
        });
        return map;
    }, [totalsByContratistaPadre]);

    const handleSearch = () => {
        router.get(
            '/admin/packing/asistencia-reporte',
            {
                date: date || undefined,
                turno_id: turnoId !== 'all' ? turnoId : undefined,
                status: status !== 'all' ? status : undefined,
            },
            { preserveScroll: true, preserveState: true },
        );
    };

    const exportUrl = useMemo(() => {
        const params = new URLSearchParams();
        params.set('date', date);
        if (turnoId !== 'all') params.set('turno_id', turnoId);
        if (status !== 'all') params.set('status', status);
        return `/admin/packing/asistencia-reporte/export?${params.toString()}`;
    }, [date, turnoId, status]);

    const handleClear = () => {
        setDate(filters.date);
        setTurnoId('all');
        setStatus('all');

        router.get(
            '/admin/packing/asistencia-reporte',
            { date: filters.date },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <>
            <Head title="Reporte asistencia packing" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Reporte asistencia packing
                        </h1>
                        <p className="text-muted-foreground">
                            Distribución de personal por ubicación padre (Unitec 1, 2, 3) y contratista.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a href={exportUrl}>
                                <FileDown className="mr-2 h-4 w-4" />
                                Exportar
                            </a>
                        </Button>
                        <Button variant="outline" onClick={handleSearch}>
                            <RefreshCcw className="mr-2 h-4 w-4" />
                            Actualizar
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Fecha operacional, turno y estado de cuadratura.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr,1fr,1fr,auto]">
                            <div className="space-y-2">
                                <Label htmlFor="date">Fecha</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={date}
                                    onChange={(event) => setDate(event.target.value)}
                                    onKeyDown={(event) => event.key === 'Enter' && handleSearch()}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="turno">Turno</Label>
                                <Select value={turnoId} onValueChange={setTurnoId}>
                                    <SelectTrigger id="turno">
                                        <SelectValue placeholder="Todos los turnos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los turnos</SelectItem>
                                        {turnos.map((turno) => (
                                            <SelectItem key={turno.id} value={turno.id.toString()}>
                                                {turno.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="status">Estado</Label>
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los estados</SelectItem>
                                        {statusOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end gap-2">
                                <Button onClick={handleSearch}>
                                    <Search className="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                                <Button variant="outline" onClick={handleClear}>
                                    <Filter className="mr-2 h-4 w-4" />
                                    Limpiar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <KpiCard label="Total personal" value={summary.total} icon={<User />} />
                    <KpiCard
                        label="Unitec 1"
                        value={padresData[0].total}
                        icon={<BarChart3 className="h-4 w-4" />}
                        color="blue-600"
                    />
                    <KpiCard
                        label="Unitec 2"
                        value={padresData[1].total}
                        icon={<BarChart3 className="h-4 w-4" />}
                        color="emerald-600"
                    />
                    <KpiCard
                        label="Unitec 3"
                        value={padresData[2].total}
                        icon={<BarChart3 className="h-4 w-4" />}
                        color="amber-600"
                    />
                    <KpiCard
                        label="App + control"
                        value={summary.app_control}
                        icon={<CheckCircle2 className="h-4 w-4" />}
                        color="emerald-600"
                    />
                    <KpiCard
                        label="App sin control"
                        value={summary.app_sin_control}
                        icon={<AlertTriangle className="h-4 w-4" />}
                        color="amber-600"
                    />
                    <KpiCard
                        label="Control sin app"
                        value={summary.control_sin_app}
                        icon={<AlertTriangle className="h-4 w-4" />}
                        color="red-600"
                    />
                    <KpiCard
                        label="Multiples marcas"
                        value={summary.marcaciones_multiples}
                        icon={<CopyCheck className="h-4 w-4" />}
                        color="sky-600"
                    />
                    <KpiCard
                        label="En planta"
                        value={enPlantaCount}
                        icon={<Clock className="h-4 w-4" />}
                        color="blue-600"
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-3">
                    {padresData.map((datos) => (
                        <PadrePanel
                            key={datos.ubicacion_padre}
                            padre={datos.ubicacion_padre}
                            datos={datos}
                            contratistasPadre={contratistasByPadre.get(datos.ubicacion_padre) ?? []}
                            totalGlobal={summary.total}
                        />
                    ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen por turno</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Turno</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>App + Control</TableHead>
                                        <TableHead>App sin control</TableHead>
                                        <TableHead>Control sin app</TableHead>
                                        <TableHead>Multiples</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {totalsByTurno.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                                Sin registros por turno.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        totalsByTurno.map((item) => (
                                            <TableRow key={item.turno_id}>
                                                <TableCell>
                                                    <div className="font-medium">{item.turno_nombre}</div>
                                                    <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                        <Clock className="h-3 w-3" />
                                                        {formatDateTime(item.turno_inicio)} - {formatDateTime(item.turno_fin)}
                                                    </div>
                                                </TableCell>
                                                <TableCell>{item.total}</TableCell>
                                                <TableCell>{item.app_control}</TableCell>
                                                <TableCell>{item.app_sin_control}</TableCell>
                                                <TableCell>{item.control_sin_app}</TableCell>
                                                <TableCell>{item.marcaciones_multiples}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen por contratista / padre</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Ubicación padre</TableHead>
                                        <TableHead>Contratista</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>App + Control</TableHead>
                                        <TableHead>App sin control</TableHead>
                                        <TableHead>Control sin app</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {totalsByContratistaPadre.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                                Sin registros.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        totalsByContratistaPadre.map((item) => (
                                            <TableRow key={`${item.ubicacion_padre}-${item.contratista}`}>
                                                <TableCell className="font-medium">{item.ubicacion_padre}</TableCell>
                                                <TableCell>{item.contratista}</TableCell>
                                                <TableCell>{item.total}</TableCell>
                                                <TableCell>{item.app_control}</TableCell>
                                                <TableCell>{item.app_sin_control}</TableCell>
                                                <TableCell>{item.control_sin_app}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Distribución por ubicación / sububicación</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Ubicación</TableHead>
                                        <TableHead>Sububicación (padre)</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>App + Control</TableHead>
                                        <TableHead>App sin control</TableHead>
                                        <TableHead>Control sin app</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {totalsByUbicacion.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                                Sin registros.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        totalsByUbicacion.map((item) => (
                                            <TableRow key={`${item.ubicacion_padre}-${item.ubicacion}`}>
                                                <TableCell className="font-medium">{item.ubicacion}</TableCell>
                                                <TableCell>{item.ubicacion_padre}</TableCell>
                                                <TableCell>{item.total}</TableCell>
                                                <TableCell>{item.app_control}</TableCell>
                                                <TableCell>{item.app_sin_control}</TableCell>
                                                <TableCell>{item.control_sin_app}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Inconsistencias</CardTitle>
                        <CardDescription>
                            Casos con app sin control o control sin app.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <ReportRowsTable rows={issueRows} emptyLabel="Sin inconsistencias." />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Marcaciones multiples</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <ReportRowsTable rows={multipleRows} emptyLabel="Sin marcaciones multiples." />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalle completo</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <ReportRowsTable rows={rows} emptyLabel="Sin registros para los filtros seleccionados." />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PackingAttendanceReportIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
