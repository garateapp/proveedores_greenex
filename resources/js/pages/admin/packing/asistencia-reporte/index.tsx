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
    CheckCircle2,
    Clock,
    CopyCheck,
    FileDown,
    Filter,
    RefreshCcw,
    Search,
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

interface TotalByGroup extends Summary {
    group_label: string;
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
    totalsByGroup: TotalByGroup[];
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
                        <TableHead>Control acceso</TableHead>
                        <TableHead>Marcaciones app</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
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
                                <TableCell>
                                    <div className="flex flex-col gap-1 text-sm">
                                        <span>Entrada: {formatTime(row.primera_entrada)}</span>
                                        <span>Salida: {formatTime(row.ultima_salida)}</span>
                                    </div>
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

export default function PackingAttendanceReportIndex({
    filters,
    turnos,
    summary,
    totalsByTurno,
    totalsByGroup,
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
                            Cruce entre marcaciones app, control de acceso y turnos configurados.
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

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total</CardDescription>
                            <CardTitle className="text-3xl">{summary.total}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>App + control</CardDescription>
                            <CardTitle className="text-3xl text-emerald-700 dark:text-emerald-300">
                                {summary.app_control}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>App sin control</CardDescription>
                            <CardTitle className="text-3xl text-amber-700 dark:text-amber-300">
                                {summary.app_sin_control}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Control sin app</CardDescription>
                            <CardTitle className="text-3xl text-red-700 dark:text-red-300">
                                {summary.control_sin_app}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Multiples marcas</CardDescription>
                            <CardTitle className="text-3xl text-sky-700 dark:text-sky-300">
                                {summary.marcaciones_multiples}
                            </CardTitle>
                        </CardHeader>
                    </Card>
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
                            <CardTitle>Resumen por contratista/departamento</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Grupo</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>App + Control</TableHead>
                                        <TableHead>App sin control</TableHead>
                                        <TableHead>Control sin app</TableHead>
                                        <TableHead>Multiples</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {totalsByGroup.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                                Sin registros por grupo.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        totalsByGroup.map((item) => (
                                            <TableRow key={item.group_label}>
                                                <TableCell className="font-medium">{item.group_label}</TableCell>
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
