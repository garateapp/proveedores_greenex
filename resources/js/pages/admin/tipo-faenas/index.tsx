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
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';

interface TipoFaena {
    id: number;
    nombre: string;
    codigo: string;
    descripcion: string | null;
    activo: boolean;
    faenas_count: number;
    tipos_documento_count: number;
    updated_at: string | null;
}

interface Props {
    tipos: {
        data: TipoFaena[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        activo?: boolean | null;
    };
}

export default function TipoFaenasIndex({ tipos, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [activoFilter, setActivoFilter] = useState<string>(
        filters.activo === false ? 'inactivos' : 'todos',
    );

    const handleSearch = () => {
        router.get(
            '/tipo-faenas',
            {
                search,
                activo: activoFilter === 'todos' ? undefined : activoFilter === 'activos',
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleDelete = (id: number, nombre: string) => {
        if (confirm(`¿Eliminar el tipo de faena "${nombre}"?`)) {
            router.delete(`/tipo-faenas/${id}`, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Tipos de Faena" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Tipos de Faena</h1>
                        <p className="text-muted-foreground">
                            Configure los tipos de faena para clasificar documentos y operaciones.
                        </p>
                    </div>
                    <Link href="/tipo-faenas/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Tipo
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>Busque por nombre o codigo</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,200px,140px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Busqueda</Label>
                                <Input
                                    id="search"
                                    placeholder="Nombre o codigo"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="estado">Estado</Label>
                                <Select value={activoFilter} onValueChange={setActivoFilter}>
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
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Codigo</TableHead>
                                    <TableHead>Faenas</TableHead>
                                    <TableHead>Tipos Documento</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Actualizado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tipos.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center">
                                            No se encontraron tipos de faena
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    tipos.data.map((tipo) => (
                                        <TableRow key={tipo.id}>
                                            <TableCell className="font-medium">{tipo.nombre}</TableCell>
                                            <TableCell>{tipo.codigo}</TableCell>
                                            <TableCell>{tipo.faenas_count}</TableCell>
                                            <TableCell>{tipo.tipos_documento_count}</TableCell>
                                            <TableCell>
                                                <Badge variant={tipo.activo ? 'default' : 'secondary'}>
                                                    {tipo.activo ? 'Activo' : 'Inactivo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{tipo.updated_at || '—'}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/tipo-faenas/${tipo.id}/edit`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(tipo.id, tipo.nombre)}
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

                {tipos.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {tipos.links.map((link, index) => (
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

TipoFaenasIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
