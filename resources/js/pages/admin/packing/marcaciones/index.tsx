import { AppLayout } from '@/layouts/app';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    filters: {
        search?: string;
    };
}

export default function PackingMarcacionesIndex({ marcaciones, indexUrl, canManageCards, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const handleSearch = () => {
        router.get(
            indexUrl,
            { search: search || undefined },
            { preserveState: true, preserveScroll: true },
        );
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
                            Busque por trabajador, documento o número de serie de tarjeta.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,140px]">
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
                            <div className="flex items-end">
                                <Button className="w-full" onClick={handleSearch}>
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
