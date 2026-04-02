import { type FormEventHandler, type ReactElement } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft } from 'lucide-react';

interface Faena {
    id: number;
    tipo_faena_id: number | null;
    nombre: string;
    codigo: string;
    descripcion: string | null;
    ubicacion: string | null;
    estado: string;
    fecha_inicio: string | null;
    fecha_termino: string | null;
}

interface TipoFaenaOption {
    value: number;
    label: string;
}

interface Props {
    faena: Faena;
    tiposFaena: TipoFaenaOption[];
}

const breadcrumbs = (id: number): BreadcrumbItem[] => [
    { title: 'Faenas', href: '/faenas' },
    { title: `Editar #${id}`, href: `/faenas/${id}/edit` },
];

export default function FaenaEdit({ faena, tiposFaena }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        tipo_faena_id: faena.tipo_faena_id ? String(faena.tipo_faena_id) : '',
        nombre: faena.nombre,
        codigo: faena.codigo,
        descripcion: faena.descripcion ?? '',
        ubicacion: faena.ubicacion ?? '',
        estado: faena.estado,
        fecha_inicio: faena.fecha_inicio ?? '',
        fecha_termino: faena.fecha_termino ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/faenas/${faena.id}`);
    };

    return (
        <>
            <Head title={`Editar ${faena.nombre}`} />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/faenas">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Editar Faena</h1>
                        <p className="text-muted-foreground">Actualice los datos de la faena.</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la Faena</CardTitle>
                        <CardDescription>Codigo actual: {faena.codigo}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="tipo_faena_id">Tipo de Faena</Label>
                                    <Select
                                        value={data.tipo_faena_id}
                                        onValueChange={(value) => setData('tipo_faena_id', value)}
                                    >
                                        <SelectTrigger id="tipo_faena_id">
                                            <SelectValue placeholder="Seleccione un tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {tiposFaena.map((tipo) => (
                                                <SelectItem key={tipo.value} value={String(tipo.value)}>
                                                    {tipo.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.tipo_faena_id && (
                                        <p className="text-sm text-destructive">{errors.tipo_faena_id}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="nombre">Nombre</Label>
                                    <Input
                                        id="nombre"
                                        value={data.nombre}
                                        onChange={(e) => setData('nombre', e.target.value)}
                                        required
                                    />
                                    {errors.nombre && (
                                        <p className="text-sm text-destructive">{errors.nombre}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="codigo">Codigo</Label>
                                    <Input
                                        id="codigo"
                                        value={data.codigo}
                                        onChange={(e) => setData('codigo', e.target.value)}
                                        required
                                    />
                                    {errors.codigo && (
                                        <p className="text-sm text-destructive">{errors.codigo}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="ubicacion">Ubicacion</Label>
                                    <Input
                                        id="ubicacion"
                                        value={data.ubicacion}
                                        onChange={(e) => setData('ubicacion', e.target.value)}
                                    />
                                    {errors.ubicacion && (
                                        <p className="text-sm text-destructive">{errors.ubicacion}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="estado">Estado</Label>
                                    <Select value={data.estado} onValueChange={(value) => setData('estado', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione un estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="activa">Activa</SelectItem>
                                            <SelectItem value="inactiva">Inactiva</SelectItem>
                                            <SelectItem value="finalizada">Finalizada</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.estado && (
                                        <p className="text-sm text-destructive">{errors.estado}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="fecha_inicio">Fecha de inicio</Label>
                                    <Input
                                        id="fecha_inicio"
                                        type="date"
                                        value={data.fecha_inicio}
                                        onChange={(e) => setData('fecha_inicio', e.target.value)}
                                    />
                                    {errors.fecha_inicio && (
                                        <p className="text-sm text-destructive">{errors.fecha_inicio}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="fecha_termino">Fecha de termino</Label>
                                    <Input
                                        id="fecha_termino"
                                        type="date"
                                        value={data.fecha_termino}
                                        onChange={(e) => setData('fecha_termino', e.target.value)}
                                    />
                                    {errors.fecha_termino && (
                                        <p className="text-sm text-destructive">{errors.fecha_termino}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="descripcion">Descripcion</Label>
                                <textarea
                                    id="descripcion"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    rows={3}
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-destructive">{errors.descripcion}</p>
                                )}
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/faenas">
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

FaenaEdit.layout = (page: ReactElement<Props>) => (
    <AppLayout breadcrumbs={breadcrumbs(page.props.faena.id)}>{page}</AppLayout>
);
