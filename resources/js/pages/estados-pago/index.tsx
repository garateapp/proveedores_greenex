import { Head, Link, router, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Estados de Pago', href: '/estados-pago' },
];

interface EstadoPago {
    id: number;
    numero_documento: string;
    estado: string;
    fecha_documento: string;
    monto: string | number;
    contratista?: { razon_social: string };
    actualizado_por?: { name: string };
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
}

interface Props {
    estadosPago: Pagination<EstadoPago>;
    filters: { estado?: string; ano?: string };
}

export default function EstadosPagoIndex({ estadosPago, filters }: Props) {
    const [estado, setEstado] = useState(filters.estado ?? '');
    const [ano, setAno] = useState(filters.ano ?? '');
    const page = usePage<SharedData>();
    const isAdmin = page.props.auth?.user?.isAdmin ?? false;

    const handleDelete = (id: number, numero: string) => {
        if (!confirm(`¿Eliminar el estado de pago ${numero}?`)) {
            return;
        }

        router.delete(`/estados-pago/${id}`, {
            preserveScroll: true,
        });
    };

    const handleFilter = () => {
        router.get(
            '/estados-pago',
            { estado: estado || undefined, ano: ano || undefined },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Estados de Pago" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Estados de Pago</h1>
                        <p className="text-muted-foreground">Seguimiento de facturas y pagos.</p>
                    </div>
                    {isAdmin && (
                        <Link href="/estados-pago/create">
                            <Button>Registrar documento</Button>
                        </Link>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>Filtre por estado o año.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <select
                            className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            value={estado}
                            onChange={(e) => setEstado(e.target.value)}
                        >
                            <option value="">Todos los estados</option>
                            <option value="recibido">Recibido</option>
                            <option value="en_revision">En revisión</option>
                            <option value="aprobado_pago">Aprobado para pago</option>
                            <option value="retenido">Retenido</option>
                            <option value="pagado">Pagado</option>
                            <option value="rechazado">Rechazado</option>
                        </select>
                        <Input
                            placeholder="Año (ej: 2025)"
                            value={ano}
                            onChange={(e) => setAno(e.target.value)}
                        />
                        <Button onClick={handleFilter}>Aplicar filtros</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Documento</TableHead>
                                    <TableHead>Contratista</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Monto</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {estadosPago.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center">
                                            No hay registros
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    estadosPago.data.map((ep) => (
                                        <TableRow key={ep.id}>
                                            <TableCell>{ep.numero_documento}</TableCell>
                                            <TableCell>{ep.contratista?.razon_social ?? '—'}</TableCell>
                                            <TableCell className="capitalize">{ep.estado.replace('_', ' ')}</TableCell>
                                            <TableCell>{ep.fecha_documento}</TableCell>
                                            <TableCell>${ep.monto}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/estados-pago/${ep.id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            Ver / Actualizar
                                                        </Button>
                                                    </Link>
                                                    {isAdmin && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-destructive"
                                                            onClick={() => handleDelete(ep.id, ep.numero_documento)}
                                                        >
                                                            Eliminar
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
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
