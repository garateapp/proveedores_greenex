import { FormEventHandler, useMemo } from 'react';
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

interface ContratistaOption {
    value: number;
    label: string;
}

interface Props {
    contratistas: ContratistaOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Personal', href: '/trabajadores' },
    { title: 'Crear', href: '/trabajadores/create' },
];

export default function TrabajadorCreate({ contratistas }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        documento: '',
        nombre: '',
        apellido: '',
        email: '',
        telefono: '',
        fecha_ingreso: '',
        observaciones: '',
        contratista_id: contratistas[0]?.value?.toString() ?? '',
    });

    const isAdmin = useMemo(() => !!contratistas.length, [contratistas.length]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/trabajadores');
    };

    return (
        <>
            <Head title="Crear Trabajador" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/trabajadores">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Crear Trabajador</h1>
                        <p className="text-muted-foreground">
                            Registre un nuevo trabajador (RUT sin puntos y con dígito verificador).
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos del Trabajador</CardTitle>
                        <CardDescription>Complete la información requerida.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="documento">RUT</Label>
                                    <Input
                                        id="documento"
                                        placeholder="12345678-9"
                                        value={data.documento}
                                        onChange={(e) => setData('documento', e.target.value)}
                                        required
                                    />
                                    {errors.documento && (
                                        <p className="text-sm text-destructive">{errors.documento}</p>
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
                                    <Label htmlFor="apellido">Apellidos</Label>
                                    <Input
                                        id="apellido"
                                        value={data.apellido}
                                        onChange={(e) => setData('apellido', e.target.value)}
                                        required
                                    />
                                    {errors.apellido && (
                                        <p className="text-sm text-destructive">{errors.apellido}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-destructive">{errors.email}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="telefono">Teléfono</Label>
                                    <Input
                                        id="telefono"
                                        value={data.telefono}
                                        onChange={(e) => setData('telefono', e.target.value)}
                                    />
                                    {errors.telefono && (
                                        <p className="text-sm text-destructive">{errors.telefono}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="fecha_ingreso">Fecha de ingreso</Label>
                                    <Input
                                        id="fecha_ingreso"
                                        type="date"
                                        value={data.fecha_ingreso}
                                        onChange={(e) => setData('fecha_ingreso', e.target.value)}
                                    />
                                    {errors.fecha_ingreso && (
                                        <p className="text-sm text-destructive">{errors.fecha_ingreso}</p>
                                    )}
                                </div>
                                {isAdmin && (
                                    <div className="space-y-2">
                                        <Label htmlFor="contratista_id">Contratista</Label>
                                        <Select
                                            value={data.contratista_id}
                                            onValueChange={(value) => setData('contratista_id', value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccione un contratista" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {contratistas.map((c) => (
                                                    <SelectItem key={c.value} value={c.value.toString()}>
                                                        {c.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.contratista_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.contratista_id}
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="observaciones">Observaciones</Label>
                                <textarea
                                    id="observaciones"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    rows={3}
                                    value={data.observaciones}
                                    onChange={(e) => setData('observaciones', e.target.value)}
                                />
                                {errors.observaciones && (
                                    <p className="text-sm text-destructive">{errors.observaciones}</p>
                                )}
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    Guardar
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
            </div>
        </>
    );
}

TrabajadorCreate.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);
