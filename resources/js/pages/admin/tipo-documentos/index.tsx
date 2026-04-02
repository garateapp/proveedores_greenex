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

interface TipoDocumento {
    id: number;
    nombre: string;
    codigo: string;
    periodicidad: string;
    permite_multiples_en_mes: boolean;
    es_obligatorio: boolean;
    es_documento_trabajador: boolean;
    tipos_faena: Array<{
        id: number;
        nombre: string;
    }>;
    activo: boolean;
    updated_at: string | null;
}

interface PeriodicidadOption {
    value: string;
    label: string;
}

interface Props {
    tipos: {
        data: TipoDocumento[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        activo?: boolean | null;
    };
    periodicidades: PeriodicidadOption[];
}

function estadoVariant(activo: boolean) {
    return activo ? 'default' : 'secondary';
}

export default function TipoDocumentosIndex({ tipos, filters, periodicidades }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [activoFilter, setActivoFilter] = useState<string>(filters.activo === false ? 'inactivos' : 'todos');

    const handleSearch = () => {
        router.get(
            '/tipo-documentos',
            { search, activo: activoFilter === 'todos' ? undefined : activoFilter === 'activos' },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleDelete = (id: number, nombre: string) => {
        if (confirm(`¿Eliminar el tipo "${nombre}"?`)) {
            router.delete(`/tipo-documentos/${id}`, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Tipos de Documento" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Tipos de Documento</h1>
                        <p className="text-muted-foreground">
                            Mantenga los tipos de documentos para validaciones y cargas.
                        </p>
                    </div>
                    <Link href="/tipo-documentos/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Tipo
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>Busque por nombre o código</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,200px,140px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Búsqueda</Label>
                                <Input
                                    id="search"
                                    placeholder="Nombre o código"
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
                                    <TableHead>Código</TableHead>
                                    <TableHead>Periodicidad</TableHead>
                                    <TableHead>Múltiples/mes</TableHead>
                                    <TableHead>Tipos de Faena</TableHead>
                                    <TableHead>Obligatorio</TableHead>
                                    <TableHead>Trabajador</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Actualizado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tipos.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={10} className="text-center">
                                            No se encontraron tipos de documentos
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    tipos.data.map((tipo) => (
                                        <TableRow key={tipo.id}>
                                            <TableCell className="font-medium">{tipo.nombre}</TableCell>
                                            <TableCell>{tipo.codigo}</TableCell>
                                            <TableCell>
                                                {periodicidades.find((p) => p.value === tipo.periodicidad)?.label ??
                                                    tipo.periodicidad}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        tipo.permite_multiples_en_mes
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {tipo.permite_multiples_en_mes ? 'Sí' : 'No'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {tipo.tipos_faena.length === 0
                                                    ? '—'
                                                    : tipo.tipos_faena.map((tipoFaena) => tipoFaena.nombre).join(', ')}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={tipo.es_obligatorio ? 'default' : 'secondary'}>
                                                    {tipo.es_obligatorio ? 'Sí' : 'Opcional'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        tipo.es_documento_trabajador ? 'default' : 'secondary'
                                                    }
                                                >
                                                    {tipo.es_documento_trabajador ? 'Si' : 'No'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={estadoVariant(tipo.activo)}>
                                                    {tipo.activo ? 'Activo' : 'Inactivo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{tipo.updated_at || '—'}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/tipo-documentos/${tipo.id}/edit`}>
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

TipoDocumentosIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
