import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, UploadCloud } from 'lucide-react';
import { FormEventHandler, useEffect, useMemo } from 'react';

interface Trabajador {
    id: string;
    documento: string;
    nombre: string;
    apellido: string;
    contratista_id: number | null;
    email: string | null;
    telefono: string | null;
    estado: string;
    fecha_ingreso: string | null;
    observaciones: string | null;
}

interface TipoDocumentoOption {
    id: number;
    nombre: string;
    codigo: string;
    formatos_permitidos: string[] | null;
    tamano_maximo_kb: number;
}

interface DocumentoTrabajador {
    id: number;
    tipo_documento_id: number;
    tipo_documento_nombre: string | null;
    archivo_nombre_original: string;
    created_at: string | null;
}

interface Props {
    trabajador: Trabajador;
    tiposDocumentos: TipoDocumentoOption[];
    documentosTrabajador: DocumentoTrabajador[];
    sinFaenaActiva: boolean;
    contratistas: { value: number; label: string }[];
    faenasDisponibles: {
        id: number;
        nombre: string;
        codigo: string;
        tipo_faena: string | null;
    }[];
    faenaIdsAsignadas: number[];
}

const breadcrumbs = (id: string): BreadcrumbItem[] => [
    { title: 'Personal', href: '/trabajadores' },
    { title: `Editar ${id}`, href: `/trabajadores/${id}/edit` },
];

export default function TrabajadorEdit({
    trabajador,
    tiposDocumentos,
    documentosTrabajador,
    sinFaenaActiva,
    contratistas,
    faenasDisponibles,
    faenaIdsAsignadas,
}: Props) {
    const isAdmin = contratistas.length > 0;

    const { data, setData, patch, processing, errors } = useForm({
        nombre: trabajador.nombre,
        apellido: trabajador.apellido,
        email: trabajador.email ?? '',
        telefono: trabajador.telefono ?? '',
        estado: trabajador.estado,
        fecha_ingreso: trabajador.fecha_ingreso ?? '',
        observaciones: trabajador.observaciones ?? '',
        contratista_id: trabajador.contratista_id?.toString() ?? '',
        faena_ids: faenaIdsAsignadas,
    });

    const uploadedTipoIds = useMemo(
        () =>
            new Set(
                documentosTrabajador.map(
                    (documento) => documento.tipo_documento_id,
                ),
            ),
        [documentosTrabajador],
    );
    const availableTipos = useMemo(
        () => tiposDocumentos.filter((tipo) => !uploadedTipoIds.has(tipo.id)),
        [tiposDocumentos, uploadedTipoIds],
    );

    const {
        data: documentoData,
        setData: setDocumentoData,
        post: postDocumento,
        processing: documentoProcessing,
        errors: documentoErrors,
        reset: resetDocumento,
    } = useForm({
        tipo_documento_id: availableTipos[0]?.id?.toString() ?? '',
        archivo: null as File | null,
    });

    useEffect(() => {
        if (!documentoData.tipo_documento_id && availableTipos.length > 0) {
            setDocumentoData(
                'tipo_documento_id',
                availableTipos[0].id.toString(),
            );
        }
    }, [availableTipos, documentoData.tipo_documento_id, setDocumentoData]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/trabajadores/${trabajador.id}`);
    };

    const toggleFaena = (faenaId: number): void => {
        const nextFaenaIds = data.faena_ids.includes(faenaId)
            ? data.faena_ids.filter((id) => id !== faenaId)
            : [...data.faena_ids, faenaId];

        setData('faena_ids', nextFaenaIds);
    };

    const submitDocumento: FormEventHandler = (e) => {
        e.preventDefault();
        postDocumento(`/trabajadores/${trabajador.id}/documentos`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                resetDocumento('archivo');
                router.reload({ only: ['documentosTrabajador'] });
            },
        });
    };

    return (
        <>
            <Head title={`Editar ${trabajador.nombre}`} />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/trabajadores">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <Link href={`/centro-carga?trabajador_id=${trabajador.id}`}>
                        <Button variant="secondary" size="sm">
                            <UploadCloud className="mr-2 h-4 w-4" />
                            Centro de Carga
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Editar Trabajador
                        </h1>
                        <p className="text-muted-foreground">
                            Actualice la información del trabajador.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos del Trabajador</CardTitle>
                        <CardDescription>
                            RUT: {trabajador.documento}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="nombre">Nombre</Label>
                                    <Input
                                        id="nombre"
                                        value={data.nombre}
                                        onChange={(e) =>
                                            setData('nombre', e.target.value)
                                        }
                                        required
                                    />
                                    {errors.nombre && (
                                        <p className="text-sm text-destructive">
                                            {errors.nombre}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="apellido">Apellidos</Label>
                                    <Input
                                        id="apellido"
                                        value={data.apellido}
                                        onChange={(e) =>
                                            setData('apellido', e.target.value)
                                        }
                                        required
                                    />
                                    {errors.apellido && (
                                        <p className="text-sm text-destructive">
                                            {errors.apellido}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-destructive">
                                            {errors.email}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="telefono">Teléfono</Label>
                                    <Input
                                        id="telefono"
                                        value={data.telefono}
                                        onChange={(e) =>
                                            setData('telefono', e.target.value)
                                        }
                                    />
                                    {errors.telefono && (
                                        <p className="text-sm text-destructive">
                                            {errors.telefono}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="fecha_ingreso">
                                        Fecha de ingreso
                                    </Label>
                                    <Input
                                        id="fecha_ingreso"
                                        type="date"
                                        value={data.fecha_ingreso}
                                        onChange={(e) =>
                                            setData(
                                                'fecha_ingreso',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {errors.fecha_ingreso && (
                                        <p className="text-sm text-destructive">
                                            {errors.fecha_ingreso}
                                        </p>
                                    )}
                                </div>
                                {isAdmin && (
                                    <div className="space-y-2">
                                        <Label htmlFor="contratista_id">
                                            Contratista
                                        </Label>
                                        <Select
                                            value={data.contratista_id}
                                            onValueChange={(value) =>
                                                setData('contratista_id', value)
                                            }
                                        >
                                            <SelectTrigger id="contratista_id">
                                                <SelectValue placeholder="Seleccione un contratista" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {contratistas.map(
                                                    (contratista) => (
                                                        <SelectItem
                                                            key={
                                                                contratista.value
                                                            }
                                                            value={contratista.value.toString()}
                                                        >
                                                            {contratista.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        {errors.contratista_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.contratista_id}
                                            </p>
                                        )}
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <Label htmlFor="estado">Estado</Label>
                                    <Select
                                        value={data.estado}
                                        onValueChange={(value) =>
                                            setData('estado', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione un estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="activo">
                                                Activo
                                            </SelectItem>
                                            <SelectItem value="inactivo">
                                                Inactivo
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.estado && (
                                        <p className="text-sm text-destructive">
                                            {errors.estado}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="observaciones">
                                    Observaciones
                                </Label>
                                <textarea
                                    id="observaciones"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    rows={3}
                                    value={data.observaciones}
                                    onChange={(e) =>
                                        setData('observaciones', e.target.value)
                                    }
                                />
                                {errors.observaciones && (
                                    <p className="text-sm text-destructive">
                                        {errors.observaciones}
                                    </p>
                                )}
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Link href="/trabajadores">
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Faenas Activas</CardTitle>
                        <CardDescription>
                            {isAdmin
                                ? 'Asigne este trabajador a una o más faenas activas.'
                                : 'Mueva al trabajador entre las faenas activas donde participa su empresa.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {faenasDisponibles.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay faenas activas disponibles para
                                asignación.
                            </p>
                        ) : (
                            <div className="grid gap-3 md:grid-cols-2">
                                {faenasDisponibles.map((faena) => (
                                    <label
                                        key={faena.id}
                                        className="flex items-start gap-3 rounded-lg border border-border/60 px-3 py-3"
                                    >
                                        <Checkbox
                                            checked={data.faena_ids.includes(
                                                faena.id,
                                            )}
                                            onCheckedChange={() =>
                                                toggleFaena(faena.id)
                                            }
                                        />
                                        <div className="space-y-1">
                                            <p className="text-sm font-medium text-foreground">
                                                {faena.nombre}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {faena.codigo}
                                                {faena.tipo_faena
                                                    ? ` · ${faena.tipo_faena}`
                                                    : ''}
                                            </p>
                                        </div>
                                    </label>
                                ))}
                            </div>
                        )}
                        {errors.faena_ids && (
                            <p className="text-sm text-destructive">
                                {errors.faena_ids as unknown as string}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Documentos del Trabajador</CardTitle>
                        <CardDescription>
                            Suba los documentos obligatorios del trabajador.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <form onSubmit={submitDocumento} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="tipo_documento_id">
                                        Tipo de documento
                                    </Label>
                                    <Select
                                        value={documentoData.tipo_documento_id}
                                        onValueChange={(value) =>
                                            setDocumentoData(
                                                'tipo_documento_id',
                                                value,
                                            )
                                        }
                                        disabled={availableTipos.length === 0}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione un tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableTipos.map((tipo) => (
                                                <SelectItem
                                                    key={tipo.id}
                                                    value={tipo.id.toString()}
                                                >
                                                    {tipo.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {documentoErrors.tipo_documento_id && (
                                        <p className="text-sm text-destructive">
                                            {documentoErrors.tipo_documento_id}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="archivo">Archivo</Label>
                                    <Input
                                        id="archivo"
                                        type="file"
                                        accept=".pdf,.csv,.txt,.xlsx,.docx"
                                        onChange={(e) =>
                                            setDocumentoData(
                                                'archivo',
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                        required
                                    />
                                    {documentoErrors.archivo && (
                                        <p className="text-sm text-destructive">
                                            {documentoErrors.archivo}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {sinFaenaActiva && (
                                <p className="text-sm text-destructive">
                                    El trabajador no tiene una faena activa con
                                    tipo definido. Asigne una faena activa para
                                    habilitar la carga documental.
                                </p>
                            )}

                            {!sinFaenaActiva && availableTipos.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    Todos los documentos requeridos ya fueron
                                    cargados.
                                </p>
                            )}

                            <div className="flex gap-4">
                                <Button
                                    type="submit"
                                    disabled={
                                        documentoProcessing ||
                                        availableTipos.length === 0 ||
                                        !documentoData.archivo ||
                                        !documentoData.tipo_documento_id ||
                                        sinFaenaActiva
                                    }
                                >
                                    Subir documento
                                </Button>
                            </div>
                        </form>

                        {documentosTrabajador.length > 0 && (
                            <div className="space-y-3">
                                <h3 className="text-sm font-semibold text-foreground">
                                    Documentos cargados
                                </h3>
                                <div className="grid gap-2">
                                    {documentosTrabajador.map((documento) => (
                                        <div
                                            key={documento.id}
                                            className="flex items-center justify-between rounded-md border border-border/60 px-3 py-2 text-sm"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {documento.tipo_documento_nombre ??
                                                        'Documento'}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {
                                                        documento.archivo_nombre_original
                                                    }
                                                    {documento.created_at
                                                        ? ` · ${documento.created_at}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <Badge variant="default">
                                                Cargado
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

TrabajadorEdit.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs((page as any).props.trabajador.id)}>
        {page}
    </AppLayout>
);
