import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FileText, UploadCloud } from 'lucide-react';
import { type ReactElement } from 'react';

interface Contratista {
    id: number;
    razon_social: string;
    nombre_fantasia: string | null;
}

interface Faena {
    id: number;
    nombre: string;
    codigo: string;
    estado: 'activa' | 'inactiva' | 'finalizada';
    pivot?: {
        fecha_asignacion: string | null;
        fecha_desasignacion: string | null;
    };
}

interface Trabajador {
    id: string;
    documento: string;
    nombre: string;
    apellido: string;
    email: string | null;
    telefono: string | null;
    estado: 'activo' | 'inactivo';
    fecha_ingreso: string | null;
    observaciones: string | null;
    contratista: Contratista | null;
    faenas: Faena[];
}

interface Props {
    trabajador: Trabajador;
}

const breadcrumbs = (id: string): BreadcrumbItem[] => [
    { title: 'Personal', href: '/trabajadores' },
    { title: `Detalle ${id}`, href: `/trabajadores/${id}` },
];

const estadoFaenaBadge = (estado: Faena['estado']) => {
    switch (estado) {
        case 'activa':
            return 'default' as const;
        case 'inactiva':
            return 'secondary' as const;
        case 'finalizada':
            return 'outline' as const;
        default:
            return 'secondary' as const;
    }
};

export default function TrabajadorShow({ trabajador }: Props) {
    return (
        <>
            <Head title={`Trabajador ${trabajador.nombre}`} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <Link href="/trabajadores">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Link href={`/centro-carga?trabajador_id=${trabajador.id}`}>
                        <Button variant="secondary" size="sm">
                            <UploadCloud className="mr-2 h-4 w-4" />
                            Centro de Carga
                        </Button>
                    </Link>
                    <Link href={`/trabajadores/${trabajador.id}/firmas-documentos`}>
                        <Button variant="secondary" size="sm">
                            <FileText className="mr-2 h-4 w-4" />
                            Firmas Digitales
                        </Button>
                    </Link>
                    <Link href={`/trabajadores/${trabajador.id}/edit`}>
                        <Button size="sm">Editar</Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {trabajador.nombre} {trabajador.apellido}
                        </CardTitle>
                        <CardDescription>RUT: {trabajador.documento}</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div>
                            <p className="text-sm text-muted-foreground">Estado</p>
                            <Badge variant={trabajador.estado === 'activo' ? 'default' : 'secondary'}>
                                {trabajador.estado}
                            </Badge>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Fecha de ingreso</p>
                            <p className="font-medium">
                                {trabajador.fecha_ingreso
                                    ? new Date(trabajador.fecha_ingreso).toLocaleDateString('es-CL')
                                    : '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Email</p>
                            <p className="font-medium">{trabajador.email || '—'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Teléfono</p>
                            <p className="font-medium">{trabajador.telefono || '—'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <p className="text-sm text-muted-foreground">Contratista</p>
                            <p className="font-medium">
                                {trabajador.contratista
                                    ? trabajador.contratista.nombre_fantasia ||
                                      trabajador.contratista.razon_social
                                    : '—'}
                            </p>
                        </div>
                        <div className="md:col-span-2">
                            <p className="text-sm text-muted-foreground">Observaciones</p>
                            <p className="font-medium">{trabajador.observaciones || '—'}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Faenas asignadas</CardTitle>
                        <CardDescription>Asignaciones vigentes e históricas del trabajador.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Faena</TableHead>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Asignación</TableHead>
                                    <TableHead>Desasignación</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {trabajador.faenas.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-muted-foreground">
                                            Sin faenas asignadas.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    trabajador.faenas.map((faena) => (
                                        <TableRow key={faena.id}>
                                            <TableCell className="font-medium">{faena.nombre}</TableCell>
                                            <TableCell>{faena.codigo}</TableCell>
                                            <TableCell>
                                                <Badge variant={estadoFaenaBadge(faena.estado)}>
                                                    {faena.estado}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {faena.pivot?.fecha_asignacion
                                                    ? new Date(
                                                          faena.pivot.fecha_asignacion,
                                                      ).toLocaleDateString('es-CL')
                                                    : '—'}
                                            </TableCell>
                                            <TableCell>
                                                {faena.pivot?.fecha_desasignacion
                                                    ? new Date(
                                                          faena.pivot.fecha_desasignacion,
                                                      ).toLocaleDateString('es-CL')
                                                    : '—'}
                                            </TableCell>
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

TrabajadorShow.layout = (page: ReactElement<Props>) => (
    <AppLayout breadcrumbs={breadcrumbs(page.props.trabajador.id)}>{page}</AppLayout>
);
