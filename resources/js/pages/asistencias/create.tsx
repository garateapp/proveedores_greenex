import { useForm, Link, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Users } from 'lucide-react';
import type React from 'react';
import { useMemo, useState } from 'react';

interface Trabajador {
    id: string;
    nombre_completo: string;
    documento: string;
}

interface Faena {
    id: number;
    nombre: string;
}

interface Props {
    trabajadores: Trabajador[];
    faenas: Faena[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Asistencias', href: '/asistencias' },
    { title: 'Registrar', href: '/asistencias/create' },
];

export default function AsistenciasCreate({ trabajadores, faenas }: Props) {
    const [search, setSearch] = useState('');
    const [tipo, setTipo] = useState<'entrada' | 'salida'>('entrada');
    const [faenaId, setFaenaId] = useState<string>('');

    const { data, setData, post, processing, errors } = useForm({
        asistencias: [] as { trabajador_id: string; tipo: 'entrada' | 'salida'; faena_id: string | null; fecha_hora: string }[],
    });

    const filteredTrabajadores = useMemo(() => {
        if (!search) return trabajadores;
        const q = search.toLowerCase();
        return trabajadores.filter((t) => `${t.nombre_completo} ${t.documento}`.toLowerCase().includes(q));
    }, [search, trabajadores]);

    const handleToggle = (trabajadorId: string, checked: boolean) => {
        const nowIso = new Date().toISOString();
        setData('asistencias', (prev) => {
            const next = prev.filter((a) => a.trabajador_id !== trabajadorId);
            if (checked) {
                next.push({
                    trabajador_id: trabajadorId,
                    tipo,
                    faena_id: faenaId ? faenaId : null,
                    fecha_hora: nowIso,
                });
            }
            return next;
        });
    };

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        post('/asistencias/bulk', {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Registrar asistencia" />
            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/asistencias">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Registro masivo</h1>
                        <p className="text-muted-foreground">
                            Seleccione entrada o salida, faena opcional y marque los trabajadores.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Configuración general</CardTitle>
                        <CardDescription>Se aplicará a todos los trabajadores seleccionados.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="tipo">Tipo de marca</Label>
                            <select
                                id="tipo"
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                value={tipo}
                                onChange={(e) => setTipo(e.target.value as 'entrada' | 'salida')}
                            >
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="faena_id">Faena (opcional)</Label>
                            <select
                                id="faena_id"
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                value={faenaId}
                                onChange={(e) => setFaenaId(e.target.value)}
                            >
                                <option value="">Sin faena</option>
                                {faenas.map((f) => (
                                    <option key={f.id} value={f.id.toString()}>
                                        {f.nombre}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="search">Buscar trabajadores</Label>
                            <Input
                                id="search"
                                placeholder="Nombre o RUT..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex items-center justify-between">
                        <div>
                            <CardTitle>Selecciona trabajadores</CardTitle>
                            <CardDescription>
                                Activa el switch para registrar la marca con fecha y hora actual.
                            </CardDescription>
                        </div>
                        <div className="text-sm text-muted-foreground">
                            Marcados: {data.asistencias.length}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {filteredTrabajadores.length === 0 ? (
                            <p className="text-muted-foreground text-sm">No se encontraron trabajadores</p>
                        ) : (
                            filteredTrabajadores.map((t) => {
                                const selected = data.asistencias.some((a) => a.trabajador_id === t.id);
                                return (
                                    <div
                                        key={t.id}
                                        className="flex items-center justify-between rounded-md border border-border/70 px-3 py-2"
                                    >
                                        <div className="flex items-center gap-3">
                                            <Users className="size-4 text-muted-foreground" />
                                            <div className="flex flex-col">
                                                <span className="font-medium">{t.nombre_completo}</span>
                                                <span className="text-xs text-muted-foreground">{t.documento}</span>
                                            </div>
                                        </div>
                                        <Switch
                                            checked={selected}
                                            onCheckedChange={(checked) => handleToggle(t.id, checked)}
                                            aria-label="Seleccionar trabajador"
                                        />
                                    </div>
                                );
                            })
                        )}
                        {errors.asistencias && (
                            <p className="text-sm text-destructive">{errors.asistencias as string}</p>
                        )}
                    </CardContent>
                </Card>

                <div className="flex gap-3">
                    <Button onClick={submit} disabled={processing || data.asistencias.length === 0}>
                        Registrar asistencias
                    </Button>
                    {errors.asistencias && (
                        <p className="text-sm text-destructive">{errors.asistencias as string}</p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
