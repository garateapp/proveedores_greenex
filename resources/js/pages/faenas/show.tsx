import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
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
import { type FormEvent, type ReactElement, useEffect } from 'react';

interface Trabajador {
    id: string;
    documento: string;
    nombre: string;
    apellido: string;
    estado: 'activo' | 'inactivo';
    pivot?: {
        fecha_asignacion: string | null;
        fecha_desasignacion: string | null;
    };
}

interface TipoFaena {
    id: number;
    nombre: string;
}

interface Faena {
    id: number;
    tipo_faena_id: number | null;
    tipo_faena?: TipoFaena | null;
    nombre: string;
    codigo: string;
    descripcion: string | null;
    ubicacion: string | null;
    estado: 'activa' | 'inactiva' | 'finalizada';
    fecha_inicio: string | null;
    fecha_termino: string | null;
    trabajadores: Trabajador[];
    contratistas?: Array<{
        id: number;
        razon_social: string;
        nombre_fantasia: string | null;
    }>;
}

interface TrabajadorDisponible {
    id: string;
    documento: string;
    nombre: string;
    apellido: string;
}

interface Props {
    faena: Faena;
    trabajadoresDisponibles: TrabajadorDisponible[];
    contratistasDisponibles: {
        id: number;
        razon_social: string;
        nombre_fantasia: string | null;
        nombre_mostrado: string;
    }[];
}

const breadcrumbs = (id: number): BreadcrumbItem[] => [
    { title: 'Faenas', href: '/faenas' },
    { title: `Detalle #${id}`, href: `/faenas/${id}` },
];

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

export default function FaenaShow({
    faena,
    trabajadoresDisponibles,
    contratistasDisponibles,
}: Props) {
    const page = usePage<SharedData>();
    const canEditFaena = page.props.auth?.user?.isAdmin ?? false;

    const { data, setData, post, processing, errors } = useForm({
        trabajador_id: trabajadoresDisponibles[0]?.id ?? '',
    });
    const {
        data: participanteData,
        setData: setParticipanteData,
        post: postParticipante,
        processing: participanteProcessing,
        errors: participanteErrors,
    } = useForm({
        contratista_id: contratistasDisponibles[0]?.id?.toString() ?? '',
    });

    useEffect(() => {
        if (
            data.trabajador_id !== '' &&
            trabajadoresDisponibles.some(
                (trabajador) => trabajador.id === data.trabajador_id,
            )
        ) {
            return;
        }

        setData('trabajador_id', trabajadoresDisponibles[0]?.id ?? '');
    }, [data.trabajador_id, setData, trabajadoresDisponibles]);

    useEffect(() => {
        if (
            participanteData.contratista_id !== '' &&
            contratistasDisponibles.some(
                (contratista) =>
                    contratista.id.toString() ===
                    participanteData.contratista_id,
            )
        ) {
            return;
        }

        setParticipanteData(
            'contratista_id',
            contratistasDisponibles[0]?.id?.toString() ?? '',
        );
    }, [
        contratistasDisponibles,
        participanteData.contratista_id,
        setParticipanteData,
    ]);

    const submitAsignacion = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(`/faenas/${faena.id}/trabajadores`, {
            preserveScroll: true,
        });
    };

    const desasignarTrabajador = (trabajadorId: string): void => {
        router.delete(`/faenas/${faena.id}/trabajadores/${trabajadorId}`, {
            preserveScroll: true,
        });
    };

    const submitParticipante = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        postParticipante(`/faenas/${faena.id}/contratistas`, {
            preserveScroll: true,
        });
    };

    const removerParticipante = (contratistaId: number): void => {
        router.delete(`/faenas/${faena.id}/contratistas/${contratistaId}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Faena ${faena.nombre}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {faena.nombre}
                        </h1>
                        <p className="text-muted-foreground">
                            Código: {faena.codigo}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/faenas">Volver</Link>
                        </Button>
                        {canEditFaena && (
                            <Button asChild>
                                <Link href={`/faenas/${faena.id}/edit`}>
                                    Editar
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Información General</CardTitle>
                        <CardDescription>
                            Detalle operativo de la faena.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Tipo de faena
                            </p>
                            <p className="font-medium">
                                {faena.tipo_faena?.nombre ?? '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Estado
                            </p>
                            <Badge variant={estadoBadgeVariant(faena.estado)}>
                                {faena.estado}
                            </Badge>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Ubicación
                            </p>
                            <p className="font-medium">
                                {faena.ubicacion ?? '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Fecha inicio
                            </p>
                            <p className="font-medium">
                                {faena.fecha_inicio
                                    ? new Date(
                                          faena.fecha_inicio,
                                      ).toLocaleDateString('es-CL')
                                    : '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Fecha término
                            </p>
                            <p className="font-medium">
                                {faena.fecha_termino
                                    ? new Date(
                                          faena.fecha_termino,
                                      ).toLocaleDateString('es-CL')
                                    : '—'}
                            </p>
                        </div>
                        <div className="md:col-span-2">
                            <p className="text-sm text-muted-foreground">
                                Descripción
                            </p>
                            <p className="font-medium">
                                {faena.descripcion ?? '—'}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Asignar Trabajador</CardTitle>
                        <CardDescription>
                            Selecciona un trabajador activo para asignarlo a
                            esta faena.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submitAsignacion}
                            className="flex flex-col gap-3 md:flex-row md:items-end"
                        >
                            <div className="flex-1 space-y-2">
                                <Label htmlFor="trabajador_id">
                                    Trabajador disponible
                                </Label>
                                <select
                                    id="trabajador_id"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    value={data.trabajador_id}
                                    onChange={(event) =>
                                        setData(
                                            'trabajador_id',
                                            event.target.value,
                                        )
                                    }
                                    disabled={
                                        trabajadoresDisponibles.length === 0
                                    }
                                >
                                    {trabajadoresDisponibles.length === 0 ? (
                                        <option value="">
                                            No hay trabajadores disponibles
                                        </option>
                                    ) : (
                                        trabajadoresDisponibles.map(
                                            (trabajador) => (
                                                <option
                                                    key={trabajador.id}
                                                    value={trabajador.id}
                                                >
                                                    {trabajador.documento} ·{' '}
                                                    {trabajador.nombre}{' '}
                                                    {trabajador.apellido}
                                                </option>
                                            ),
                                        )
                                    )}
                                </select>
                                {errors.trabajador_id && (
                                    <p className="text-sm text-destructive">
                                        {errors.trabajador_id}
                                    </p>
                                )}
                            </div>
                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    trabajadoresDisponibles.length === 0 ||
                                    !data.trabajador_id
                                }
                            >
                                Asignar
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Contratistas Participantes</CardTitle>
                        <CardDescription>
                            Empresas habilitadas para operar y mover personal en
                            esta faena.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {canEditFaena && (
                            <form
                                onSubmit={submitParticipante}
                                className="flex flex-col gap-3 md:flex-row md:items-end"
                            >
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="contratista_id">
                                        Agregar contratista
                                    </Label>
                                    <select
                                        id="contratista_id"
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        value={participanteData.contratista_id}
                                        onChange={(event) =>
                                            setParticipanteData(
                                                'contratista_id',
                                                event.target.value,
                                            )
                                        }
                                        disabled={
                                            contratistasDisponibles.length === 0
                                        }
                                    >
                                        {contratistasDisponibles.length ===
                                        0 ? (
                                            <option value="">
                                                No hay contratistas disponibles
                                            </option>
                                        ) : (
                                            contratistasDisponibles.map(
                                                (contratista) => (
                                                    <option
                                                        key={contratista.id}
                                                        value={contratista.id.toString()}
                                                    >
                                                        {
                                                            contratista.nombre_mostrado
                                                        }
                                                    </option>
                                                ),
                                            )
                                        )}
                                    </select>
                                    {participanteErrors.contratista_id && (
                                        <p className="text-sm text-destructive">
                                            {participanteErrors.contratista_id}
                                        </p>
                                    )}
                                </div>
                                <Button
                                    type="submit"
                                    disabled={
                                        participanteProcessing ||
                                        contratistasDisponibles.length === 0 ||
                                        !participanteData.contratista_id
                                    }
                                >
                                    Agregar
                                </Button>
                            </form>
                        )}

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Contratista</TableHead>
                                    <TableHead>Razón social</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {faena.contratistas?.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={3}
                                            className="text-center text-muted-foreground"
                                        >
                                            No hay contratistas participantes.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    faena.contratistas?.map((contratista) => (
                                        <TableRow key={contratista.id}>
                                            <TableCell className="font-medium">
                                                {contratista.nombre_fantasia ??
                                                    '—'}
                                            </TableCell>
                                            <TableCell>
                                                {contratista.razon_social}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {canEditFaena ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            removerParticipante(
                                                                contratista.id,
                                                            )
                                                        }
                                                    >
                                                        Quitar
                                                    </Button>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Trabajadores Asignados</CardTitle>
                        <CardDescription>
                            Personal activo vinculado a esta faena.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>RUT</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha Asignación</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {faena.trabajadores.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-center text-muted-foreground"
                                        >
                                            No hay trabajadores asignados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    faena.trabajadores.map((trabajador) => (
                                        <TableRow key={trabajador.id}>
                                            <TableCell className="font-mono">
                                                {trabajador.documento}
                                            </TableCell>
                                            <TableCell>
                                                {trabajador.nombre}{' '}
                                                {trabajador.apellido}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        trabajador.estado ===
                                                        'activo'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {trabajador.estado}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {trabajador.pivot
                                                    ?.fecha_asignacion
                                                    ? new Date(
                                                          trabajador.pivot.fecha_asignacion,
                                                      ).toLocaleDateString(
                                                          'es-CL',
                                                      )
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        desasignarTrabajador(
                                                            trabajador.id,
                                                        )
                                                    }
                                                >
                                                    Desasignar
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

FaenaShow.layout = (page: ReactElement<Props>) => (
    <AppLayout breadcrumbs={breadcrumbs(page.props.faena.id)}>{page}</AppLayout>
);
