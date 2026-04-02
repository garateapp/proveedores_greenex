import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MapPin, Plus, Search, Users } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Faenas',
        href: '/faenas',
    },
];

interface Faena {
    id: number;
    codigo: string;
    nombre: string;
    tipo_faena?: {
        id: number;
        nombre: string;
    } | null;
    descripcion: string | null;
    ubicacion: string | null;
    estado: 'activa' | 'inactiva' | 'finalizada';
    fecha_inicio: string | null;
    fecha_termino: string | null;
    trabajadores_count: number;
    created_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: Faena[];
}

interface Filters {
    search?: string;
    estado?: string;
}

interface Props {
    faenas: Pagination;
    filters: Filters;
}

const estadoBadgeVariant = (estado: Faena['estado']) => {
    switch (estado) {
        case 'activa':
            return 'default';
        case 'inactiva':
            return 'secondary';
        case 'finalizada':
            return 'outline';
        default:
            return 'secondary';
    }
};

export default function FaenasIndex({ faenas, filters }: Props) {
    const page = usePage<SharedData>();
    const canCreateFaena = page.props.auth?.user?.isAdmin ?? false;
    const canEditFaena = page.props.auth?.user?.isAdmin ?? false;
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/faenas', { search }, { preserveState: true });
    };

    const handleFilterEstado = (estado: string) => {
        router.get('/faenas', { ...filters, estado }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Faenas" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Gestión de Faenas</h1>
                        <p className="text-muted-foreground">
                            Administre las faenas y cuadrillas de trabajo
                        </p>
                    </div>
                    {canCreateFaena && (
                        <Button asChild>
                            <Link href="/faenas/create">
                                <Plus className="mr-2 size-4" />
                                Nueva Faena
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Busque y filtre faenas por diferentes criterios
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Search */}
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Buscar por nombre o código..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Button type="submit">Buscar</Button>
                        </form>

                        {/* Estado filters */}
                        <div className="flex gap-2">
                            <Button
                                variant={!filters.estado ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => handleFilterEstado('')}
                            >
                                Todas
                            </Button>
                            <Button
                                variant={filters.estado === 'activa' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => handleFilterEstado('activa')}
                            >
                                Activas
                            </Button>
                            <Button
                                variant={filters.estado === 'inactiva' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => handleFilterEstado('inactiva')}
                            >
                                Inactivas
                            </Button>
                            <Button
                                variant={filters.estado === 'finalizada' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => handleFilterEstado('finalizada')}
                            >
                                Finalizadas
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Faenas ({faenas.total} faena{faenas.total !== 1 ? 's' : ''})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Tipo Faena</TableHead>
                                    <TableHead>Ubicación</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Personal</TableHead>
                                    <TableHead>Fecha Inicio</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {faenas.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center text-muted-foreground">
                                            No se encontraron faenas
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    faenas.data.map((faena) => (
                                        <TableRow key={faena.id}>
                                            <TableCell className="font-mono font-medium">
                                                {faena.codigo}
                                            </TableCell>
                                            <TableCell className="font-medium">{faena.nombre}</TableCell>
                                            <TableCell>{faena.tipo_faena?.nombre ?? '—'}</TableCell>
                                            <TableCell>
                                                {faena.ubicacion ? (
                                                    <div className="flex items-center gap-1">
                                                        <MapPin className="size-4 text-muted-foreground" />
                                                        <span className="text-sm">{faena.ubicacion}</span>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={estadoBadgeVariant(faena.estado)}>
                                                    {faena.estado}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1">
                                                    <Users className="size-4 text-muted-foreground" />
                                                    <span className="text-sm font-medium">
                                                        {faena.trabajadores_count}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {faena.fecha_inicio
                                                    ? new Date(faena.fecha_inicio).toLocaleDateString('es-CL')
                                                    : '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/faenas/${faena.id}`}>
                                                            Ver
                                                        </Link>
                                                    </Button>
                                                    {canEditFaena && (
                                                        <Button variant="ghost" size="sm" asChild>
                                                            <Link href={`/faenas/${faena.id}/edit`}>
                                                                Editar
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {faenas.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Página {faenas.current_page} de {faenas.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={faenas.current_page === 1}
                                        asChild
                                    >
                                        <Link href={`/faenas?page=${faenas.current_page - 1}`} preserveState>
                                            Anterior
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={faenas.current_page === faenas.last_page}
                                        asChild
                                    >
                                        <Link href={`/faenas?page=${faenas.current_page + 1}`} preserveState>
                                            Siguiente
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
