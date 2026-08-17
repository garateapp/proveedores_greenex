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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    BadgeCheck,
    Building2,
    CheckCircle,
    Clock,
    Download,
    Eye,
    FileText,
    Info,
    User,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mi Empresa', href: '/dashboard' },
    { title: 'Mis Documentos', href: '/contratistas/documentos' },
];

interface TipoDocumento {
    id: number;
    nombre: string;
    codigo: string;
}

interface TrabajadorInfo {
    id: string;
    nombre_completo: string;
    documento: string;
}

interface DocumentoItem {
    id: number;
    tipo_documento: TipoDocumento;
    es_documento_trabajador: boolean;
    trabajador: TrabajadorInfo | null;
    periodo_ano: number | null;
    periodo_mes: number | null;
    estado: 'pendiente_validacion' | 'aprobado' | 'rechazado';
    motivo_rechazo: string | null;
    fecha_vencimiento: string | null;
    archivo_nombre_original: string;
    created_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: DocumentoItem[];
}

interface Filters {
    tipo_documento_id?: string;
    estado?: string;
    ano?: string;
}

interface Props {
    documentos: Pagination;
    tiposDocumentos: TipoDocumento[];
    filters: Filters;
}

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

function estadoBadgeConfig(estado: DocumentoItem['estado']) {
    switch (estado) {
        case 'aprobado':
            return {
                className:
                    'bg-[var(--brand-green)] text-[var(--primary-foreground)] hover:bg-[var(--brand-green)]',
                icon: CheckCircle,
                label: 'Aprobado',
            };
        case 'pendiente_validacion':
            return {
                className:
                    'bg-amber-100 text-amber-800 hover:bg-amber-100',
                icon: Clock,
                label: 'Pendiente',
            };
        case 'rechazado':
            return {
                className: 'bg-red-100 text-red-700 hover:bg-red-100',
                icon: XCircle,
                label: 'Rechazado',
            };
        default:
            return {
                className: 'bg-muted text-muted-foreground',
                icon: FileText,
                label: estado,
            };
    }
}

export default function ContratistaDocumentos({
    documentos,
    tiposDocumentos,
    filters,
}: Props) {
    const [previewDocumento, setPreviewDocumento] =
        useState<DocumentoItem | null>(null);

    const handleFilterChange = (key: string, value: string) => {
        const payload = { ...filters, [key]: value };
        router.get('/contratistas/documentos', payload, {
            preserveState: true,
        });
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

        params.set('page', page.toString());

        return `/contratistas/documentos?${params.toString()}`;
    };

    const getPreviewUrl = (doc: DocumentoItem): string => {
        if (doc.es_documento_trabajador) {
            return `/documentos-trabajadores/${doc.id}/preview`;
        }

        return `/documentos/${doc.id}/preview`;
    };

    const getDownloadUrl = (doc: DocumentoItem): string => {
        if (doc.es_documento_trabajador) {
            return `/documentos-trabajadores/${doc.id}/download`;
        }

        return `/documentos/${doc.id}/download`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mis Documentos" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Mis Documentos
                    </h1>
                    <p className="text-muted-foreground">
                        Documentos de la empresa y de su personal
                    </p>
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card className="border-[var(--brand-green)]/25">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-lg bg-[var(--brand-green)]/10">
                                    <Building2 className="size-5 text-[var(--brand-green)]" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {
                                            documentos.data.filter(
                                                (d) =>
                                                    !d.es_documento_trabajador,
                                            ).length
                                        }
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Documentos empresa (en esta pagina)
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-amber-200/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-lg bg-amber-100">
                                    <User className="size-5 text-amber-700" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {
                                            documentos.data.filter(
                                                (d) =>
                                                    d.es_documento_trabajador,
                                            ).length
                                        }
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Documentos personal (en esta pagina)
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-red-200/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-lg bg-red-100">
                                    <AlertCircle className="size-5 text-red-600" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {
                                            documentos.data.filter(
                                                (d) =>
                                                    d.estado === 'rechazado',
                                            ).length
                                        }
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Rechazados (en esta pagina)
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Filtre por tipo de documento, estado o periodo
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-3">
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
                                            Pendiente de aprobacion
                                        </SelectItem>
                                        <SelectItem value="aprobado">
                                            Aprobado
                                        </SelectItem>
                                        <SelectItem value="rechazado">
                                            Rechazado
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Ano
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
                                        <SelectValue placeholder="Todos los anos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los anos
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
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>Origen</TableHead>
                                        <TableHead>Periodo</TableHead>
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
                                            const estadoCfg = estadoBadgeConfig(
                                                documento.estado,
                                            );
                                            const EstadoIcon =
                                                estadoCfg.icon;

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
                                                        {documento.es_documento_trabajador ? (
                                                            <div className="flex items-center gap-1.5">
                                                                <Badge
                                                                    variant="outline"
                                                                    className="border-purple-200 text-purple-700"
                                                                >
                                                                    <User className="mr-1 size-3" />
                                                                    Personal
                                                                </Badge>
                                                                {documento.trabajador && (
                                                                    <span className="max-w-40 truncate text-xs text-muted-foreground">
                                                                        {
                                                                            documento
                                                                                .trabajador
                                                                                .nombre_completo
                                                                        }
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <Badge
                                                                variant="outline"
                                                                className="border-[var(--brand-green)]/30 text-[var(--brand-green)]"
                                                            >
                                                                <Building2 className="mr-1 size-3" />
                                                                Empresa
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {documento.periodo_mes
                                                            ? `${meses[documento.periodo_mes - 1]} ${documento.periodo_ano}`
                                                            : documento.periodo_ano
                                                              ? String(
                                                                    documento.periodo_ano,
                                                                )
                                                              : '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-1">
                                                            <Badge
                                                                className={
                                                                    estadoCfg.className
                                                                }
                                                            >
                                                                <EstadoIcon className="mr-1 size-3" />
                                                                {
                                                                    estadoCfg.label
                                                                }
                                                            </Badge>
                                                            {documento.estado ===
                                                                'rechazado' &&
                                                                documento.motivo_rechazo && (
                                                                    <Tooltip>
                                                                        <TooltipTrigger
                                                                            asChild
                                                                        >
                                                                            <button
                                                                                type="button"
                                                                                className="cursor-help"
                                                                            >
                                                                                <Info className="size-3.5 text-red-500" />
                                                                            </button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent
                                                                            side="right"
                                                                            className="max-w-xs text-xs"
                                                                        >
                                                                            <p className="mb-0.5 font-medium">
                                                                                Motivo
                                                                                del
                                                                                rechazo:
                                                                            </p>
                                                                            <p>
                                                                                {
                                                                                    documento.motivo_rechazo
                                                                                }
                                                                            </p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                        </div>
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
                                                                    href={getDownloadUrl(
                                                                        documento,
                                                                    )}
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
                        </div>

                        {/* Pagination */}
                        {documentos.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Pagina {documentos.current_page} de{' '}
                                    {documentos.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={
                                            documentos.current_page === 1
                                        }
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
                                'Documento'}
                        </DialogDescription>
                    </DialogHeader>

                    {previewDocumento && (
                        <div className="space-y-3">
                            <div className="flex items-center gap-3">
                                {previewDocumento.es_documento_trabajador && (
                                    <Badge
                                        variant="outline"
                                        className="border-purple-200 text-purple-700"
                                    >
                                        <User className="mr-1 size-3" />
                                        {previewDocumento.trabajador
                                            ? `${previewDocumento.trabajador.nombre_completo} (${previewDocumento.trabajador.documento})`
                                            : 'Personal'}
                                    </Badge>
                                )}
                                <div className="flex-1" />
                                <Button variant="outline" asChild>
                                    <Link
                                        href={getDownloadUrl(previewDocumento)}
                                    >
                                        <Download className="mr-2 size-4" />
                                        Descargar
                                    </Link>
                                </Button>
                            </div>

                            <div className="h-[70vh] overflow-hidden rounded-lg border border-border/70 bg-muted/15">
                                <iframe
                                    src={getPreviewUrl(previewDocumento)}
                                    title={`Vista previa documento ${previewDocumento.id}`}
                                    className="h-full w-full"
                                />
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Si el navegador no soporta este formato, usa el
                                boton Descargar.
                            </p>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
