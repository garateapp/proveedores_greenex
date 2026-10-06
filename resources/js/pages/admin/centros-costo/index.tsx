import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface CentroCostoItem {
    id: number;
    codigo: string;
    nombre: string;
    activo: boolean;
    trabajadores_count: number;
    vales_almuerzo_count: number;
    vales_lote_count: number;
}

interface Props {
    centros: {
        data: CentroCostoItem[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        estado?: string | null;
    };
}

export default function CentrosCostoIndex({ centros, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado ?? 'all');
    const [showDialog, setShowDialog] = useState(false);
    const [editingCentro, setEditingCentro] = useState<CentroCostoItem | null>(
        null,
    );

    const form = useForm({
        codigo: '',
        nombre: '',
        activo: true,
    });

    const handleSearch = () => {
        router.get(
            '/admin/centros-costo',
            {
                search: search || undefined,
                estado: estado === 'all' ? undefined : estado,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const openCreateDialog = () => {
        setEditingCentro(null);
        form.setData({ codigo: '', nombre: '', activo: true });
        form.clearErrors();
        setShowDialog(true);
    };

    const openEditDialog = (centro: CentroCostoItem) => {
        setEditingCentro(centro);
        form.setData({
            codigo: centro.codigo,
            nombre: centro.nombre,
            activo: centro.activo,
        });
        form.clearErrors();
        setShowDialog(true);
    };

    const submit = () => {
        if (editingCentro) {
            form.put(`/admin/centros-costo/${editingCentro.id}`, {
                preserveScroll: true,
                onSuccess: () => setShowDialog(false),
            });

            return;
        }

        form.post('/admin/centros-costo', {
            preserveScroll: true,
            onSuccess: () => setShowDialog(false),
        });
    };

    const destroy = (centro: CentroCostoItem) => {
        const consecuencias: string[] = [];

        if (centro.trabajadores_count > 0) {
            consecuencias.push(
                `${centro.trabajadores_count} trabajador(es) quedarán sin centro de costo`,
            );
        }

        if (centro.vales_almuerzo_count + centro.vales_lote_count > 0) {
            consecuencias.push(
                'los vales ya emitidos conservarán el centro de costo con el que se emitieron',
            );
        }

        const detalle =
            consecuencias.length > 0 ? ` ${consecuencias.join(' y ')}.` : '';

        if (!confirm(`¿Eliminar el centro de costo "${centro.codigo}"?${detalle}`)) {
            return;
        }

        router.delete(`/admin/centros-costo/${centro.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Centros de Costo" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Centros de Costo
                        </h1>
                        <p className="text-muted-foreground">
                            Administre los centros de costo que se imprimen en
                            los vales de almuerzo y lote.
                        </p>
                    </div>
                    <Button onClick={openCreateDialog}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nuevo Centro de Costo
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Busque por código o nombre del centro de costo
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,220px,140px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Búsqueda</Label>
                                <Input
                                    id="search"
                                    placeholder="Ej: CC-1042,Bodega central, etc."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' && handleSearch()
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="estado">Estado</Label>
                                <Select
                                    value={estado}
                                    onValueChange={setEstado}
                                >
                                    <SelectTrigger id="estado">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        <SelectItem value="activo">
                                            Activos
                                        </SelectItem>
                                        <SelectItem value="inactivo">
                                            Inactivos
                                        </SelectItem>
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
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">
                                        Trabajadores
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Vale almuerzo
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Vale lote
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {centros.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No se encontraron centros de costo.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    centros.data.map((centro) => (
                                        <TableRow key={centro.id}>
                                            <TableCell className="font-mono">
                                                {centro.codigo}
                                            </TableCell>
                                            <TableCell>
                                                {centro.nombre}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        centro.activo
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {centro.activo
                                                        ? 'Activo'
                                                        : 'Inactivo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {centro.trabajadores_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {centro.vales_almuerzo_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {centro.vales_lote_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Editar"
                                                        onClick={() =>
                                                            openEditDialog(
                                                                centro,
                                                            )
                                                        }
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Eliminar"
                                                        className="text-destructive"
                                                        onClick={() =>
                                                            destroy(centro)
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4" />
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

                {centros.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {centros.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={
                                    link.active ? 'default' : 'outline'
                                }
                                size="sm"
                                disabled={!link.url}
                                onClick={() =>
                                    link.url && router.visit(link.url)
                                }
                                dangerouslySetInnerHTML={{
                                    __html: link.label,
                                }}
                            />
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={showDialog} onOpenChange={setShowDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingCentro
                                ? 'Editar Centro de Costo'
                                : 'Nuevo Centro de Costo'}
                        </DialogTitle>
                        <DialogDescription>
                            El código es el que se imprime en el vale y debe ser
                            corto y legible.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="centro-codigo">Código</Label>
                            <Input
                                id="centro-codigo"
                                value={form.data.codigo}
                                onChange={(e) =>
                                    form.setData('codigo', e.target.value)
                                }
                                placeholder="CC-1042"
                            />
                            {form.errors.codigo && (
                                <p className="text-sm text-destructive">
                                    {form.errors.codigo}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="centro-nombre">Nombre</Label>
                            <Input
                                id="centro-nombre"
                                value={form.data.nombre}
                                onChange={(e) =>
                                    form.setData('nombre', e.target.value)
                                }
                                placeholder="Bodega central"
                            />
                            {form.errors.nombre && (
                                <p className="text-sm text-destructive">
                                    {form.errors.nombre}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="centro-activo">Estado</Label>
                            <Select
                                value={form.data.activo ? 'activo' : 'inactivo'}
                                onValueChange={(value) =>
                                    form.setData('activo', value === 'activo')
                                }
                            >
                                <SelectTrigger id="centro-activo">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="activo">
                                        Activo
                                    </SelectItem>
                                    <SelectItem value="inactivo">
                                        Inactivo
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            {form.errors.activo && (
                                <p className="text-sm text-destructive">
                                    {form.errors.activo}
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setShowDialog(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            onClick={submit}
                            disabled={form.processing}
                        >
                            {editingCentro ? 'Guardar cambios' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CentrosCostoIndex.layout = (page: React.ReactNode) => (
    <AppLayout>{page}</AppLayout>
);
