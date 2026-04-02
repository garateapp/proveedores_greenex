import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
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
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Search, Upload, UploadCloud } from 'lucide-react';
import type React from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Personal',
        href: '/trabajadores',
    },
];

interface Contratista {
    id: number;
    razon_social: string;
    nombre_fantasia: string | null;
}

interface Trabajador {
    id: string;
    documento: string;
    nombre: string;
    apellido: string;
    email: string | null;
    telefono: string | null;
    estado: 'activo' | 'inactivo';
    documentos_obligatorios_completos: boolean;
    documentos_obligatorios_total: number;
    documentos_obligatorios_cargados: number;
    documentos_obligatorios_pendientes: number;
    documentos_obligatorios_porcentaje: number;
    fecha_ingreso: string;
    contratista: Contratista;
    created_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: Trabajador[];
}

interface Filters {
    search?: string;
    estado?: string;
}

interface Props {
    trabajadores: Pagination;
    filters: Filters;
    contratistas?: { value: number; label: string }[];
}

export default function TrabajadoresIndex({ trabajadores, filters, contratistas = [] }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const page = usePage<SharedData & { contratistas?: { value: number; label: string }[] }>();
    const isAdmin = page.props.auth?.user?.isAdmin ?? false;

    const { data, setData, post, processing, errors, progress } = useForm({
        file: null as File | null,
        contratista_id: '',
    });

    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const [isDragging, setIsDragging] = useState(false);

    const defaultContratista = useMemo(() => {
        const list = contratistas.length ? contratistas : (page.props.contratistas ?? []);
        return list[0]?.value?.toString() ?? '';
    }, [contratistas, page.props.contratistas]);

    useEffect(() => {
        if (isAdmin && defaultContratista) {
            setData('contratista_id', defaultContratista);
        }
    }, [isAdmin, defaultContratista, setData]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/trabajadores', { search }, { preserveState: true });
    };

    const handleFilterEstado = (estado: string) => {
        router.get('/trabajadores', { ...filters, estado }, { preserveState: true });
    };

    const handleFileChange = (file: File | null) => {
        setData('file', file);
    };

    const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileChange(e.dataTransfer.files[0]);
        }
    };

    const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDelete = (id: string, nombreCompleto: string) => {
        if (!confirm(`¿Eliminar al trabajador ${nombreCompleto}?`)) {
            return;
        }

        router.delete(`/trabajadores/${id}`, {
            preserveScroll: true,
        });
    };

    const toggleEstado = (id: string, estadoActual: 'activo' | 'inactivo') => {
        router.patch(
            `/trabajadores/${id}/estado`,
            { estado: estadoActual === 'activo' ? 'inactivo' : 'activo' },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['trabajadores'],
            },
        );
    };

    const submitImport: React.FormEventHandler = (e) => {
        e.preventDefault();
        post('/trabajadores/import', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Personal" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Gestión de Personal</h1>
                        <p className="text-muted-foreground">
                            Administre los trabajadores de su organización
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/centro-carga">
                            <Button variant="secondary">
                                <UploadCloud className="mr-2 size-4" />
                                Centro de Carga
                            </Button>
                        </Link>
                        <Button asChild variant="outline">
                            <a href="/trabajadores/template/download">
                                <Upload className="mr-2 size-4" />
                                Plantilla Excel
                            </a>
                        </Button>
                        <Link href="/trabajadores/create">
                            <Button>
                                <Plus className="mr-2 size-4" />
                                Nuevo Trabajador
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Busque y filtre trabajadores por diferentes criterios
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Search */}
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Buscar por nombre, apellido o RUT..."
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
                                Todos
                            </Button>
                            <Button
                                variant={filters.estado === 'activo' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => handleFilterEstado('activo')}
                            >
                                Activos
                            </Button>
                            <Button
                                variant={filters.estado === 'inactivo' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => handleFilterEstado('inactivo')}
                            >
                                Inactivos
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Importar CSV */}
                <Card>
                    <CardHeader>
                        <CardTitle>Importación masiva</CardTitle>
                        <CardDescription>Cargue la plantilla CSV para crear trabajadores en lote.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <form onSubmit={submitImport} className="space-y-4">
                            {isAdmin && (
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="contratista_id">
                                        Contratista
                                    </label>
                                    <select
                                        id="contratista_id"
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        value={data.contratista_id}
                                        onChange={(e) => setData('contratista_id', e.target.value)}
                                        required
                                    >
                                        <option value="">Seleccione un contratista</option>
                                        {(page.props.contratistas ?? []).map((contratistaOption) => (
                                            <option
                                                key={contratistaOption.value}
                                                value={contratistaOption.value}
                                            >
                                                {contratistaOption.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.contratista_id && (
                                        <p className="text-sm text-destructive">{errors.contratista_id as string}</p>
                                    )}
                                </div>
                            )}

                            <div
                                className={`flex flex-col items-center justify-center rounded-md border border-dashed p-6 text-sm transition ${
                                    isDragging ? 'border-primary bg-primary/5' : 'border-border hover:border-primary'
                                }`}
                                onDrop={handleDrop}
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                            >
                                <p className="text-center text-muted-foreground">
                                    Arrastre y suelte el archivo CSV aquí
                                </p>
                                <p className="text-center text-muted-foreground">o</p>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => fileInputRef.current?.click()}
                                    className="mt-2"
                                >
                                    Seleccionar archivo
                                </Button>
                                {data.file && (
                                    <p className="mt-3 text-center text-foreground">{data.file.name}</p>
                                )}
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".csv"
                                    className="hidden"
                                    onChange={(e) => handleFileChange(e.target.files?.[0] || null)}
                                    required
                                />
                            </div>
                            {progress && (
                                <p className="text-sm text-muted-foreground">Subiendo: {progress.percentage}%</p>
                            )}
                            {errors.file && <p className="text-sm text-destructive">{errors.file as string}</p>}

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing || !data.file}>
                                    Importar CSV
                                </Button>
                                <p className="text-sm text-muted-foreground">
                                    Solo formato CSV (use la plantilla de arriba).
                                </p>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Personal ({trabajadores.total} trabajador
                            {trabajadores.total !== 1 ? 'es' : ''})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>RUT</TableHead>
                                    <TableHead>Nombre Completo</TableHead>
                                    <TableHead>Contratista</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Docs obligatorios</TableHead>
                                    <TableHead>Fecha Ingreso</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {trabajadores.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center text-muted-foreground">
                                            No se encontraron trabajadores
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    trabajadores.data.map((trabajador) => (
                                        <TableRow key={trabajador.id}>
                                            <TableCell className="font-mono">{trabajador.documento}</TableCell>
                                            <TableCell className="font-medium">
                                                {trabajador.nombre} {trabajador.apellido}
                                            </TableCell>
                                            <TableCell>
                                                {trabajador.contratista.nombre_fantasia || trabajador.contratista.razon_social}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Badge
                                                        variant={trabajador.estado === 'activo' ? 'default' : 'secondary'}
                                                    >
                                                        {trabajador.estado}
                                                    </Badge>
                                                    <Switch
                                                        checked={trabajador.estado === 'activo'}
                                                        onCheckedChange={() => toggleEstado(trabajador.id, trabajador.estado)}
                                                        aria-label="Cambiar estado"
                                                    />
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="min-w-[180px] space-y-1.5">
                                                    <div className="flex items-center justify-between text-xs">
                                                        <span className="text-muted-foreground">
                                                            {trabajador.documentos_obligatorios_total === 0
                                                                ? 'Sin requeridos'
                                                                : `${trabajador.documentos_obligatorios_cargados}/${trabajador.documentos_obligatorios_total} cargados`}
                                                        </span>
                                                        <span className="font-semibold text-foreground">
                                                            {trabajador.documentos_obligatorios_porcentaje}%
                                                        </span>
                                                    </div>
                                                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted/80">
                                                        <div
                                                            className={
                                                                trabajador.documentos_obligatorios_completos
                                                                    ? 'h-full rounded-full bg-gradient-to-r from-[var(--brand-green)] to-[var(--brand-lime)] transition-all duration-300'
                                                                    : 'h-full rounded-full bg-gradient-to-r from-[var(--brand-orange)] to-[var(--brand-lime)] transition-all duration-300'
                                                            }
                                                            style={{
                                                                width: `${trabajador.documentos_obligatorios_porcentaje}%`,
                                                            }}
                                                        />
                                                    </div>
                                                    <p className="text-[11px] text-muted-foreground">
                                                        {trabajador.documentos_obligatorios_total === 0
                                                            ? 'No aplica'
                                                            : trabajador.documentos_obligatorios_pendientes === 0
                                                              ? 'Completado'
                                                              : `Faltan ${trabajador.documentos_obligatorios_pendientes}`}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {new Date(trabajador.fecha_ingreso).toLocaleDateString('es-CL')}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/trabajadores/${trabajador.id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            Ver
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/trabajadores/${trabajador.id}/edit`}>
                                                        <Button variant="ghost" size="sm">
                                                            Editar
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/centro-carga?trabajador_id=${trabajador.id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            Carga
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive"
                                                        onClick={() =>
                                                            handleDelete(
                                                                trabajador.id,
                                                                `${trabajador.nombre} ${trabajador.apellido}`,
                                                            )
                                                        }
                                                    >
                                                        Eliminar
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {trabajadores.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Página {trabajadores.current_page} de {trabajadores.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Link
                                        href={`/trabajadores?page=${trabajadores.current_page - 1}`}
                                        preserveState
                                    >
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={trabajadores.current_page === 1}
                                        >
                                            Anterior
                                        </Button>
                                    </Link>
                                    <Link
                                        href={`/trabajadores?page=${trabajadores.current_page + 1}`}
                                        preserveState
                                    >
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={trabajadores.current_page === trabajadores.last_page}
                                        >
                                            Siguiente
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
