import { AppLayout } from '@/layouts/app';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Clock, Plus, Search, SquareArrowOutUpRight } from 'lucide-react';
import { useState } from 'react';

interface EstadoOption {
    value: string;
    label: string;
}

interface TrabajadorOption {
    id: string;
    nombre_completo: string;
    documento: string;
    contratista: string | null;
}

interface TarjetaQrItem {
    id: number;
    numero_serie: string;
    codigo_qr: string;
    estado: string;
    observaciones: string | null;
    trabajador_actual: {
        id: string;
        nombre_completo: string;
        contratista: string | null;
        asignada_en: string | null;
    } | null;
}

interface Props {
    tarjetas: TarjetaQrItem[];
    trabajadores: TrabajadorOption[];
    filters: {
        search?: string;
        estado?: string | null;
    };
    estados: EstadoOption[];
}

function estadoVariant(estado: string) {
    switch (estado) {
        case 'asignada':
            return 'default';
        case 'bloqueada':
            return 'destructive';
        case 'baja':
            return 'secondary';
        default:
            return 'outline';
    }
}

export default function PackingTarjetasIndex({ tarjetas, trabajadores, filters, estados }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado ?? 'all');

    const createForm = useForm({
        numero_serie: '',
        codigo_qr: '',
        estado: 'disponible',
        observaciones: '',
    });

    const assignForm = useForm({
        tarjeta_id: tarjetas[0]?.id.toString() ?? '',
        trabajador_id: trabajadores[0]?.id ?? '',
        asignada_en: new Date().toISOString().slice(0, 16),
        observaciones: '',
    });

    const handleSearch = () => {
        router.get(
            '/admin/packing/tarjetas',
            {
                search: search || undefined,
                estado: estado === 'all' ? undefined : estado,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const submitCreate = () => {
        createForm.post('/admin/packing/tarjetas', {
            preserveScroll: true,
            onSuccess: () => createForm.reset('numero_serie', 'codigo_qr', 'observaciones'),
        });
    };

    const submitAssignment = () => {
        if (!assignForm.data.tarjeta_id) {
            return;
        }

        assignForm.post(`/admin/packing/tarjetas/${assignForm.data.tarjeta_id}/asignaciones`, {
            preserveScroll: true,
            onSuccess: () => assignForm.reset('observaciones'),
        });
    };

    return (
        <>
            <Head title="Packing QR" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Packing QR</h1>
                        <p className="text-muted-foreground">
                            Administre tarjetas QR reutilizables y sus asignaciones al personal.
                        </p>
                    </div>
                    <Link href="/admin/packing/marcaciones">
                        <Button variant="outline">
                            <Clock className="mr-2 h-4 w-4" />
                            Ver marcaciones
                        </Button>
                    </Link>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.15fr,0.95fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Nueva tarjeta</CardTitle>
                            <CardDescription>
                                Registre una tarjeta física con su número de serie y contenido QR.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="numero_serie">Número de serie</Label>
                                    <Input
                                        id="numero_serie"
                                        value={createForm.data.numero_serie}
                                        onChange={(event) =>
                                            createForm.setData('numero_serie', event.target.value)
                                        }
                                        placeholder="PACK-0001"
                                    />
                                    {createForm.errors.numero_serie && (
                                        <p className="text-sm text-destructive">
                                            {createForm.errors.numero_serie}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="codigo_qr">Código QR</Label>
                                    <Input
                                        id="codigo_qr"
                                        value={createForm.data.codigo_qr}
                                        onChange={(event) =>
                                            createForm.setData('codigo_qr', event.target.value)
                                        }
                                        placeholder="QR-PACK-0001"
                                    />
                                    {createForm.errors.codigo_qr && (
                                        <p className="text-sm text-destructive">
                                            {createForm.errors.codigo_qr}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="estado">Estado inicial</Label>
                                    <Select
                                        value={createForm.data.estado}
                                        onValueChange={(value) => createForm.setData('estado', value)}
                                    >
                                        <SelectTrigger id="estado">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {estados.map((estadoOption) => (
                                                <SelectItem
                                                    key={estadoOption.value}
                                                    value={estadoOption.value}
                                                >
                                                    {estadoOption.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="observaciones">Observaciones</Label>
                                    <Input
                                        id="observaciones"
                                        value={createForm.data.observaciones}
                                        onChange={(event) =>
                                            createForm.setData('observaciones', event.target.value)
                                        }
                                        placeholder="Uso interno"
                                    />
                                </div>
                            </div>

                            <Button onClick={submitCreate} disabled={createForm.processing}>
                                <Plus className="mr-2 h-4 w-4" />
                                Crear tarjeta
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Asignar tarjeta</CardTitle>
                            <CardDescription>
                                La reasignación cierra automáticamente la asignación activa anterior.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="tarjeta_id">Tarjeta</Label>
                                <Combobox
                                    options={tarjetas.map((tarjeta) => ({
                                        value: tarjeta.id.toString(),
                                        label: `${tarjeta.numero_serie} · ${tarjeta.estado}`,
                                        searchValue: `${tarjeta.numero_serie} ${tarjeta.estado}`,
                                    }))}
                                    value={assignForm.data.tarjeta_id}
                                    onValueChange={(value) => assignForm.setData('tarjeta_id', value)}
                                    placeholder="Seleccione una tarjeta"
                                    searchPlaceholder="Buscar tarjeta por número de serie..."
                                    emptyMessage="No se encontraron tarjetas."
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="trabajador_id">Trabajador</Label>
                                <Combobox
                                    options={trabajadores.map((trabajador) => ({
                                        value: trabajador.id,
                                        label: `${trabajador.nombre_completo} · ${trabajador.documento}`,
                                        searchValue: `${trabajador.nombre_completo} ${trabajador.documento}`,
                                    }))}
                                    value={assignForm.data.trabajador_id}
                                    onValueChange={(value) =>
                                        assignForm.setData('trabajador_id', value)
                                    }
                                    placeholder="Seleccione un trabajador"
                                    searchPlaceholder="Buscar trabajador por nombre o documento..."
                                    emptyMessage="No se encontraron trabajadores."
                                />
                                {assignForm.errors.trabajador_id && (
                                    <p className="text-sm text-destructive">
                                        {assignForm.errors.trabajador_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="asignada_en">Fecha de asignación</Label>
                                    <Input
                                        id="asignada_en"
                                        type="datetime-local"
                                        value={assignForm.data.asignada_en}
                                        onChange={(event) =>
                                            assignForm.setData('asignada_en', event.target.value)
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="assign_observaciones">Observaciones</Label>
                                    <Input
                                        id="assign_observaciones"
                                        value={assignForm.data.observaciones}
                                        onChange={(event) =>
                                            assignForm.setData('observaciones', event.target.value)
                                        }
                                        placeholder="Motivo o contexto"
                                    />
                                </div>
                            </div>

                            <Button
                                onClick={submitAssignment}
                                disabled={
                                    assignForm.processing ||
                                    !assignForm.data.tarjeta_id ||
                                    !assignForm.data.trabajador_id
                                }
                            >
                                <SquareArrowOutUpRight className="mr-2 h-4 w-4" />
                                Asignar tarjeta
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Inventario</CardTitle>
                        <CardDescription>
                            Consulte el estado actual y la última asignación activa de cada tarjeta.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-[1fr,220px,140px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Buscar</Label>
                                <Input
                                    id="search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    onKeyDown={(event) => event.key === 'Enter' && handleSearch()}
                                    placeholder="Serie o código QR"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="estado_filter">Estado</Label>
                                <Select value={estado} onValueChange={setEstado}>
                                    <SelectTrigger id="estado_filter">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        {estados.map((estadoOption) => (
                                            <SelectItem
                                                key={estadoOption.value}
                                                value={estadoOption.value}
                                            >
                                                {estadoOption.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button className="w-full" onClick={handleSearch}>
                                    <Search className="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                            </div>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Serie</TableHead>
                                    <TableHead>Código QR</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Asignación activa</TableHead>
                                    <TableHead>Observaciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tarjetas.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center">
                                            No hay tarjetas registradas.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    tarjetas.map((tarjeta) => (
                                        <TableRow key={tarjeta.id}>
                                            <TableCell className="font-medium">
                                                {tarjeta.numero_serie}
                                            </TableCell>
                                            <TableCell>{tarjeta.codigo_qr}</TableCell>
                                            <TableCell>
                                                <Badge variant={estadoVariant(tarjeta.estado)}>
                                                    {tarjeta.estado}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {tarjeta.trabajador_actual ? (
                                                    <div className="flex flex-col text-sm">
                                                        <span className="font-medium">
                                                            {tarjeta.trabajador_actual.nombre_completo}
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {tarjeta.trabajador_actual.contratista ?? '-'}
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {tarjeta.trabajador_actual.asignada_en}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Sin asignación activa
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>{tarjeta.observaciones || '-'}</TableCell>
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

PackingTarjetasIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
