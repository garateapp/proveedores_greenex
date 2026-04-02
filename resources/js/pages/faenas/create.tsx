import { FormEventHandler } from 'react';
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

interface TipoFaenaOption {
    value: number;
    label: string;
}

interface Props {
    tiposFaena: TipoFaenaOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Faenas', href: '/faenas' },
    { title: 'Crear', href: '/faenas/create' },
];

export default function FaenaCreate({ tiposFaena }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        tipo_faena_id: '',
        nombre: '',
        codigo: '',
        descripcion: '',
        ubicacion: '',
        fecha_inicio: '',
        fecha_termino: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/faenas');
    };

    return (
        <>
            <Head title="Crear Faena" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/faenas">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Crear Faena</h1>
                        <p className="text-muted-foreground">Registre una nueva faena o cuadrilla.</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la Faena</CardTitle>
                        <CardDescription>Complete la informacion requerida.</CardDescription>
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
                                    Guardar
                                </Button>
                                <Link href="/faenas">
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

FaenaCreate.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);
