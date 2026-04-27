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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { AppLayout } from '@/layouts/app';
import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    Clock,
    Copy,
    Edit,
    MapPin,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import type React from 'react';
import { useState } from 'react';

interface UbicacionOption {
    id: number;
    nombre: string;
    codigo: string;
    tipo: 'principal' | 'secundaria';
    nombre_completo: string;
}

interface TurnoItem {
    id: number;
    fecha: string;
    nombre: string;
    hora_inicio: string;
    hora_fin: string;
    descripcion: string | null;
    activo: boolean;
    ubicaciones: Array<{
        id: number;
        nombre: string;
        codigo: string;
        nombre_completo: string;
    }>;
}

interface Props {
    turnos: TurnoItem[];
    ubicaciones: UbicacionOption[];
    filters: {
        fecha: string;
        fecha_anterior: string;
    };
}

export default function TurnosIndex({ turnos, ubicaciones, filters }: Props) {
    const [fecha, setFecha] = useState(filters.fecha);
    const [showDialog, setShowDialog] = useState(false);
    const [editingTurno, setEditingTurno] = useState<TurnoItem | null>(null);

    const turnoForm = useForm({
        fecha: filters.fecha,
        nombre: '',
        hora_inicio: '08:00',
        hora_fin: '18:00',
        descripcion: '',
        activo: true,
        ubicacion_ids: [] as number[],
    });

    const cloneForm = useForm({
        source_date: filters.fecha_anterior,
        target_date: filters.fecha,
    });

    const handleDateSearch = () => {
        router.get(
            '/admin/turnos',
            { fecha },
            { preserveState: true, preserveScroll: true },
        );
    };

    const openCreateDialog = () => {
        setEditingTurno(null);
        turnoForm.setData({
            fecha: filters.fecha,
            nombre: '',
            hora_inicio: '08:00',
            hora_fin: '18:00',
            descripcion: '',
            activo: true,
            ubicacion_ids: [],
        });
        turnoForm.clearErrors();
        setShowDialog(true);
    };

    const openEditDialog = (turno: TurnoItem) => {
        setEditingTurno(turno);
        turnoForm.setData({
            fecha: turno.fecha,
            nombre: turno.nombre,
            hora_inicio: turno.hora_inicio,
            hora_fin: turno.hora_fin,
            descripcion: turno.descripcion ?? '',
            activo: turno.activo,
            ubicacion_ids: turno.ubicaciones.map((ubicacion) => ubicacion.id),
        });
        turnoForm.clearErrors();
        setShowDialog(true);
    };

    const submitTurno = () => {
        if (editingTurno) {
            turnoForm.put(`/admin/turnos/${editingTurno.id}`, {
                preserveScroll: true,
                onSuccess: () => setShowDialog(false),
            });

            return;
        }

        turnoForm.post('/admin/turnos', {
            preserveScroll: true,
            onSuccess: () => setShowDialog(false),
        });
    };

    const cloneFromPreviousDay = () => {
        router.post(
            '/admin/turnos/clone',
            {
                source_date: filters.fecha_anterior,
                target_date: filters.fecha,
            },
            { preserveScroll: true },
        );
    };

    const submitSpecificClone = () => {
        cloneForm.setData('target_date', filters.fecha);
        cloneForm.post('/admin/turnos/clone', {
            preserveScroll: true,
        });
    };

    const deleteTurno = (turno: TurnoItem) => {
        if (!confirm(`¿Eliminar el turno "${turno.nombre}"?`)) {
            return;
        }

        router.delete(`/admin/turnos/${turno.id}`, {
            preserveScroll: true,
        });
    };

    const updateLocationSelection = (ubicacionId: number, selected: boolean) => {
        const currentIds = turnoForm.data.ubicacion_ids;

        turnoForm.setData(
            'ubicacion_ids',
            selected
                ? [...new Set([...currentIds, ubicacionId])]
                : currentIds.filter((id) => id !== ubicacionId),
        );
    };

    return (
        <>
            <Head title="Turnos por ubicación" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Turnos por ubicación
                        </h1>
                        <p className="text-muted-foreground">
                            Configure turnos diarios y sus ubicaciones asociadas.
                        </p>
                    </div>
                    <Button onClick={openCreateDialog}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nuevo turno
                    </Button>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr,1.1fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Fecha de trabajo</CardTitle>
                            <CardDescription>
                                Turnos configurados para el día seleccionado.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-[1fr,140px]">
                                <div className="space-y-2">
                                    <Label htmlFor="fecha">Fecha</Label>
                                    <Input
                                        id="fecha"
                                        type="date"
                                        value={fecha}
                                        onChange={(event) =>
                                            setFecha(event.target.value)
                                        }
                                        onKeyDown={(event) =>
                                            event.key === 'Enter' &&
                                            handleDateSearch()
                                        }
                                    />
                                </div>
                                <div className="flex items-end">
                                    <Button
                                        className="w-full"
                                        onClick={handleDateSearch}
                                    >
                                        <Search className="mr-2 h-4 w-4" />
                                        Ver día
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Clonar turnos</CardTitle>
                            <CardDescription>
                                Copia horarios y ubicaciones hacia {filters.fecha}.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap items-end gap-3">
                                <Button
                                    variant="outline"
                                    onClick={cloneFromPreviousDay}
                                >
                                    <Copy className="mr-2 h-4 w-4" />
                                    Clonar día anterior
                                </Button>
                                <div className="grid flex-1 gap-2 sm:grid-cols-[1fr,160px]">
                                    <div className="space-y-2">
                                        <Label htmlFor="source_date">
                                            Fecha origen
                                        </Label>
                                        <Input
                                            id="source_date"
                                            type="date"
                                            value={cloneForm.data.source_date}
                                            onChange={(event) =>
                                                cloneForm.setData(
                                                    'source_date',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="flex items-end">
                                        <Button
                                            className="w-full"
                                            onClick={submitSpecificClone}
                                            disabled={cloneForm.processing}
                                        >
                                            <CalendarDays className="mr-2 h-4 w-4" />
                                            Clonar fecha
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            {cloneForm.errors.source_date && (
                                <p className="text-sm text-destructive">
                                    {cloneForm.errors.source_date}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Turnos del día</CardTitle>
                        <CardDescription>{filters.fecha}</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Turno</TableHead>
                                    <TableHead>Horario</TableHead>
                                    <TableHead>Ubicaciones</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {turnos.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Sin turnos configurados para esta
                                            fecha.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    turnos.map((turno) => (
                                        <TableRow key={turno.id}>
                                            <TableCell className="font-medium">
                                                {turno.nombre}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Clock className="h-4 w-4 text-muted-foreground" />
                                                    {turno.hora_inicio} -{' '}
                                                    {turno.hora_fin}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex max-w-xl flex-wrap gap-1.5">
                                                    {turno.ubicaciones.map(
                                                        (ubicacion) => (
                                                            <Badge
                                                                key={
                                                                    ubicacion.id
                                                                }
                                                                variant="outline"
                                                            >
                                                                <MapPin className="mr-1 h-3 w-3" />
                                                                {
                                                                    ubicacion.nombre_completo
                                                                }
                                                            </Badge>
                                                        ),
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        turno.activo
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {turno.activo
                                                        ? 'Activo'
                                                        : 'Inactivo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {turno.descripcion || '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            openEditDialog(
                                                                turno,
                                                            )
                                                        }
                                                        title="Editar"
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            deleteTurno(turno)
                                                        }
                                                        title="Eliminar"
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={showDialog} onOpenChange={setShowDialog}>
                <DialogContent className="max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingTurno ? 'Editar turno' : 'Nuevo turno'}
                        </DialogTitle>
                        <DialogDescription>
                            {turnoForm.data.fecha}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-5">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="turno-fecha">Fecha</Label>
                                <Input
                                    id="turno-fecha"
                                    type="date"
                                    value={turnoForm.data.fecha}
                                    onChange={(event) =>
                                        turnoForm.setData(
                                            'fecha',
                                            event.target.value,
                                        )
                                    }
                                />
                                {turnoForm.errors.fecha && (
                                    <p className="text-sm text-destructive">
                                        {turnoForm.errors.fecha}
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="turno-nombre">Nombre</Label>
                                <Input
                                    id="turno-nombre"
                                    value={turnoForm.data.nombre}
                                    onChange={(event) =>
                                        turnoForm.setData(
                                            'nombre',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Turno Día"
                                />
                                {turnoForm.errors.nombre && (
                                    <p className="text-sm text-destructive">
                                        {turnoForm.errors.nombre}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="hora_inicio">Inicio</Label>
                                <Input
                                    id="hora_inicio"
                                    type="time"
                                    value={turnoForm.data.hora_inicio}
                                    onChange={(event) =>
                                        turnoForm.setData(
                                            'hora_inicio',
                                            event.target.value,
                                        )
                                    }
                                />
                                {turnoForm.errors.hora_inicio && (
                                    <p className="text-sm text-destructive">
                                        {turnoForm.errors.hora_inicio}
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="hora_fin">Fin</Label>
                                <Input
                                    id="hora_fin"
                                    type="time"
                                    value={turnoForm.data.hora_fin}
                                    onChange={(event) =>
                                        turnoForm.setData(
                                            'hora_fin',
                                            event.target.value,
                                        )
                                    }
                                />
                                {turnoForm.errors.hora_fin && (
                                    <p className="text-sm text-destructive">
                                        {turnoForm.errors.hora_fin}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="descripcion">Descripción</Label>
                            <Input
                                id="descripcion"
                                value={turnoForm.data.descripcion}
                                onChange={(event) =>
                                    turnoForm.setData(
                                        'descripcion',
                                        event.target.value,
                                    )
                                }
                                placeholder="Descripción opcional"
                            />
                            {turnoForm.errors.descripcion && (
                                <p className="text-sm text-destructive">
                                    {turnoForm.errors.descripcion}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="activo"
                                checked={turnoForm.data.activo}
                                onCheckedChange={(checked) =>
                                    turnoForm.setData(
                                        'activo',
                                        checked === true,
                                    )
                                }
                            />
                            <Label htmlFor="activo">Activo</Label>
                        </div>

                        <div className="space-y-3">
                            <Label>Ubicaciones</Label>
                            <div className="grid max-h-64 gap-2 overflow-y-auto rounded-md border p-3 md:grid-cols-2">
                                {ubicaciones.map((ubicacion) => (
                                    <label
                                        key={ubicacion.id}
                                        className="flex cursor-pointer items-start gap-3 rounded-md px-2 py-2 hover:bg-muted"
                                    >
                                        <Checkbox
                                            checked={turnoForm.data.ubicacion_ids.includes(
                                                ubicacion.id,
                                            )}
                                            onCheckedChange={(checked) =>
                                                updateLocationSelection(
                                                    ubicacion.id,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <span className="min-w-0">
                                            <span className="block text-sm font-medium">
                                                {ubicacion.nombre_completo}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {ubicacion.codigo}
                                            </span>
                                        </span>
                                    </label>
                                ))}
                            </div>
                            {turnoForm.errors.ubicacion_ids && (
                                <p className="text-sm text-destructive">
                                    {turnoForm.errors.ubicacion_ids}
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowDialog(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={submitTurno}
                            disabled={turnoForm.processing}
                        >
                            {editingTurno ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

TurnosIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
