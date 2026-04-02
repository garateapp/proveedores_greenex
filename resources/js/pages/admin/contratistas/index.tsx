import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { AppLayout } from '@/layouts/app';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Eye, Pencil, Plus, Search, Trash2 } from 'lucide-react';

interface Contratista {
    id: number;
    rut: string;
    razon_social: string;
    nombre_fantasia: string | null;
    email: string | null;
    telefono: string | null;
    estado: string;
    users_count: number;
    created_at: string;
}

interface EstadoOption {
    value: string;
    label: string;
}

interface Props {
    contratistas: {
        data: Contratista[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        estado?: string;
    };
    estados: EstadoOption[];
}

function getEstadoVariant(estado: string) {
    switch (estado) {
        case 'activo':
            return 'default';
        case 'bloqueado':
            return 'destructive';
        default:
            return 'secondary';
    }
}

export default function ContratistasIndex({ contratistas, filters, estados }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [estadoFilter, setEstadoFilter] = useState(filters.estado || 'all');

    const handleSearch = () => {
        router.get(
            '/admin/contratistas',
            { search, estado: estadoFilter === 'all' ? undefined : estadoFilter },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleDelete = (contratistaId: number, razonSocial: string) => {
        if (confirm(`¿Eliminar el contratista "${razonSocial}"?`)) {
            router.delete(`/admin/contratistas/${contratistaId}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Gestión de Contratistas" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Contratistas</h1>
                        <p className="text-muted-foreground">
                            Administre el registro y estado de los contratistas del sistema
                        </p>
                    </div>
                    <Link href="/admin/contratistas/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Contratista
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Busque por RUT, razón social o nombre de fantasía
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,220px,140px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Búsqueda</Label>
                                <Input
                                    id="search"
                                    placeholder="Ej: 12345678-9, Agro Ltda, etc."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="estado">Estado</Label>
                                <Select value={estadoFilter} onValueChange={setEstadoFilter}>
                                    <SelectTrigger id="estado">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        {estados.map((estado) => (
                                            <SelectItem key={estado.value} value={estado.value}>
                                                {estado.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button className="w-full" onClick={handleSearch}>
                                    <Search className="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>RUT</TableHead>
                                    <TableHead>Razón Social</TableHead>
                                    <TableHead>Contacto</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Usuarios</TableHead>
                                    <TableHead>Creado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {contratistas.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center">
                                            No se encontraron contratistas
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    contratistas.data.map((contratista) => (
                                        <TableRow key={contratista.id}>
                                            <TableCell className="font-medium">
                                                {contratista.rut}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col">
                                                    <span className="font-medium">
                                                        {contratista.razon_social}
                                                    </span>
                                                    {contratista.nombre_fantasia && (
                                                        <span className="text-sm text-muted-foreground">
                                                            {contratista.nombre_fantasia}
                                                        </span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col text-sm">
                                                    <span>{contratista.email || '-'}</span>
                                                    <span className="text-muted-foreground">
                                                        {contratista.telefono || '-'}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getEstadoVariant(contratista.estado)}>
                                                    {contratista.estado}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{contratista.users_count}</TableCell>
                                            <TableCell>{contratista.created_at}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/admin/contratistas/${contratista.id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Link
                                                        href={`/admin/contratistas/${contratista.id}/edit`}
                                                    >
                                                        <Button variant="ghost" size="sm">
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleDelete(
                                                                contratista.id,
                                                                contratista.razon_social,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {contratistas.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {contratistas.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ContratistasIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
