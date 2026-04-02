import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface HistorialItem {
    id: number;
    estado: string;
    observaciones?: string | null;
    created_at: string;
    usuario?: { name: string };
}

interface EstadoPago {
    id: number;
    numero_documento: string;
    estado: string;
    fecha_documento: string;
    monto: number;
    contratista?: { razon_social: string };
    observaciones?: string | null;
    motivo_retencion?: string | null;
    fecha_pago_estimada?: string | null;
    fecha_pago_real?: string | null;
    historial?: HistorialItem[];
}

interface Props {
    estadoPago: EstadoPago;
}

const breadcrumbs = (id: number): BreadcrumbItem[] => [
    { title: 'Estados de Pago', href: '/estados-pago' },
    { title: `Detalle #${id}`, href: `/estados-pago/${id}` },
];

export default function EstadoPagoShow({ estadoPago }: Props) {
    const page = usePage<SharedData>();
    const isAdmin = page.props.auth?.user?.isAdmin ?? false;

    const { data, setData, patch, processing, errors } = useForm({
        estado: estadoPago.estado,
        observaciones: estadoPago.observaciones ?? '',
        motivo_retencion: estadoPago.motivo_retencion ?? '',
        fecha_pago_estimada: estadoPago.fecha_pago_estimada ?? '',
        fecha_pago_real: estadoPago.fecha_pago_real ?? '',
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/estados-pago/${estadoPago.id}/estado`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(estadoPago.id)}>
            <Head title={`Estado de Pago #${estadoPago.id}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Estado de Pago</h1>
                        <p className="text-muted-foreground">Documento #{estadoPago.numero_documento}</p>
                    </div>
                    <Link href="/estados-pago">
                        <Button variant="outline">Volver</Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalle</CardTitle>
                        <CardDescription>Información general del documento.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div>
                            <p className="text-sm text-muted-foreground">Contratista</p>
                            <p className="font-medium">{estadoPago.contratista?.razon_social ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Estado</p>
                            <p className="font-medium capitalize">{estadoPago.estado.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Fecha documento</p>
                            <p className="font-medium">{estadoPago.fecha_documento}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Monto</p>
                            <p className="font-medium">${estadoPago.monto}</p>
                        </div>
                        <div className="md:col-span-2">
                            <p className="text-sm text-muted-foreground">Observaciones</p>
                            <p className="font-medium">{estadoPago.observaciones ?? '—'}</p>
                        </div>
                    </CardContent>
                </Card>

                {isAdmin && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Actualizar estado</CardTitle>
                            <CardDescription>Solo para administradores.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium text-foreground" htmlFor="estado">
                                            Estado
                                        </label>
                                        <select
                                            id="estado"
                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            value={data.estado}
                                            onChange={(e) => setData('estado', e.target.value)}
                                        >
                                            <option value="recibido">Recibido</option>
                                            <option value="en_revision">En revisión</option>
                                            <option value="aprobado_pago">Aprobado para pago</option>
                                            <option value="retenido">Retenido</option>
                                            <option value="pagado">Pagado</option>
                                            <option value="rechazado">Rechazado</option>
                                        </select>
                                        {errors.estado && (
                                            <p className="text-sm text-destructive">{errors.estado as string}</p>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium text-foreground" htmlFor="fecha_pago_estimada">
                                            Fecha pago estimada
                                        </label>
                                        <Input
                                            id="fecha_pago_estimada"
                                            type="date"
                                            value={data.fecha_pago_estimada}
                                            onChange={(e) => setData('fecha_pago_estimada', e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium text-foreground" htmlFor="fecha_pago_real">
                                            Fecha pago real
                                        </label>
                                        <Input
                                            id="fecha_pago_real"
                                            type="date"
                                            value={data.fecha_pago_real}
                                            onChange={(e) => setData('fecha_pago_real', e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="motivo_retencion">
                                        Motivo de retención
                                    </label>
                                    <Textarea
                                        id="motivo_retencion"
                                        value={data.motivo_retencion}
                                        onChange={(e) => setData('motivo_retencion', e.target.value)}
                                        rows={2}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="observaciones">
                                        Observaciones
                                    </label>
                                    <Textarea
                                        id="observaciones"
                                        value={data.observaciones}
                                        onChange={(e) => setData('observaciones', e.target.value)}
                                        rows={2}
                                    />
                                </div>
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {estadoPago.historial && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Historial</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {estadoPago.historial.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">Sin historial</p>
                                ) : (
                                    estadoPago.historial.map((h) => (
                                        <div key={h.id} className="rounded-md border border-border p-3">
                                            <p className="text-sm font-medium capitalize">
                                                Estado: {h.estado.replace('_', ' ')}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {h.created_at} — {h.usuario?.name ?? 'Sistema'}
                                            </p>
                                            {h.observaciones && (
                                                <p className="text-sm text-foreground mt-1">{h.observaciones}</p>
                                            )}
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
