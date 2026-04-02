import { type ReactNode, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { AppLayout } from '@/layouts/app';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
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
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';

interface Plantilla {
    id: number;
    nombre: string;
    tipo_documento_nombre: string | null;
    tipo_documento_codigo: string | null;
    activo: boolean;
    documentos_firmados_count: number;
    updated_at: string | null;
}

interface Props {
    plantillas: {
        data: Plantilla[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        activo?: boolean | null;
    };
}

export default function PlantillasDocumentosTrabajadorIndex({ plantillas, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [activeFilter, setActiveFilter] = useState<string>(filters.activo === false ? 'inactivos' : 'todos');

    const applyFilters = () => {
        router.get(
            '/admin/plantillas-documentos-trabajador',
            {
                search,
                activo: activeFilter === 'todos' ? undefined : activeFilter === 'activos',
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleDelete = (id: number, nombre: string) => {
        if (!confirm(`¿Eliminar plantilla "${nombre}"?`)) {
            return;
        }

        router.delete(`/admin/plantillas-documentos-trabajador/${id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Plantillas de Firma" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Plantillas de Firma</h1>
                        <p className="text-muted-foreground">
                            Defina documentos formales para firma digital de trabajadores.
                        </p>
                    </div>
                    <Link href="/admin/plantillas-documentos-trabajador/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Plantilla
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>Busque por plantilla o tipo de documento</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,200px,140px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Búsqueda</Label>
                                <Input
                                    id="search"
                                    placeholder="Nombre o tipo documento"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    onKeyDown={(event) => event.key === 'Enter' && applyFilters()}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="estado">Estado</Label>
                                <Select value={activeFilter} onValueChange={setActiveFilter}>
                                    <SelectTrigger id="estado">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos</SelectItem>
                                        <SelectItem value="activos">Activos</SelectItem>
                                        <SelectItem value="inactivos">Inactivos</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button className="w-full" onClick={applyFilters}>
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
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Tipo documento</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Firmas generadas</TableHead>
                                    <TableHead>Actualizado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {plantillas.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center">
                                            No se encontraron plantillas.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    plantillas.data.map((plantilla) => (
                                        <TableRow key={plantilla.id}>
                                            <TableCell className="font-medium">{plantilla.nombre}</TableCell>
                                            <TableCell>
                                                {plantilla.tipo_documento_nombre ? (
                                                    <div className="space-y-0.5">
                                                        <p>{plantilla.tipo_documento_nombre}</p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {plantilla.tipo_documento_codigo}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={plantilla.activo ? 'default' : 'secondary'}>
                                                    {plantilla.activo ? 'Activa' : 'Inactiva'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{plantilla.documentos_firmados_count}</TableCell>
                                            <TableCell>{plantilla.updated_at || '—'}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/admin/plantillas-documentos-trabajador/${plantilla.id}/edit`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(plantilla.id, plantilla.nombre)}
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

                {plantillas.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {plantillas.links.map((link, index) => (
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

PlantillasDocumentosTrabajadorIndex.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
