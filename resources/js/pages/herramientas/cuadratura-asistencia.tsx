import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, type ReactNode } from 'react';
import { FileText, Upload } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Herramientas', href: '/herramientas/cuadratura-asistencia' },
    { title: 'Cuadratura asistencia', href: '/herramientas/cuadratura-asistencia' },
];

interface CuadraturaRow {
    numero: number;
    rut: string;
    apellido_paterno: string;
    apellido_materno: string;
    nombres: string;
    dias_trabajados: number;
    dias_asistencia: number | null;
    diferencia: number | null;
    estado: 'coincide' | 'difiere' | 'sin_datos';
}

interface CuadraturaSummary {
    nombre_archivo: string;
    periodo_mes: number | null;
    periodo_ano: number | null;
    total_registros: number;
    entidad_id: number;
    entidad_nombre: string | null;
    mes_consultado: number;
}

interface ComparisonSummary {
    total_registros: number;
    total_coinciden: number;
    total_difieren: number;
    total_sin_datos: number;
}

interface EntidadOption {
    id: number;
    nombre: string;
}

interface Filters {
    entidad_id: number | null;
    mes: number | null;
}

interface Props {
    rows: CuadraturaRow[];
    summary: CuadraturaSummary | null;
    comparisonSummary: ComparisonSummary | null;
    entidades: EntidadOption[];
    entidadesError: string | null;
    filters: Filters;
}

export default function CuadraturaAsistencia({
    rows,
    summary,
    comparisonSummary,
    entidades,
    entidadesError,
    filters,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        archivo: null as File | null,
        entidad_id: filters.entidad_id ? String(filters.entidad_id) : '',
        mes: filters.mes ? String(filters.mes) : '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/herramientas/cuadratura-asistencia', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Cuadratura asistencia" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Cuadratura asistencia</h1>
                    <p className="text-muted-foreground">
                        Carga un PDF de cotizaciones FONASA para extraer RUT, apellidos, nombres y días
                        trabajados del anexo detalle.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Lectura de planilla</CardTitle>
                        <CardDescription>
                            Carga PDF y cruza con asistencia de Greenexnet por entidad y mes.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="grid gap-4 md:grid-cols-4" onSubmit={submit}>
                            <div className="grid gap-2">
                                <Label htmlFor="entidad_id">Entidad</Label>
                                <select
                                    id="entidad_id"
                                    className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    value={data.entidad_id}
                                    onChange={(event) => setData('entidad_id', event.target.value)}
                                >
                                    <option value="">Selecciona una entidad</option>
                                    {entidades.map((entidad) => (
                                        <option key={entidad.id} value={entidad.id}>
                                            {entidad.nombre}
                                        </option>
                                    ))}
                                </select>
                                {errors.entidad_id && (
                                    <p className="text-sm text-destructive">{errors.entidad_id}</p>
                                )}
                                {entidadesError && (
                                    <p className="text-sm text-destructive">{entidadesError}</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="mes">Mes a consultar</Label>
                                <Input
                                    id="mes"
                                    type="number"
                                    min={1}
                                    max={12}
                                    value={data.mes}
                                    onChange={(event) => setData('mes', event.target.value)}
                                />
                                {errors.mes && <p className="text-sm text-destructive">{errors.mes}</p>}
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="archivo">PDF de cotizaciones</Label>
                                <Input
                                    id="archivo"
                                    type="file"
                                    accept="application/pdf"
                                    onChange={(event) =>
                                        setData('archivo', event.currentTarget.files?.[0] ?? null)
                                    }
                                />
                                {errors.archivo && (
                                    <p className="text-sm text-destructive">{errors.archivo}</p>
                                )}
                            </div>

                            <div className="md:col-span-4">
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        data.archivo === null ||
                                        data.entidad_id === '' ||
                                        data.mes === ''
                                    }
                                >
                                    <Upload className="mr-2 h-4 w-4" />
                                    {processing ? 'Procesando...' : 'Procesar y comparar'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {summary && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                Resultado de extracción
                            </CardTitle>
                            <CardDescription>
                                Archivo: {summary.nombre_archivo} | Registros: {summary.total_registros}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm text-muted-foreground md:grid-cols-4">
                            <p>
                                <span className="font-medium text-foreground">Período mes:</span>{' '}
                                {summary.periodo_mes ?? 'No detectado'}
                            </p>
                            <p>
                                <span className="font-medium text-foreground">Período año:</span>{' '}
                                {summary.periodo_ano ?? 'No detectado'}
                            </p>
                            <p>
                                <span className="font-medium text-foreground">Total filas:</span>{' '}
                                {summary.total_registros}
                            </p>
                            <p>
                                <span className="font-medium text-foreground">Entidad:</span>{' '}
                                {summary.entidad_nombre ?? `ID ${summary.entidad_id}`}
                            </p>
                            <p>
                                <span className="font-medium text-foreground">Mes consultado:</span>{' '}
                                {summary.mes_consultado}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {comparisonSummary && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen de comparación</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm text-muted-foreground md:grid-cols-4">
                            <p>
                                <span className="font-medium text-foreground">Total:</span>{' '}
                                {comparisonSummary.total_registros}
                            </p>
                            <p>
                                <span className="font-medium text-foreground">Coinciden:</span>{' '}
                                {comparisonSummary.total_coinciden}
                            </p>
                            <p>
                                <span className="font-medium text-foreground">Difieren:</span>{' '}
                                {comparisonSummary.total_difieren}
                            </p>
                            <p>
                                <span className="font-medium text-foreground">Sin datos:</span>{' '}
                                {comparisonSummary.total_sin_datos}
                            </p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Detalle de anexo</CardTitle>
                        <CardDescription>
                            Campos extraídos: R.U.T C.I. (Con Dig. Verif), Apellido Paterno, Apellido
                            Materno, Nombres y comparación de días trabajados.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="overflow-x-auto p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[80px]">N°</TableHead>
                                    <TableHead>R.U.T C.I. (Con Dig. Verif)</TableHead>
                                    <TableHead>Apellido Paterno</TableHead>
                                    <TableHead>Apellido Materno</TableHead>
                                    <TableHead>Nombres</TableHead>
                                    <TableHead className="w-[140px]">Días PDF</TableHead>
                                    <TableHead className="w-[150px]">Días asistencia</TableHead>
                                    <TableHead className="w-[120px]">Diferencia</TableHead>
                                    <TableHead className="w-[120px]">Estado</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={10} className="py-8 text-center">
                                            Sin datos extraídos todavía.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    rows.map((row) => (
                                        <TableRow key={`${row.numero}-${row.rut}`}>
                                            <TableCell>{row.numero}</TableCell>
                                            <TableCell>{row.rut}</TableCell>
                                            <TableCell>{row.apellido_paterno}</TableCell>
                                            <TableCell>{row.apellido_materno || '—'}</TableCell>
                                            <TableCell>{row.nombres}</TableCell>
                                            <TableCell>{row.dias_trabajados}</TableCell>
                                            <TableCell>{row.dias_asistencia ?? '—'}</TableCell>
                                            <TableCell>{row.diferencia ?? '—'}</TableCell>
                                            <TableCell className="capitalize">{row.estado}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CuadraturaAsistencia.layout = (page: ReactNode) => <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>;
