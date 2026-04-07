import { AppLayout } from '@/layouts/app';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ArrowLeft, Search } from 'lucide-react';
import { useState } from 'react';

interface MarcacionPackingItem {
    id: number;
    uuid: string;
    trabajador: string | null;
    documento: string | null;
    contratista: string | null;
    numero_serie: string;
    codigo_qr: string;
    marcado_en: string | null;
    device_id: string | null;
    sync_batch_id: string | null;
    ubicacion: string | null;
}

interface Props {
    marcaciones: MarcacionPackingItem[];
    indexUrl: string;
    canManageCards: boolean;
    contratistas: Array<{ id: number; nombre: string }>;
    ubicaciones: Array<{ id: number; nombre: string }>;
    filters: {
        search?: string;
        contratista_id?: string;
        ubicacion_id?: string;
        date_from?: string;
        date_to?: string;
    };
}

export default function PackingMarcacionesIndex({
    marcaciones,
    indexUrl,
    canManageCards,
    contratistas,
    ubicaciones,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [contratistaId, setContratistaId] = useState(filters.contratista_id || 'all');
    const [ubicacionId, setUbicacionId] = useState(filters.ubicacion_id || 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const handleSearch = () => {
        router.get(
            indexUrl,
            {
                search: search || undefined,
                contratista_id:
                    canManageCards && contratistaId !== 'all' ? contratistaId : undefined,
                ubicacion_id: ubicacionId !== 'all' ? ubicacionId : undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setSearch('');
        setContratistaId('all');
        setUbicacionId('all');
        setDateFrom('');
        setDateTo('');

        router.get(indexUrl, {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <>
            <Head title="Marcaciones Packing" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Marcaciones Packing</h1>
                        <p className="text-muted-foreground">
                            Revise las marcaciones históricas sincronizadas desde la app mobile.
                        </p>
                    </div>
                    {canManageCards && (
                        <Link href="/admin/packing/tarjetas">
                            <Button variant="outline">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver a tarjetas
                            </Button>
                        </Link>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Filtre por trabajador, tarjeta, contratista, ubicación y rango de fecha.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="search">Buscar</Label>
                                <Input
                                    id="search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    onKeyDown={(event) => event.key === 'Enter' && handleSearch()}
                                    placeholder="Trabajador, RUT o serie"
                                />
                            </div>
                            {canManageCards && (
                                <div className="space-y-2">
                                    <Label htmlFor="contratista_id">Contratista</Label>
                                    <Select value={contratistaId} onValueChange={setContratistaId}>
                                        <SelectTrigger id="contratista_id">
                                            <SelectValue placeholder="Todos los contratistas" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos los contratistas</SelectItem>
                                            {contratistas.map((contratista) => (
                                                <SelectItem
                                                    key={contratista.id}
                                                    value={contratista.id.toString()}
                                                >
                                                    {contratista.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="ubicacion_id">Ubicación</Label>
                                <Select value={ubicacionId} onValueChange={setUbicacionId}>
                                    <SelectTrigger id="ubicacion_id">
                                        <SelectValue placeholder="Todas las ubicaciones" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las ubicaciones</SelectItem>
                                        {ubicaciones.map((ubicacion) => (
                                            <SelectItem key={ubicacion.id} value={ubicacion.id.toString()}>
                                                {ubicacion.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_from">Desde</Label>
                                <Input
                                    id="date_from"
                                    type="date"
                                    value={dateFrom}
                                    onChange={(event) => setDateFrom(event.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_to">Hasta</Label>
                                <Input
                                    id="date_to"
                                    type="date"
                                    value={dateTo}
                                    onChange={(event) => setDateTo(event.target.value)}
                                />
                            </div>
                            <div className="flex items-end gap-2">
                                <Button className="flex-1" onClick={handleSearch}>
                                    <Search className="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                                <Button variant="outline" onClick={handleClear}>
                                    Limpiar
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
                                    <TableHead>Marcado en</TableHead>
                                    <TableHead>Trabajador</TableHead>
                                    <TableHead>Tarjeta</TableHead>
                                    <TableHead>Contratista</TableHead>
                                    <TableHead>Ubicación</TableHead>
                                    <TableHead>Dispositivo</TableHead>
                                    <TableHead>Lote sync</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {marcaciones.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center">
                                            No hay marcaciones registradas.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    marcaciones.map((marcacion) => (
                                        <TableRow key={marcacion.id}>
                                            <TableCell className="font-medium">
                                                {marcacion.marcado_en ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col text-sm">
                                                    <span>{marcacion.trabajador ?? '-'}</span>
                                                    <span className="text-muted-foreground">
                                                        {marcacion.documento ?? '-'}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col text-sm">
                                                    <span>{marcacion.numero_serie}</span>
                                                    <span className="text-muted-foreground">
                                                        {marcacion.codigo_qr}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>{marcacion.contratista ?? '-'}</TableCell>
                                            <TableCell>{marcacion.ubicacion ?? '-'}</TableCell>
                                            <TableCell>{marcacion.device_id ?? '-'}</TableCell>
                                            <TableCell>{marcacion.sync_batch_id ?? '-'}</TableCell>
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

PackingMarcacionesIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
