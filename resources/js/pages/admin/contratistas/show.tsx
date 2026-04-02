import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/layouts/app';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ArrowLeft, Pencil, Users } from 'lucide-react';

interface Contratista {
    id: number;
    rut: string;
    razon_social: string;
    nombre_fantasia: string | null;
    direccion: string | null;
    comuna: string | null;
    region: string | null;
    telefono: string | null;
    email: string | null;
    estado: string;
    observaciones: string | null;
    created_at: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
}

interface Stats {
    total_users: number;
    total_trabajadores: number;
    trabajadores_activos: number;
}

interface Props {
    contratista: Contratista;
    users: User[];
    stats: Stats;
}

function estadoVariant(estado: string) {
    switch (estado) {
        case 'activo':
            return 'default';
        case 'bloqueado':
            return 'destructive';
        default:
            return 'secondary';
    }
}

export default function ContratistaShow({ contratista, users, stats }: Props) {
    return (
        <>
            <Head title={`Contratista ${contratista.razon_social}`} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/admin/contratistas">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                {contratista.razon_social}
                            </h1>
                            <p className="text-muted-foreground">Detalle del contratista</p>
                        </div>
                    </div>
                    <Link href={`/admin/contratistas/${contratista.id}/edit`}>
                        <Button variant="outline">
                            <Pencil className="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Usuarios vinculados
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">{stats.total_users}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Trabajadores totales
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">{stats.total_trabajadores}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Trabajadores activos
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">{stats.trabajadores_activos}</div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Información general</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">RUT</p>
                            <p className="font-medium">{contratista.rut}</p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Nombre de fantasía</p>
                            <p className="font-medium">
                                {contratista.nombre_fantasia || '—'}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Dirección</p>
                            <p className="font-medium">{contratista.direccion || '—'}</p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Comuna y región</p>
                            <p className="font-medium">
                                {[contratista.comuna, contratista.region].filter(Boolean).join(', ') ||
                                    '—'}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Contacto</p>
                            <p className="font-medium">{contratista.email || '—'}</p>
                            <p className="text-sm text-muted-foreground">
                                {contratista.telefono || '—'}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Estado</p>
                            <Badge variant={estadoVariant(contratista.estado)}>
                                {contratista.estado}
                            </Badge>
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Observaciones</p>
                            <p className="font-medium leading-relaxed">
                                {contratista.observaciones || '—'}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Creado</p>
                            <p className="font-medium">{contratista.created_at}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-lg font-semibold flex items-center gap-2">
                            <Users className="h-4 w-4" />
                            Usuarios asociados
                        </CardTitle>
                        <p className="text-sm text-muted-foreground">
                            {users.length} usuario(s)
                        </p>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Rol</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={3} className="text-center">
                                            Sin usuarios asociados
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    users.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell className="font-medium">{user.name}</TableCell>
                                            <TableCell>{user.email}</TableCell>
                                            <TableCell>{user.role_label}</TableCell>
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

ContratistaShow.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
