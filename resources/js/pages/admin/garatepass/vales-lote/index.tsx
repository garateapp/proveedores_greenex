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

interface ValeLoteItem {
    id: number;
    codigo: string;
    lote_id: string;
    posicion: number;
    administrador: string | null;
    centro_costo: string | null;
    emitido_en: string | null;
    canjeado_en: string | null;
}

interface Resumen {
    total: number;
    canjeados: number;
    vigentes: number;
}

interface Props {
    vales: {
        data: ValeLoteItem[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    resumen: Resumen;
    filters: {
        search?: string;
        estado?: string | null;
        desde?: string | null;
        hasta?: string | null;
    };
}

export default function ValesLoteIndex({
    vales,
    resumen,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado ?? 'all');
    const [desde, setDesde] = useState(filters.desde ?? '');
    const [hasta, setHasta] = useState(filters.hasta ?? '');

    const handleSearch = () => {
        router.get(
            '/admin/garatepass/vales-lote',
            {
                search: search || undefined,
                estado: estado === 'all' ? undefined : estado,
                desde: desde || undefined,
                hasta: hasta || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Vales de Lote" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Vales de Lote
                    </h1>
                    <p className="text-muted-foreground">
                        Consulta de los lotes emitidos desde la app. Los vales se
                        emiten en el casino y se canjean en el punto de servicio.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Emitidos</CardDescription>
                            <CardTitle className="text-3xl">
                                {resumen.total}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Canjeados</CardDescription>
                            <CardTitle className="text-3xl text-green-600">
                                {resumen.canjeados}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Vigentes</CardDescription>
                            <CardTitle className="text-3xl text-amber-600">
                                {resumen.vigentes}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Busque por código, lote o administrador, y filtre por
                            estado o rango de fechas
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,180px,180px,180px,140px]">
                            <div className="space-y-2">
                                <Label htmlFor="search">Búsqueda</Label>
                                <Input
                                    id="search"
                                    placeholder="Ej: LOT-000042, Carlos, etc."
                                    value={search}
                                    onChange={(e) =>
                                        setSearch(e.target.value)
                                    }
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
                                        <SelectItem value="vigente">
                                            Vigentes
                                        </SelectItem>
                                        <SelectItem value="canjeado">
                                            Canjeados
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
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
                                    <TableHead className="text-right">
                                        Posición
                                    </TableHead>
                                    <TableHead>Administrador</TableHead>
                                    <TableHead>Centro de costo</TableHead>
                                    <TableHead>Emitido</TableHead>
                                    <TableHead>Canjeado</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {vales.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No se encontraron vales de lote
                                            con los filtros aplicados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    vales.data.map((vale) => (
                                        <TableRow key={vale.id}>
                                            <TableCell className="font-mono">
                                                {vale.codigo}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {vale.posicion}
                                            </TableCell>
                                            <TableCell>
                                                {vale.administrador ?? '—'}
                                            </TableCell>
                                            <TableCell className="font-mono">
                                                {vale.centro_costo ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {vale.emitido_en ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {vale.canjeado_en ? (
                                                    <Badge variant="secondary">
                                                        {vale.canjeado_en}
                                                    </Badge>
                                                ) : (
                                                    <Badge>Vigente</Badge>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {vales.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {vales.links.map((link, index) => (
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
        </>
    );
}

ValesLoteIndex.layout = (page: React.ReactNode) => (
    <AppLayout>{page}</AppLayout>
);
