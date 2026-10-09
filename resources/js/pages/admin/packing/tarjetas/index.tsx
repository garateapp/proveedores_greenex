import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { AppLayout } from '@/layouts/app';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Clock,
    Download,
    Plus,
    Search,
    ShieldCheck,
    SquareArrowOutUpRight,
    UserRoundX,
} from 'lucide-react';
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

interface AdministradorOption {
    id: number;
    nombre: string;
    centro_costo: string | null;
}

interface TarjetaQrItem {
    id: number;
    numero_serie: string;
    codigo_qr: string;
    estado: string;
    perfil: 'COMENSAL' | 'ADMIN_CASINO';
    administrador: {
        id: number;
        nombre: string;
        centro_costo: string | null;
    } | null;
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
    administradores: AdministradorOption[];
    filters: {
        search?: string;
        estado?: string | null;
        perfil?: string | null;
    };
    estados: EstadoOption[];
    perfiles: EstadoOption[];
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

export default function PackingTarjetasIndex({
    tarjetas,
    trabajadores,
    administradores,
    filters,
    estados,
    perfiles,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado ?? 'all');
    const [perfil, setPerfil] = useState(filters.perfil ?? 'all');
    const [showAdminDialog, setShowAdminDialog] = useState(false);
    const exportQuery = new URLSearchParams();

    if (search) {
        exportQuery.set('search', search);
    }

    if (estado !== 'all') {
        exportQuery.set('estado', estado);
    }

    if (perfil !== 'all') {
        exportQuery.set('perfil', perfil);
    }

    const exportHref = `/admin/packing/tarjetas/export${exportQuery.toString() ? `?${exportQuery.toString()}` : ''}`;

    /**
     * Solo las tarjetas comensales pueden pasar a un trabajador: un QR de
     * administrador emite vales de lote y jamás debe quedar asignado a una
     * persona. El backend lo vuelve a validar igual.
     */
    const tarjetasAsignables = tarjetas.filter(
        (tarjeta) =>
            tarjeta.perfil === 'COMENSAL' &&
            !['bloqueada', 'baja'].includes(tarjeta.estado),
    );

    const createForm = useForm({
        numero_serie: '',
        codigo_qr: '',
        estado: 'disponible',
        observaciones: '',
        multiticket: '0',
    });

    const assignForm = useForm({
        tarjeta_id: tarjetasAsignables[0]?.id.toString() ?? '',
        trabajador_id: trabajadores[0]?.id ?? '',
        asignada_en: new Date().toISOString().slice(0, 16),
        observaciones: '',
    });

    const adminForm = useForm({
        tarjeta_id: '',
        admin_user_id: '',
        observaciones: '',
    });

    const handleSearch = () => {
        router.get(
            '/admin/packing/tarjetas',
            {
                search: search || undefined,
                estado: estado === 'all' ? undefined : estado,
                perfil: perfil === 'all' ? undefined : perfil,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const submitCreate = () => {
        createForm.post('/admin/packing/tarjetas', {
            preserveScroll: true,
            onSuccess: () =>
                createForm.reset('numero_serie', 'codigo_qr', 'observaciones', 'multiticket'),
        });
    };

    const submitAssignment = () => {
        if (!assignForm.data.tarjeta_id) {
            return;
        }

        assignForm.post(
            `/admin/packing/tarjetas/${assignForm.data.tarjeta_id}/asignaciones`,
            {
                preserveScroll: true,
                onSuccess: () => assignForm.reset('observaciones'),
            },
        );
    };

    const tarjetasConvertibles = tarjetas.filter(
        (tarjeta) => tarjeta.estado !== 'bloqueada' && tarjeta.estado !== 'baja',
    );

    const openAdminDialog = (tarjetaId: string) => {
        adminForm.setData({ tarjeta_id: tarjetaId, admin_user_id: '', observaciones: '' });
        adminForm.clearErrors();
        setShowAdminDialog(true);
    };

    const submitAdmin = () => {
        if (!adminForm.data.tarjeta_id || !adminForm.data.admin_user_id) {
            return;
        }

        adminForm.post(
            `/admin/packing/tarjetas/${adminForm.data.tarjeta_id}/administrador`,
            {
                preserveScroll: true,
                onSuccess: () => setShowAdminDialog(false),
            },
        );
    };

    const revokeAdmin = (tarjeta: TarjetaQrItem) => {
        if (
            !confirm(
                `¿Devolver la tarjeta ${tarjeta.numero_serie} al perfil de comensal? Dejará de emitir vales de lote.`,
            )
        ) {
            return;
        }

        router.delete(`/admin/packing/tarjetas/${tarjeta.id}/administrador`, {
            preserveScroll: true,
        });
    };

    const unassignCard = (tarjeta: TarjetaQrItem) => {
        if (
            !confirm(
                `¿Desasignar la tarjeta ${tarjeta.numero_serie}? Quedará en estado disponible.`,
            )
        ) {
            return;
        }

        router.delete(`/admin/packing/tarjetas/${tarjeta.id}/asignaciones`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Packing QR" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Packing QR
                        </h1>
                        <p className="text-muted-foreground">
                            Administre tarjetas QR reutilizables y sus
                            asignaciones al personal.
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
                                Registre una tarjeta física con su número de
                                serie y contenido QR.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="numero_serie">
                                        Número de serie
                                    </Label>
                                    <Input
                                        id="numero_serie"
                                        value={createForm.data.numero_serie}
                                        onChange={(event) =>
                                            createForm.setData(
                                                'numero_serie',
                                                event.target.value,
                                            )
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
                                            createForm.setData(
                                                'codigo_qr',
                                                event.target.value,
                                            )
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
                                    <Label htmlFor="estado">
                                        Estado inicial
                                    </Label>
                                    <Select
                                        value={createForm.data.estado}
                                        onValueChange={(value) =>
                                            createForm.setData('estado', value)
                                        }
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
                                    <Label htmlFor="multiticket">
                                        Multiticket
                                    </Label>
                                    <Select
                                        value={createForm.data.multiticket}
                                        onValueChange={(value) =>
                                            createForm.setData('multiticket', value)
                                        }
                                    >
                                        <SelectTrigger id="multiticket">
                                            <SelectValue  />
                                        </SelectTrigger>
                                        <SelectContent>

                                                <SelectItem
                                                    key="1"
                                                    value="1"
                                                >
                                                    SI
                                                </SelectItem>
                                                <SelectItem
                                                    key="0"
                                                    value="0"
                                                >
                                                    NO
                                                </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="observaciones">
                                        Observaciones
                                    </Label>
                                    <Input
                                        id="observaciones"
                                        value={createForm.data.observaciones}
                                        onChange={(event) =>
                                            createForm.setData(
                                                'observaciones',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Uso interno"
                                    />
                                </div>
                            </div>

                            <Button
                                onClick={submitCreate}
                                disabled={createForm.processing}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Crear tarjeta
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Asignar tarjeta</CardTitle>
                            <CardDescription>
                                La reasignación cierra automáticamente la
                                asignación activa anterior.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="tarjeta_id">Tarjeta</Label>
                                <Combobox
                                    options={tarjetasAsignables.map((tarjeta) => ({
                                        value: tarjeta.id.toString(),
                                        label: `${tarjeta.numero_serie} · ${tarjeta.estado}`,
                                        searchValue: `${tarjeta.numero_serie} ${tarjeta.estado}`,
                                    }))}
                                    value={assignForm.data.tarjeta_id}
                                    onValueChange={(value) =>
                                        assignForm.setData('tarjeta_id', value)
                                    }
                                    placeholder="Seleccione una tarjeta"
                                    searchPlaceholder="Buscar tarjeta por número de serie..."
                                    emptyMessage="No hay tarjetas comensales disponibles para asignar."
                                />
                            </div>

                            {assignForm.errors.tarjeta_id && (
                                <p className="text-sm text-destructive">
                                    {assignForm.errors.tarjeta_id}
                                </p>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="trabajador_id">
                                    Trabajador
                                </Label>
                                <Combobox
                                    options={trabajadores.map((trabajador) => ({
                                        value: trabajador.id,
                                        label: `${trabajador.nombre_completo} · ${trabajador.documento}`,
                                        searchValue: `${trabajador.nombre_completo} ${trabajador.documento}`,
                                    }))}
                                    value={assignForm.data.trabajador_id}
                                    onValueChange={(value) =>
                                        assignForm.setData(
                                            'trabajador_id',
                                            value,
                                        )
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
                                    <Label htmlFor="asignada_en">
                                        Fecha de asignación
                                    </Label>
                                    <Input
                                        id="asignada_en"
                                        type="datetime-local"
                                        value={assignForm.data.asignada_en}
                                        onChange={(event) =>
                                            assignForm.setData(
                                                'asignada_en',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="assign_observaciones">
                                        Observaciones
                                    </Label>
                                    <Input
                                        id="assign_observaciones"
                                        value={assignForm.data.observaciones}
                                        onChange={(event) =>
                                            assignForm.setData(
                                                'observaciones',
                                                event.target.value,
                                            )
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
                        <CardTitle>QR de administrador</CardTitle>
                        <CardDescription>
                            Convierte una tarjeta en el código que permite emitir
                            vales de lote desde la app. Cierra la asignación de
                            trabajador si tenía una.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="admin_tarjeta_id">Tarjeta</Label>
                            <Combobox
                                options={tarjetasConvertibles.map((tarjeta) => ({
                                    value: tarjeta.id.toString(),
                                    label: `${tarjeta.numero_serie} · ${
                                        tarjeta.perfil === 'ADMIN_CASINO'
                                            ? 'Ya es de administrador'
                                            : tarjeta.estado
                                    }`,
                                    searchValue: tarjeta.numero_serie,
                                }))}
                                    value={adminForm.data.tarjeta_id}
                                    onValueChange={(value) =>
                                        adminForm.setData('tarjeta_id', value)
                                    }
                                placeholder="Seleccione una tarjeta"
                                searchPlaceholder="Buscar tarjeta por número de serie..."
                                emptyMessage="No se encontraron tarjetas."
                            />
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="admin_user_id">
                                    Administrador
                                </Label>
                                <Select
                                    value={adminForm.data.admin_user_id}
                                    onValueChange={(value) =>
                                        adminForm.setData('admin_user_id', value)
                                    }
                                >
                                    <SelectTrigger id="admin_user_id">
                                        <SelectValue placeholder="Seleccione un administrador" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {administradores.map((admin) => (
                                            <SelectItem
                                                key={admin.id}
                                                value={admin.id.toString()}
                                            >
                                                {admin.nombre}
                                                {admin.centro_costo
                                                    ? ` · ${admin.centro_costo}`
                                                    : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {adminForm.errors.admin_user_id && (
                                    <p className="text-sm text-destructive">
                                        {adminForm.errors.admin_user_id}
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="admin_observaciones">
                                    Observaciones
                                </Label>
                                <Input
                                    id="admin_observaciones"
                                    value={adminForm.data.observaciones}
                                    onChange={(event) =>
                                        adminForm.setData(
                                            'observaciones',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Uso interno"
                                />
                            </div>
                        </div>

                        <Button
                            onClick={submitAdmin}
                            disabled={
                                adminForm.processing ||
                                !adminForm.data.tarjeta_id ||
                                !adminForm.data.admin_user_id
                            }
                        >
                            <ShieldCheck className="mr-2 h-4 w-4" />
                            Asignar como administrador
                        </Button>

                        {adminForm.errors.tarjeta_id && (
                            <p className="text-sm text-destructive">
                                {adminForm.errors.tarjeta_id}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Inventario</CardTitle>
                        <CardDescription>
                            Consulte el estado actual y la última asignación
                            activa de cada tarjeta.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-[1fr,180px,180px,140px] xl:grid-cols-[1fr,180px,180px,140px,220px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Buscar</Label>
                                <Input
                                    id="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    onKeyDown={(event) =>
                                        event.key === 'Enter' && handleSearch()
                                    }
                                    placeholder="Serie o código QR"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="estado_filter">Estado</Label>
                                <Select
                                    value={estado}
                                    onValueChange={setEstado}
                                >
                                    <SelectTrigger id="estado_filter">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
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
                                <Label htmlFor="perfil_filter">Perfil</Label>
                                <Select
                                    value={perfil}
                                    onValueChange={setPerfil}
                                >
                                    <SelectTrigger id="perfil_filter">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        {perfiles.map((perfilOption) => (
                                            <SelectItem
                                                key={perfilOption.value}
                                                value={perfilOption.value}
                                            >
                                                {perfilOption.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button
                                    className="w-full"
                                    onClick={handleSearch}
                                >
                                    <Search className="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                            </div>
                            <div className="flex items-end">
                                <Button
                                    asChild
                                    variant="outline"
                                    className="w-full"
                                >
                                    <a href={exportHref}>
                                        <Download className="mr-2 h-4 w-4" />
                                        Exportar CSV
                                    </a>
                                </Button>
                            </div>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Serie</TableHead>
                                    <TableHead>Código QR</TableHead>
                                    <TableHead>Perfil</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Asignación activa</TableHead>
                                    <TableHead>Observaciones</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tarjetas.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="text-center"
                                        >
                                            No hay tarjetas registradas.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    tarjetas.map((tarjeta) => (
                                        <TableRow key={tarjeta.id}>
                                            <TableCell className="font-medium">
                                                {tarjeta.numero_serie}
                                            </TableCell>
                                            <TableCell>
                                                {tarjeta.codigo_qr}
                                            </TableCell>
                                            <TableCell>
                                                {tarjeta.perfil ===
                                                'ADMIN_CASINO' ? (
                                                    <Badge variant="default">
                                                        Administrador
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Comensal
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={estadoVariant(
                                                        tarjeta.estado,
                                                    )}
                                                >
                                                    {tarjeta.estado}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {tarjeta.perfil ===
                                                'ADMIN_CASINO' ? (
                                                    <div className="flex flex-col text-sm">
                                                        <span className="font-medium">
                                                            {
                                                                tarjeta
                                                                    .administrador
                                                                    ?.nombre ??
                                                                'Sin administrador'
                                                            }
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {tarjeta
                                                                .administrador
                                                                ?.centro_costo ??
                                                                'sin centro de costo'}
                                                        </span>
                                                    </div>
                                                ) : tarjeta.trabajador_actual ? (
                                                    <div className="flex flex-col text-sm">
                                                        <span className="font-medium">
                                                            {
                                                                tarjeta
                                                                    .trabajador_actual
                                                                    .nombre_completo
                                                            }
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {tarjeta
                                                                .trabajador_actual
                                                                .contratista ??
                                                                '-'}
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {
                                                                tarjeta
                                                                    .trabajador_actual
                                                                    .asignada_en
                                                            }
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Sin asignación activa
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {tarjeta.observaciones || '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {tarjeta.perfil ===
                                                    'ADMIN_CASINO' && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        title="Devolver al perfil de comensal"
                                                        onClick={() =>
                                                            revokeAdmin(tarjeta)
                                                        }
                                                    >
                                                        <UserRoundX className="h-4 w-4" />
                                                    </Button>
                                                )}
                                                {tarjeta.perfil ===
                                                    'COMENSAL' &&
                                                    tarjeta.trabajador_actual && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            title="Desasignar tarjeta"
                                                            onClick={() =>
                                                                unassignCard(
                                                                    tarjeta,
                                                                )
                                                            }
                                                        >
                                                            <UserRoundX className="h-4 w-4" />
                                                        </Button>
                                                    )}
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

PackingTarjetasIndex.layout = (page: React.ReactNode) => (
    <AppLayout>{page}</AppLayout>
);
