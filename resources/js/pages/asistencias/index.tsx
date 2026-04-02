import { Head, Link, router, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Asistencias', href: '/asistencias' },
];

interface Asistencia {
    id: number;
    trabajador: { nombre_completo: string; documento: string };
    faena?: { nombre: string } | null;
    tipo: string;
    fecha_hora: string;
    ubicacion_texto?: string | null;
    registrado_por?: { name: string } | null;
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    asistencias: Pagination<Asistencia>;
    filters: {
        trabajador_id?: string;
        faena_id?: string;
        start_date?: string;
        end_date?: string;
        tipo?: string;
    };
}

export default function AsistenciasIndex({ asistencias, filters }: Props) {
    const [tipo, setTipo] = useState(filters.tipo ?? '');
    const [startDate, setStartDate] = useState(filters.start_date ?? '');
    const [endDate, setEndDate] = useState(filters.end_date ?? '');
    const page = usePage<SharedData>();

    const handleFilter = () => {
        router.get(
            '/asistencias',
            {
                ...filters,
                tipo: tipo || undefined,
                start_date: startDate || undefined,
                end_date: endDate || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Asistencias" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Asistencias</h1>
                        <p className="text-muted-foreground">
                            Control de marca de entrada y salida de trabajadores.
                        </p>
                    </div>
                    <Link href="/asistencias/create">
                        <Button>Registrar asistencia</Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-4">
                        <Input
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            placeholder="Fecha inicio"
                        />
                        <Input
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            placeholder="Fecha fin"
                        />
                        <select
                            className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            value={tipo}
                            onChange={(e) => setTipo(e.target.value)}
                        >
                            <option value="">Todos los tipos</option>
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                        </select>
                        <Button onClick={handleFilter}>Aplicar filtros</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Trabajador</TableHead>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Fecha/Hora</TableHead>
                                    <TableHead>Faena</TableHead>
                                    <TableHead>Ubicación</TableHead>
                                    <TableHead>Registrado por</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {asistencias.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center">
                                            No hay asistencias registradas
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    asistencias.data.map((a) => (
                                        <TableRow key={a.id}>
                                            <TableCell className="font-medium">
                                                {a.trabajador?.nombre_completo ?? 'N/A'} ({a.trabajador?.documento})
                                            </TableCell>
                                            <TableCell className="capitalize">{a.tipo}</TableCell>
                                            <TableCell>{a.fecha_hora}</TableCell>
                                            <TableCell>{a.faena?.nombre ?? '—'}</TableCell>
                                            <TableCell>{a.ubicacion_texto ?? '—'}</TableCell>
                                            <TableCell>{a.registrado_por?.name ?? '—'}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
