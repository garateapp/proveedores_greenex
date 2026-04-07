import { AppLayout } from '@/layouts/app';
import { Head, router, useForm } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Combobox } from '@/components/ui/combobox';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Plus, Search, Edit, Trash2, Power, PowerOff, ChevronRight } from 'lucide-react';
import { useState } from 'react';

interface UbicacionHijo {
    id: number;
    nombre: string;
    codigo: string;
    orden: number;
    activa: boolean;
}

interface UbicacionItem {
    id: number;
    padre_id: number | null;
    padre: {
        id: number;
        nombre: string;
    } | null;
    nombre: string;
    codigo: string;
    descripcion: string | null;
    tipo: 'principal' | 'secundaria';
    orden: number;
    activa: boolean;
    hijos_count: number;
    hijos_activos: UbicacionHijo[];
}

interface Props {
    ubicaciones: UbicacionItem[];
    ubicacionesPrincipales: UbicacionItem[];
    filters: {
        search?: string;
        tipo?: string;
        activa?: string;
    };
}

export default function UbicacionesIndex({ ubicaciones, ubicacionesPrincipales, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [showDialog, setShowDialog] = useState(false);
    const [editingUbicacion, setEditingUbicacion] = useState<UbicacionItem | null>(null);

    const form = useForm({
        padre_id: '',
        nombre: '',
        codigo: '',
        descripcion: '',
        tipo: 'secundaria',
        orden: '0',
    });

    const handleSearch = () => {
        router.get(
            '/admin/ubicaciones',
            {
                search: search || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const openCreateDialog = () => {
        setEditingUbicacion(null);
        form.reset();
        form.setData('tipo', 'secundaria');
        setShowDialog(true);
    };

    const openEditDialog = (ubicacion: UbicacionItem) => {
        setEditingUbicacion(ubicacion);
        form.setData({
            padre_id: ubicacion.padre_id?.toString() ?? '',
            nombre: ubicacion.nombre,
            codigo: ubicacion.codigo,
            descripcion: ubicacion.descripcion ?? '',
            tipo: ubicacion.tipo,
            orden: ubicacion.orden.toString(),
        });
        setShowDialog(true);
    };

    const handleSubmit = () => {
        if (editingUbicacion) {
            form.put(`/admin/ubicaciones/${editingUbicacion.id}`, {
                preserveScroll: true,
                onSuccess: () => setShowDialog(false),
            });
        } else {
            form.post('/admin/ubicaciones', {
                preserveScroll: true,
                onSuccess: () => setShowDialog(false),
            });
        }
    };

    const toggleActiva = (ubicacion: UbicacionItem) => {
        router.post(
            `/admin/ubicaciones/${ubicacion.id}/toggle-activa`,
            {},
            { preserveScroll: true },
        );
    };

    const handleDelete = (ubicacion: UbicacionItem) => {
        if (ubicacion.hijos_count > 0) {
            alert('No se puede eliminar una ubicación que tiene sub-ubicaciones.');
            return;
        }

        if (!confirm('¿Está seguro de eliminar esta ubicación?')) {
            return;
        }

        router.delete(`/admin/ubicaciones/${ubicacion.id}`, {
            preserveScroll: true,
        });
    };

    const filteredPrincipales = ubicaciones.filter((u) => u.tipo === 'principal');
    const filteredSecundarias = ubicaciones.filter((u) => u.tipo === 'secundaria');

    return (
        <>
            <Head title="Ubicaciones" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Ubicaciones</h1>
                        <p className="text-muted-foreground">
                            Gestione las estructura de ubicaciones dentro de la planta.
                        </p>
                    </div>
                    <Button onClick={openCreateDialog}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nueva ubicación
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtrar ubicaciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,140px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Buscar</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="search"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        onKeyDown={(event) => event.key === 'Enter' && handleSearch()}
                                        placeholder="Nombre, código o descripción"
                                    />
                                    <Button onClick={handleSearch}>
                                        <Search className="mr-2 h-4 w-4" />
                                        Buscar
                                    </Button>
                                </div>
                            </div>
                            <div className="flex items-end">
                                <Button
                                    variant="outline"
                                    className="w-full"
                                    onClick={() => {
                                        setSearch('');
                                        router.get('/admin/ubicaciones');
                                    }}
                                >
                                    Limpiar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Ubicaciones Principales</CardTitle>
                        <CardDescription>
                            Áreas principales de la planta (ej: UNITEC 1, UNITEC 2)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead>Sub-ubicaciones</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Orden</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredPrincipales.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center">
                                            No hay ubicaciones principales registradas.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    filteredPrincipales.map((ubicacion) => (
                                        <UbicacionRow
                                            key={ubicacion.id}
                                            ubicacion={ubicacion}
                                            onEdit={openEditDialog}
                                            onDelete={handleDelete}
                                            onToggle={toggleActiva}
                                        />
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Sub-ubicaciones</CardTitle>
                        <CardDescription>
                            Ubicaciones secundarias dentro de áreas principales
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Ubicación Principal</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Orden</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredSecundarias.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center">
                                            No hay sub-ubicaciones registradas.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    filteredSecundarias.map((ubicacion) => (
                                        <UbicacionRow
                                            key={ubicacion.id}
                                            ubicacion={ubicacion}
                                            onEdit={openEditDialog}
                                            onDelete={handleDelete}
                                            onToggle={toggleActiva}
                                        />
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={showDialog} onOpenChange={setShowDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingUbicacion ? 'Editar ubicación' : 'Nueva ubicación'}
                        </DialogTitle>
                        <DialogDescription>
                            {editingUbicacion
                                ? 'Actualice los datos de la ubicación.'
                                : 'Complete los datos para crear una nueva ubicación.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="tipo">Tipo de ubicación</Label>
                            <select
                                id="tipo"
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                value={form.data.tipo}
                                onChange={(e) => {
                                    form.setData('tipo', e.target.value);
                                    if (e.target.value === 'principal') {
                                        form.setData('padre_id', '');
                                    }
                                }}
                            >
                                <option value="principal">Principal</option>
                                <option value="secundaria">Secundaria</option>
                            </select>
                            {form.errors.tipo && (
                                <p className="text-sm text-destructive">{form.errors.tipo}</p>
                            )}
                        </div>

                        {form.data.tipo === 'secundaria' && (
                            <div className="space-y-2">
                                <Label htmlFor="padre_id">Ubicación principal</Label>
                                <Combobox
                                    options={ubicacionesPrincipales.map((u) => ({
                                        value: u.id.toString(),
                                        label: u.nombre,
                                    }))}
                                    value={form.data.padre_id}
                                    onValueChange={(value) => form.setData('padre_id', value)}
                                    placeholder="Seleccione una ubicación principal"
                                    searchPlaceholder="Buscar ubicación..."
                                    emptyMessage="No se encontraron ubicaciones principales."
                                />
                                {form.errors.padre_id && (
                                    <p className="text-sm text-destructive">{form.errors.padre_id}</p>
                                )}
                            </div>
                        )}

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="nombre">Nombre</Label>
                                <Input
                                    id="nombre"
                                    value={form.data.nombre}
                                    onChange={(e) => form.setData('nombre', e.target.value)}
                                    placeholder="Filtro, Altillo, etc."
                                />
                                {form.errors.nombre && (
                                    <p className="text-sm text-destructive">{form.errors.nombre}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="codigo">Código</Label>
                                <Input
                                    id="codigo"
                                    value={form.data.codigo}
                                    onChange={(e) => form.setData('codigo', e.target.value)}
                                    placeholder="UNITEC1-FILTRO"
                                />
                                {form.errors.codigo && (
                                    <p className="text-sm text-destructive">{form.errors.codigo}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="descripcion">Descripción</Label>
                            <Input
                                id="descripcion"
                                value={form.data.descripcion}
                                onChange={(e) => form.setData('descripcion', e.target.value)}
                                placeholder="Descripción opcional"
                            />
                            {form.errors.descripcion && (
                                <p className="text-sm text-destructive">{form.errors.descripcion}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="orden">Orden</Label>
                            <Input
                                id="orden"
                                type="number"
                                min="0"
                                value={form.data.orden}
                                onChange={(e) => form.setData('orden', e.target.value)}
                                placeholder="0"
                            />
                            {form.errors.orden && (
                                <p className="text-sm text-destructive">{form.errors.orden}</p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowDialog(false)}>
                            Cancelar
                        </Button>
                        <Button onClick={handleSubmit} disabled={form.processing}>
                            {editingUbicacion ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function UbicacionRow({
    ubicacion,
    onEdit,
    onDelete,
    onToggle,
}: {
    ubicacion: UbicacionItem;
    onEdit: (ubicacion: UbicacionItem) => void;
    onDelete: (ubicacion: UbicacionItem) => void;
    onToggle: (ubicacion: UbicacionItem) => void;
}) {
    return (
        <TableRow>
            <TableCell className="font-medium">{ubicacion.codigo}</TableCell>
            <TableCell>
                <div className="flex items-center gap-2">
                    {ubicacion.tipo === 'secundaria' && (
                        <ChevronRight className="h-4 w-4 text-muted-foreground" />
                    )}
                    {ubicacion.nombre}
                </div>
            </TableCell>
            {ubicacion.tipo === 'secundaria' ? (
                <TableCell>{ubicacion.padre?.nombre ?? '-'}</TableCell>
            ) : (
                <TableCell>-</TableCell>
            )}
            {ubicacion.tipo === 'principal' ? (
                <TableCell>
                    <div className="flex flex-col gap-1 text-sm">
                        {ubicacion.hijos_activos.length > 0 ? (
                            ubicacion.hijos_activos.map((hijo) => (
                                <Badge key={hijo.id} variant="outline">
                                    {hijo.nombre}
                                </Badge>
                            ))
                        ) : (
                            <span className="text-muted-foreground">Sin sub-ubicaciones</span>
                        )}
                    </div>
                </TableCell>
            ) : (
                <TableCell>{ubicacion.descripcion || '-'}</TableCell>
            )}
            <TableCell>
                <Badge variant={ubicacion.activa ? 'default' : 'secondary'}>
                    {ubicacion.activa ? 'Activa' : 'Inactiva'}
                </Badge>
            </TableCell>
            <TableCell>{ubicacion.orden}</TableCell>
            <TableCell className="text-right">
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onToggle(ubicacion)}
                        title={ubicacion.activa ? 'Desactivar' : 'Activar'}
                    >
                        {ubicacion.activa ? (
                            <PowerOff className="h-4 w-4" />
                        ) : (
                            <Power className="h-4 w-4" />
                        )}
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onEdit(ubicacion)}
                        title="Editar"
                    >
                        <Edit className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onDelete(ubicacion)}
                        title="Eliminar"
                    >
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            </TableCell>
        </TableRow>
    );
}

UbicacionesIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
