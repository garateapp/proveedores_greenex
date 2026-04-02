import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft } from 'lucide-react';
import type React from 'react';

interface ContratistaOption {
    value: number;
    label: string;
}

interface Props {
    contratistas: ContratistaOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Estados de Pago', href: '/estados-pago' },
    { title: 'Registrar', href: '/estados-pago/create' },
];

export default function EstadoPagoCreate({ contratistas }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        contratista_id: contratistas[0]?.value?.toString() ?? '',
        numero_documento: '',
        fecha_documento: '',
        monto: '',
        observaciones: '',
        fecha_pago_estimada: '',
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        post('/estados-pago');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Registrar Estado de Pago" />
            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/estados-pago">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Registrar Estado de Pago</h1>
                        <p className="text-muted-foreground">
                            Ingrese los datos del documento para seguimiento de pago.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos del documento</CardTitle>
                        <CardDescription>Campos obligatorios marcados.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="contratista_id">Contratista</Label>
                                    <select
                                        id="contratista_id"
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        value={data.contratista_id}
                                        onChange={(e) => setData('contratista_id', e.target.value)}
                                        required
                                    >
                                        <option value="">Seleccione un contratista</option>
                                        {contratistas.map((c) => (
                                            <option key={c.value} value={c.value}>
                                                {c.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.contratista_id && (
                                        <p className="text-sm text-destructive">{errors.contratista_id as string}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="numero_documento">Número de documento</Label>
                                    <Input
                                        id="numero_documento"
                                        value={data.numero_documento}
                                        onChange={(e) => setData('numero_documento', e.target.value)}
                                        required
                                    />
                                    {errors.numero_documento && (
                                        <p className="text-sm text-destructive">{errors.numero_documento as string}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="fecha_documento">Fecha del documento</Label>
                                    <Input
                                        id="fecha_documento"
                                        type="date"
                                        value={data.fecha_documento}
                                        onChange={(e) => setData('fecha_documento', e.target.value)}
                                        required
                                    />
                                    {errors.fecha_documento && (
                                        <p className="text-sm text-destructive">{errors.fecha_documento as string}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="monto">Monto</Label>
                                    <Input
                                        id="monto"
                                        type="number"
                                        min={0}
                                        step="0.01"
                                        value={data.monto}
                                        onChange={(e) => setData('monto', e.target.value)}
                                        required
                                    />
                                    {errors.monto && (
                                        <p className="text-sm text-destructive">{errors.monto as string}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="fecha_pago_estimada">Fecha de pago estimada</Label>
                                    <Input
                                        id="fecha_pago_estimada"
                                        type="date"
                                        value={data.fecha_pago_estimada}
                                        onChange={(e) => setData('fecha_pago_estimada', e.target.value)}
                                    />
                                    {errors.fecha_pago_estimada && (
                                        <p className="text-sm text-destructive">
                                            {errors.fecha_pago_estimada as string}
                                        </p>
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
                                        <p className="text-sm text-destructive">{errors.observaciones as string}</p>
                                    )}
                                </div>
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    Registrar
                                </Button>
                                <Link href="/estados-pago">
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
