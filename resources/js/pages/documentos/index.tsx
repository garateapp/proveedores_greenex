import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle,
    Clock,
    Download,
    Eye,
    FileText,
    Upload,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Documentos',
        href: '/documentos',
    },
];

interface TipoDocumento {
    id: number;
    nombre: string;
    codigo: string;
}

interface Contratista {
    id: number;
    razon_social: string;
    nombre_fantasia: string | null;
}

interface Documento {
    id: number;
    tipo_documento_id: number;
    contratista_id: number;
    periodo_ano: number;
    periodo_mes: number | null;
    nombre_archivo?: string;
    ruta_archivo?: string;
    archivo_nombre_original?: string;
    archivo_ruta?: string;
    estado: 'pendiente_validacion' | 'aprobado' | 'rechazado' | 'vencido';
    fecha_vencimiento: string | null;
    observaciones: string | null;
    tipo_documento: TipoDocumento;
    contratista: Contratista;
    created_at: string;
    updated_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: Documento[];
}

interface Filters {
    tipo_documento_id?: string;
    estado?: string;
    ano?: string;
    contratista_id?: string;
}

interface Props {
    documentos: Pagination;
    tiposDocumentos: TipoDocumento[];
    contratistas: { value: string; label: string }[];
    filters: Filters;
}

const estadoBadgeConfig = (estado: Documento['estado']) => {
    switch (estado) {
        case 'aprobado':
            return {
                variant: 'default' as const,
                icon: CheckCircle,
                label: 'Aprobado',
            };
        case 'pendiente_validacion':
            return {
                variant: 'secondary' as const,
                icon: Clock,
                label: 'Pendiente de aprobación',
            };
        case 'rechazado':
            return {
                variant: 'destructive' as const,
                icon: XCircle,
                label: 'Rechazado',
            };
        case 'vencido':
            return {
                variant: 'outline' as const,
                icon: AlertCircle,
                label: 'Vencido',
            };
        default:
            return {
                variant: 'secondary' as const,
                icon: FileText,
                label: estado,
            };
    }
};

const meses = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

export default function DocumentosIndex({
    documentos,
    tiposDocumentos,
    contratistas,
    filters,
}: Props) {
    const page = usePage<SharedData>();
    const isAdmin = page.props.auth?.user?.isAdmin ?? false;
    const [previewDocumento, setPreviewDocumento] = useState<Documento | null>(
        null,
    );

    const handleFilterChange = (key: string, value: string) => {
        const payload = { ...filters, [key]: value };
        router.get('/documentos', payload, { preserveState: true });
    };

    const buildPageHref = (page: number): string => {
        const params = new URLSearchParams();

        if (filters.tipo_documento_id) {
            params.set('tipo_documento_id', filters.tipo_documento_id);
        }

        if (filters.estado) {
            params.set('estado', filters.estado);
        }

        if (filters.ano) {
            params.set('ano', filters.ano);
        }

        if (filters.contratista_id) {
            params.set('contratista_id', filters.contratista_id);
        }

        params.set('page', page.toString());

        return `/documentos?${params.toString()}`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Documentos" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Gestión de Documentos
                        </h1>
                        <p className="text-muted-foreground">
                            Administre la documentación legal y de cumplimiento
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {isAdmin && (
                            <Button variant="outline" asChild>
                                <Link href="/documentos/aprobaciones">
                                    <CheckCircle className="mr-2 size-4" />
                                    Aprobaciones
                                </Link>
                            </Button>
                        )}
                        <Button asChild>
                            <Link href="/documentos/create">
                                <Upload className="mr-2 size-4" />
                                Cargar Documento
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Filtre documentos por tipo, estado y período
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            {/* Tipo Documento */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Tipo de Documento
                                </label>
                                <Select
                                    value={filters.tipo_documento_id || 'all'}
                                    onValueChange={(value) =>
                                        handleFilterChange(
                                            'tipo_documento_id',
                                            value === 'all' ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los tipos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los tipos
                                        </SelectItem>
                                        {tiposDocumentos.map((tipo) => (
                                            <SelectItem
                                                key={tipo.id}
                                                value={tipo.id.toString()}
                                            >
                                                {tipo.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Contratista */}
                            {contratistas.length > 0 && (
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Contratista
                                    </label>
                                    <Select
                                        value={filters.contratista_id || 'all'}
                                        onValueChange={(value) =>
                                            handleFilterChange(
                                                'contratista_id',
                                                value === 'all' ? '' : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los contratistas" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                Todos los contratistas
                                            </SelectItem>
                                            {contratistas.map((contratista) => (
                                                <SelectItem
                                                    key={contratista.value}
                                                    value={contratista.value}
                                                >
                                                    {contratista.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            {/* Estado */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Estado
                                </label>
                                <Select
                                    value={filters.estado || 'all'}
                                    onValueChange={(value) =>
                                        handleFilterChange(
                                            'estado',
                                            value === 'all' ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los estados
                                        </SelectItem>
                                        <SelectItem value="pendiente_validacion">
                                            Pendiente de aprobación
                                        </SelectItem>
                                        <SelectItem value="aprobado">
                                            Aprobado
                                        </SelectItem>
                                        <SelectItem value="rechazado">
                                            Rechazado
                                        </SelectItem>
                                        <SelectItem value="vencido">
                                            Vencido
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Año */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Año
                                </label>
                                <Select
                                    value={filters.ano || 'all'}
                                    onValueChange={(value) =>
                                        handleFilterChange(
                                            'ano',
                                            value === 'all' ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los años" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los años
                                        </SelectItem>
                                        {Array.from(
                                            { length: 5 },
                                            (_, i) =>
                                                new Date().getFullYear() - i,
                                        ).map((year) => (
                                            <SelectItem
                                                key={year}
                                                value={year.toString()}
                                            >
                                                {year}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Documentos ({documentos.total} documento
                            {documentos.total !== 1 ? 's' : ''})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Período</TableHead>
                                    <TableHead>Contratista</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha Carga</TableHead>
                                    <TableHead>Vencimiento</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {documentos.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="text-center text-muted-foreground"
                                        >
                                            No se encontraron documentos
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    documentos.data.map((documento) => {
                                        const estadoConfig = estadoBadgeConfig(
                                            documento.estado,
                                        );
                                        const EstadoIcon = estadoConfig.icon;

                                        return (
                                            <TableRow key={documento.id}>
                                                <TableCell className="font-medium">
                                                    <div className="flex items-center gap-2">
                                                        <FileText className="size-4 text-muted-foreground" />
                                                        {
                                                            documento
                                                                .tipo_documento
                                                                .nombre
                                                        }
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {documento.periodo_mes
                                                        ? `${meses[documento.periodo_mes - 1]} ${documento.periodo_ano}`
                                                        : documento.periodo_ano}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {documento.contratista
                                                        .nombre_fantasia ||
                                                        documento.contratista
                                                            .razon_social}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={
                                                            estadoConfig.variant
                                                        }
                                                    >
                                                        <EstadoIcon className="mr-1 size-3" />
                                                        {estadoConfig.label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {new Date(
                                                        documento.created_at,
                                                    ).toLocaleDateString(
                                                        'es-CL',
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {documento.fecha_vencimiento
                                                        ? new Date(
                                                              documento.fecha_vencimiento,
                                                          ).toLocaleDateString(
                                                              'es-CL',
                                                          )
                                                        : '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            type="button"
                                                            onClick={() =>
                                                                setPreviewDocumento(
                                                                    documento,
                                                                )
                                                            }
                                                        >
                                                            <Eye className="size-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/documentos/${documento.id}/download`}
                                                            >
                                                                <Download className="size-4" />
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {documentos.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Página {documentos.current_page} de{' '}
                                    {documentos.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={documentos.current_page === 1}
                                        asChild
                                    >
                                        <Link
                                            href={buildPageHref(
                                                documentos.current_page - 1,
                                            )}
                                            preserveState
                                        >
                                            Anterior
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={
                                            documentos.current_page ===
                                            documentos.last_page
                                        }
                                        asChild
                                    >
                                        <Link
                                            href={buildPageHref(
                                                documentos.current_page + 1,
                                            )}
                                            preserveState
                                        >
                                            Siguiente
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={previewDocumento !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPreviewDocumento(null);
                    }
                }}
            >
                <DialogContent className="max-w-6xl">
                    <DialogHeader>
                        <DialogTitle>Visor de documento</DialogTitle>
                        <DialogDescription>
                            {previewDocumento?.archivo_nombre_original ||
                                previewDocumento?.nombre_archivo ||
                                'Documento'}
                        </DialogDescription>
                    </DialogHeader>

                    {previewDocumento && (
                        <div className="space-y-3">
                            <div className="flex justify-end">
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/documentos/${previewDocumento.id}/download`}
                                    >
                                        <Download className="mr-2 size-4" />
                                        Descargar
                                    </Link>
                                </Button>
                            </div>

                            <div className="h-[70vh] overflow-hidden rounded-lg border border-border/70 bg-muted/15">
                                <iframe
                                    src={`/documentos/${previewDocumento.id}/preview`}
                                    title={`Vista previa documento ${previewDocumento.id}`}
                                    className="h-full w-full"
                                />
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Si el navegador no soporta este formato, usa el
                                botón Descargar.
                            </p>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
