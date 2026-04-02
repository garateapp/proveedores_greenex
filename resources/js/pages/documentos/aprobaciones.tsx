import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    CheckCircle,
    Clock,
    Download,
    Eye,
    FileText,
    ShieldAlert,
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
    {
        title: 'Aprobaciones',
        href: '/documentos/aprobaciones',
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
    archivo_nombre_original?: string;
    estado: 'pendiente_validacion' | 'aprobado' | 'rechazado';
    fecha_vencimiento: string | null;
    tipo_documento: TipoDocumento;
    contratista: Contratista;
    created_at: string;
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
    contratista_id?: string;
    ano?: string;
}

interface Props {
    documentos: Pagination;
    tiposDocumentos: TipoDocumento[];
    contratistas: { value: string; label: string }[];
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

export default function DocumentosAprobaciones({
    documentos,
    tiposDocumentos,
    contratistas,
    filters,
}: Props) {
    const [previewDocumento, setPreviewDocumento] = useState<Documento | null>(null);
    const [rejectDocumento, setRejectDocumento] = useState<Documento | null>(null);
    const [motivoRechazo, setMotivoRechazo] = useState('');
    const [rejectError, setRejectError] = useState('');

    const handleFilterChange = (key: string, value: string): void => {
        router.get('/documentos/aprobaciones', { ...filters, [key]: value }, { preserveState: true });
    };

    const buildPageHref = (page: number): string => {
        const params = new URLSearchParams();

        if (filters.tipo_documento_id) {
            params.set('tipo_documento_id', filters.tipo_documento_id);
        }

        if (filters.contratista_id) {
            params.set('contratista_id', filters.contratista_id);
        }

        if (filters.ano) {
            params.set('ano', filters.ano);
        }

        params.set('page', page.toString());

        return `/documentos/aprobaciones?${params.toString()}`;
    };

    const approveDocumento = (documento: Documento): void => {
        router.post(`/documentos/${documento.id}/approve`, {}, { preserveScroll: true });
    };

    const openRejectDialog = (documento: Documento): void => {
        setRejectDocumento(documento);
        setMotivoRechazo('');
        setRejectError('');
    };

    const submitReject = (): void => {
        if (!rejectDocumento) {
            return;
        }

        const motivo = motivoRechazo.trim();
        if (motivo.length < 5) {
            setRejectError('Ingresa un motivo de rechazo válido (mínimo 5 caracteres).');
            return;
        }

        router.post(
            `/documentos/${rejectDocumento.id}/reject`,
            { motivo_rechazo: motivo },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRejectDocumento(null);
                    setMotivoRechazo('');
                    setRejectError('');
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    if (typeof firstError === 'string' && firstError.length > 0) {
                        setRejectError(firstError);
                    }
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aprobación de Documentos" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Bandeja de Aprobación</h1>
                        <p className="text-muted-foreground">
                            Revisión de documentos cargados pendientes de validación administrativa.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/documentos">Ir a Documentos</Link>
                        </Button>
                        <Badge className="bg-[var(--brand-orange)] text-white">
                            {documentos.total} pendiente(s)
                        </Badge>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ShieldAlert className="size-4 text-[var(--brand-orange-strong)]" />
                            Filtros de aprobación
                        </CardTitle>
                        <CardDescription>
                            Acota la revisión por tipo de documento, contratista o año.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Tipo de Documento</label>
                            <Select
                                value={filters.tipo_documento_id || 'all'}
                                onValueChange={(value) =>
                                    handleFilterChange('tipo_documento_id', value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los tipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    {tiposDocumentos.map((tipo) => (
                                        <SelectItem key={tipo.id} value={tipo.id.toString()}>
                                            {tipo.nombre}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Contratista</label>
                            <Select
                                value={filters.contratista_id || 'all'}
                                onValueChange={(value) =>
                                    handleFilterChange('contratista_id', value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los contratistas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los contratistas</SelectItem>
                                    {contratistas.map((contratista) => (
                                        <SelectItem key={contratista.value} value={contratista.value}>
                                            {contratista.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Año</label>
                            <Select
                                value={filters.ano || 'all'}
                                onValueChange={(value) =>
                                    handleFilterChange('ano', value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los años" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los años</SelectItem>
                                    {Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i).map((year) => (
                                        <SelectItem key={year} value={year.toString()}>
                                            {year}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Pendientes de aprobación</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Período</TableHead>
                                    <TableHead>Contratista</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha de Carga</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {documentos.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center text-muted-foreground">
                                            No hay documentos pendientes con los filtros seleccionados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    documentos.data.map((documento) => (
                                        <TableRow key={documento.id}>
                                            <TableCell className="font-medium">
                                                <div className="flex items-center gap-2">
                                                    <FileText className="size-4 text-muted-foreground" />
                                                    {documento.tipo_documento.nombre}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {documento.periodo_mes
                                                    ? `${meses[documento.periodo_mes - 1]} ${documento.periodo_ano}`
                                                    : documento.periodo_ano}
                                            </TableCell>
                                            <TableCell>
                                                {documento.contratista.nombre_fantasia || documento.contratista.razon_social}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    <Clock className="mr-1 size-3" />
                                                    Pendiente de aprobación
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {new Date(documento.created_at).toLocaleDateString('es-CL')}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        type="button"
                                                        onClick={() => setPreviewDocumento(documento)}
                                                    >
                                                        <Eye className="size-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        type="button"
                                                        onClick={() => approveDocumento(documento)}
                                                        className="text-[var(--brand-green)]"
                                                    >
                                                        <CheckCircle className="size-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        type="button"
                                                        onClick={() => openRejectDialog(documento)}
                                                        className="text-destructive"
                                                    >
                                                        <XCircle className="size-4" />
                                                    </Button>
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/documentos/${documento.id}/download`}>
                                                            <Download className="size-4" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>

                        {documentos.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Página {documentos.current_page} de {documentos.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={documentos.current_page === 1}
                                        asChild
                                    >
                                        <Link
                                            href={buildPageHref(documentos.current_page - 1)}
                                            preserveState
                                        >
                                            Anterior
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={documentos.current_page === documentos.last_page}
                                        asChild
                                    >
                                        <Link
                                            href={buildPageHref(documentos.current_page + 1)}
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
                            {previewDocumento?.archivo_nombre_original || 'Documento'}
                        </DialogDescription>
                    </DialogHeader>

                    {previewDocumento && (
                        <div className="space-y-3">
                            <div className="flex justify-end">
                                <Button variant="outline" asChild>
                                    <Link href={`/documentos/${previewDocumento.id}/download`}>
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
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={rejectDocumento !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRejectDocumento(null);
                        setMotivoRechazo('');
                        setRejectError('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Rechazar documento</DialogTitle>
                        <DialogDescription>
                            Indica el motivo para notificar al contratista.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3">
                        <Textarea
                            value={motivoRechazo}
                            onChange={(event) => setMotivoRechazo(event.target.value)}
                            placeholder="Ejemplo: archivo ilegible, información incompleta, período incorrecto..."
                            rows={4}
                        />
                        {rejectError && (
                            <p className="text-sm text-destructive">{rejectError}</p>
                        )}

                        <div className="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                type="button"
                                onClick={() => {
                                    setRejectDocumento(null);
                                    setMotivoRechazo('');
                                    setRejectError('');
                                }}
                            >
                                Cancelar
                            </Button>
                            <Button type="button" variant="destructive" onClick={submitReject}>
                                Confirmar rechazo
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
