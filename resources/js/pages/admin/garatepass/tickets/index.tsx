import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';

interface ContratistaTotal {
    contratista: string;
    total: number;
}

interface CentroCostoTotal {
    centro_costo_id: number | null;
    codigo: string | null;
    nombre: string | null;
    total: number;
}

interface Props {
    filters: {
        desde: string;
        hasta: string;
        contratista: string | null;
        centro_costo_id: number | null;
    };
    total: number;
    totalsByContratista: ContratistaTotal[];
    contratistas: string[];
    detalleRut: { rut: string; contratista: string } | null;
    detalleCentroCosto: {
        total: number;
        rows: CentroCostoTotal[];
    } | null;
    centrosCosto: { value: string; label: string }[];
}

export default function TicketsEmitidosIndex({
    filters,
    total,
    totalsByContratista,
    contratistas,
    detalleRut,
    detalleCentroCosto,
    centrosCosto,
}: Props) {
    const [desde, setDesde] = useState(filters.desde);
    const [hasta, setHasta] = useState(filters.hasta);
    const [contratista, setContratista] = useState(
        filters.contratista ?? 'all',
    );
    const [centroCostoId, setCentroCostoId] = useState(
        filters.centro_costo_id ? String(filters.centro_costo_id) : 'all',
    );

    const handleSearch = (overrides?: {
        contratista?: string;
        centroCostoId?: string;
    }) => {
        const nextContratista = overrides?.contratista ?? contratista;
        const nextCentroCosto = overrides?.centroCostoId ?? centroCostoId;

        router.get(
            '/admin/garatepass/tickets',
            {
                desde: desde || undefined,
                hasta: hasta || undefined,
                contratista:
                    nextContratista === 'all' ? undefined : nextContratista,
                centro_costo_id:
                    nextCentroCosto === 'all'
                        ? undefined
                        : Number(nextCentroCosto),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleContratistaChange = (value: string) => {
        setContratista(value);
        setCentroCostoId('all');
        handleSearch({ contratista: value, centroCostoId: 'all' });
    };

    const handleCentroCostoChange = (value: string) => {
        setCentroCostoId(value);
        handleSearch({ centroCostoId: value });
    };

    return (
        <>
            <Head title="Tickets Emitidos" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Tickets Emitidos
                    </h1>
                    <p className="text-muted-foreground">
                        Cantidad de tickets de almuerzo emitidos en el rango,
                        desglosados por contratista y, cuando corresponde, por
                        centro de costo.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Tickets emitidos</CardDescription>
                            <CardTitle className="text-3xl">{total}</CardTitle>
                        </CardHeader>
                    </Card>
                    {detalleRut && (
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>
                                    Contratista con detalle por centro de costo
                                </CardDescription>
                                <CardTitle className="text-lg">
                                    {detalleRut.contratista}
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    {detalleRut.rut}
                                </p>
                            </CardHeader>
                        </Card>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Filtre por rango de fechas y contratista. Para el
                            contratista configurado puede además filtrar por
                            centro de costo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-4">
                            <div className="space-y-2">
                                <Label htmlFor="desde">Desde</Label>
                                <Input
                                    id="desde"
                                    type="date"
                                    value={desde}
                                    onChange={(e) => setDesde(e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="hasta">Hasta</Label>
                                <Input
                                    id="hasta"
                                    type="date"
                                    value={hasta}
                                    onChange={(e) => setHasta(e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="contratista">Contratista</Label>
                                <Select
                                    value={contratista}
                                    onValueChange={handleContratistaChange}
                                >
                                    <SelectTrigger id="contratista">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        {contratistas.map((item) => (
                                            <SelectItem key={item} value={item}>
                                                {item}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {detalleCentroCosto && centrosCosto.length > 0 && (
                                <div className="space-y-2">
                                    <Label htmlFor="centro_costo">
                                        Centro de costo
                                    </Label>
                                    <Select
                                        value={centroCostoId}
                                        onValueChange={handleCentroCostoChange}
                                    >
                                        <SelectTrigger id="centro_costo">
                                            <SelectValue placeholder="Todos" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                Todos
                                            </SelectItem>
                                            {centrosCosto.map((item) => (
                                                <SelectItem
                                                    key={item.value}
                                                    value={item.value}
                                                >
                                                    {item.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div className="flex items-end md:col-start-4">
                                <Button
                                    className="w-full"
                                    onClick={() => handleSearch()}
                                >
                                    <Search className="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Tickets por contratista</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Contratista</TableHead>
                                    <TableHead className="text-right">
                                        Tickets
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {totalsByContratista.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={2}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Sin tickets emitidos con los filtros
                                            aplicados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    totalsByContratista.map((item) => (
                                        <TableRow key={item.contratista}>
                                            <TableCell>
                                                {item.contratista}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Badge variant="secondary">
                                                    {item.total}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {detalleCentroCosto && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Detalle por centro de costo
                                {detalleRut
                                    ? ` — ${detalleRut.contratista}`
                                    : ''}
                            </CardTitle>
                            <CardDescription>
                                {detalleCentroCosto.total} tickets en el detalle
                                seleccionado
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Centro de costo</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead className="text-right">
                                            Tickets
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {detalleCentroCosto.rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="py-8 text-center text-muted-foreground"
                                            >
                                                Sin tickets para el contratista
                                                en el rango.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        detalleCentroCosto.rows.map((row) => (
                                            <TableRow
                                                key={
                                                    row.centro_costo_id ??
                                                    'sin-centro'
                                                }
                                            >
                                                <TableCell className="font-mono">
                                                    {row.codigo ??
                                                        '(sin centro de costo)'}
                                                </TableCell>
                                                <TableCell>
                                                    {row.nombre ?? '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Badge>{row.total}</Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

TicketsEmitidosIndex.layout = (page: React.ReactNode) => (
    <AppLayout>{page}</AppLayout>
);
