import { FormEventHandler, useEffect, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { AppLayout } from '@/layouts/app';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    AlertTriangle,
    ArrowLeftRight,
    ChevronRight,
    Loader2,
    Search,
    Users,
    Building2,
    FileText,
    CreditCard,
    ArrowRight,
    CheckCircle2,
} from 'lucide-react';
import { Page } from '@inertiajs/core';

interface ContratistaOption {
    value: number;
    label: string;
}

interface FaenaInfo {
    id: number;
    nombre: string;
}

interface TrabajadorItem {
    id: string;
    documento: string;
    nombre: string;
    apellido: string;
    estado: string;
    faenas_activas: FaenaInfo[];
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

interface PreviewData {
    total_trabajadores: number;
    trabajadores_invalidos: number;
    faenas_activas_a_cerrar: Array<{
        id: number;
        nombre: string;
        total_trabajadores: number;
    }>;
    tarjetas_qr_a_desasignar: number;
    faenas_destino_disponibles: Array<{
        value: number;
        label: string;
    }>;
}

interface Props {
    contratistas: ContratistaOption[];
}

export default function Transferencia({ contratistas }: Props) {
    const page = usePage<Page<Props>>();
    const flash = (page.props as any).flash ?? {};

    const [step, setStep] = useState<'select' | 'confirm'>('select');
    const [origenId, setOrigenId] = useState<string>('');
    const [destinoId, setDestinoId] = useState<string>('');
    const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
    const [search, setSearch] = useState('');
    const [trabajadores, setTrabajadores] = useState<TrabajadorItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [preview, setPreview] = useState<PreviewData | null>(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [showPreview, setShowPreview] = useState(false);
    const [faenaDestinoIds, setFaenaDestinoIds] = useState<number[]>([]);
    const [motivo, setMotivo] = useState('');
    const [successMsg, setSuccessMsg] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);

    useEffect(() => {
        if (flash.success) {
            setSuccessMsg(flash.success as string);
        }
    }, [flash.success]);

    const loadTrabajadores = async (contratistaId: string, pageNum: number = 1, searchTerm: string = '') => {
        if (!contratistaId) {
            setTrabajadores([]);
            return;
        }

        setLoading(true);
        try {
            const params = new URLSearchParams({ contratista_id: contratistaId, page: String(pageNum) });
            if (searchTerm) params.set('search', searchTerm);

            const res = await fetch(`/admin/contratistas/transferencia/trabajadores?${params}`);
            const data: PaginatedData<TrabajadorItem> = await res.json();

            setTrabajadores(data.data);
            setCurrentPage(data.current_page);
            setLastPage(data.last_page);
            setTotal(data.total);
            setSelectedIds(new Set());
        } catch {
            setTrabajadores([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (origenId) {
            loadTrabajadores(origenId, 1, search);
        }
    }, [origenId]);

    const handleSearch = () => {
        if (origenId) {
            loadTrabajadores(origenId, 1, search);
        }
    };

    const toggleWorker = (id: string) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    };

    const toggleAll = () => {
        if (selectedIds.size === trabajadores.length) {
            setSelectedIds(new Set());
        } else {
            setSelectedIds(new Set(trabajadores.map((t) => t.id)));
        }
    };

    const origenContratista = contratistas.find((c) => String(c.value) === origenId);
    const destinoContratista = contratistas.find((c) => String(c.value) === destinoId);

    const handlePreview = async () => {
        setPreviewLoading(true);
        try {
            const res = await fetch('/admin/contratistas/transferencia/preview', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (window as any).csrfToken ?? '' },
                body: JSON.stringify({
                    contratista_origen_id: Number(origenId),
                    contratista_destino_id: Number(destinoId),
                    trabajador_ids: Array.from(selectedIds),
                }),
            });
            const data: PreviewData = await res.json();
            setPreview(data);
            setShowPreview(true);
        } catch {
            //
        } finally {
            setPreviewLoading(false);
        }
    };

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        setSubmitting(true);

        router.post(
            '/admin/contratistas/transferencia',
            {
                contratista_origen_id: Number(origenId),
                contratista_destino_id: Number(destinoId),
                trabajador_ids: Array.from(selectedIds),
                faena_ids: faenaDestinoIds,
                motivo,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSubmitting(false);
                    setShowPreview(false);
                    setStep('select');
                    setSelectedIds(new Set());
                    setDestinoId('');
                    setOrigenId('');
                    setFaenaDestinoIds([]);
                    setMotivo('');
                    setTrabajadores([]);
                    setPreview(null);
                },
                onError: () => {
                    setSubmitting(false);
                    setShowPreview(false);
                },
            },
        );
    };

    return (
        <>
            <Head title="Traspaso de Personal" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Traspaso de Personal</h1>
                        <p className="text-muted-foreground">
                            Transfiera trabajadores de un contratista a otro de forma masiva
                        </p>
                    </div>
                </div>

                {successMsg && (
                    <Alert variant="default" className="border-green-500 bg-green-50 text-green-900 dark:bg-green-950 dark:text-green-100">
                        <CheckCircle2 className="h-4 w-4" />
                        <AlertTitle>Traspaso exitoso</AlertTitle>
                        <AlertDescription>{successMsg}</AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit}>
                    <div className="grid gap-6 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Building2 className="h-4 w-4" />
                                    Contratista Origen
                                </CardTitle>
                                <CardDescription>
                                    Seleccione el contratista actual de los trabajadores
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="origen">Contratista origen</Label>
                                    <Select value={origenId} onValueChange={(v) => { setOrigenId(v); setSelectedIds(new Set()); }}>
                                        <SelectTrigger id="origen">
                                            <SelectValue placeholder="Seleccione contratista..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {contratistas.map((c) => (
                                                <SelectItem key={c.value} value={String(c.value)}>
                                                    {c.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {origenId && (
                                    <div className="space-y-2">
                                        <Label htmlFor="search">Buscar trabajadores</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="search"
                                                placeholder="Nombre, RUT..."
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                            />
                                            <Button variant="outline" size="icon" onClick={handleSearch} type="button">
                                                <Search className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Building2 className="h-4 w-4" />
                                    Contratista Destino
                                </CardTitle>
                                <CardDescription>
                                    Seleccione el contratista que recibirá los trabajadores
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="destino">Contratista destino</Label>
                                    <Select value={destinoId} onValueChange={setDestinoId}>
                                        <SelectTrigger id="destino">
                                            <SelectValue placeholder="Seleccione contratista..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {contratistas
                                                .filter((c) => String(c.value) !== origenId)
                                                .map((c) => (
                                                    <SelectItem key={c.value} value={String(c.value)}>
                                                        {c.label}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {destinoId && preview && showPreview && (
                                    <div className="space-y-3 pt-2">
                                        <Separator />
                                        <div className="space-y-2">
                                            <Label>Reasignar a faenas del destino (opcional)</Label>
                                            <div className="max-h-40 space-y-2 overflow-y-auto rounded-md border p-3">
                                                {preview.faenas_destino_disponibles.length === 0 ? (
                                                    <p className="text-sm text-muted-foreground">El contratista destino no tiene faenas activas</p>
                                                ) : (
                                                    preview.faenas_destino_disponibles.map((f) => (
                                                        <label key={f.value} className="flex items-center gap-2 text-sm">
                                                            <Checkbox
                                                                checked={faenaDestinoIds.includes(f.value)}
                                                                onCheckedChange={(checked) => {
                                                                    if (checked) {
                                                                        setFaenaDestinoIds((prev) => [...prev, f.value]);
                                                                    } else {
                                                                        setFaenaDestinoIds((prev) => prev.filter((id) => id !== f.value));
                                                                    }
                                                                }}
                                                            />
                                                            {f.label}
                                                        </label>
                                                    ))
                                                )}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="motivo">Motivo del traspaso (opcional)</Label>
                                            <Textarea
                                                id="motivo"
                                                placeholder="Ej: Cambio de contratista por término de contrato..."
                                                value={motivo}
                                                onChange={(e) => setMotivo(e.target.value)}
                                                rows={2}
                                            />
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {origenId && (
                        <Card className="mt-6">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Users className="h-4 w-4" />
                                    Trabajadores de {origenContratista?.label ?? '...'}
                                    {total > 0 && (
                                        <Badge variant="outline" className="ml-2 font-normal">
                                            {total} trabajadores
                                        </Badge>
                                    )}
                                </CardTitle>
                                <CardDescription>
                                    Seleccione los trabajadores que desea transferir
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-0">
                                {loading ? (
                                    <div className="flex items-center justify-center py-12">
                                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                    </div>
                                ) : trabajadores.length === 0 ? (
                                    <div className="py-12 text-center text-sm text-muted-foreground">
                                        {search ? 'No se encontraron trabajadores con ese criterio de búsqueda' : 'Seleccione un contratista origen para ver sus trabajadores'}
                                    </div>
                                ) : (
                                    <>
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-10">
                                                        <Checkbox
                                                            checked={trabajadores.length > 0 && selectedIds.size === trabajadores.length}
                                                            onCheckedChange={toggleAll}
                                                        />
                                                    </TableHead>
                                                    <TableHead>RUT</TableHead>
                                                    <TableHead>Nombre</TableHead>
                                                    <TableHead>Estado</TableHead>
                                                    <TableHead>Faenas Activas</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {trabajadores.map((t) => (
                                                    <TableRow
                                                        key={t.id}
                                                        className="cursor-pointer"
                                                        onClick={() => toggleWorker(t.id)}
                                                    >
                                                        <TableCell>
                                                            <Checkbox checked={selectedIds.has(t.id)} />
                                                        </TableCell>
                                                        <TableCell className="font-mono text-sm">{t.documento}</TableCell>
                                                        <TableCell className="font-medium">{t.nombre} {t.apellido}</TableCell>
                                                        <TableCell>
                                                            <Badge variant={t.estado === 'activo' ? 'default' : 'secondary'}>
                                                                {t.estado}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            {t.faenas_activas.length === 0 ? (
                                                                <span className="text-sm text-muted-foreground">Sin faenas</span>
                                                            ) : (
                                                                <div className="flex flex-wrap gap-1">
                                                                    {t.faenas_activas.map((f) => (
                                                                        <Badge key={f.id} variant="outline" className="text-xs">
                                                                            {f.nombre}
                                                                        </Badge>
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>

                                        {lastPage > 1 && (
                                            <div className="flex items-center justify-center gap-2 border-t p-4">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={currentPage <= 1}
                                                    onClick={() => loadTrabajadores(origenId, currentPage - 1, search)}
                                                    type="button"
                                                >
                                                    Anterior
                                                </Button>
                                                <span className="text-sm text-muted-foreground">
                                                    Página {currentPage} de {lastPage}
                                                </span>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={currentPage >= lastPage}
                                                    onClick={() => loadTrabajadores(origenId, currentPage + 1, search)}
                                                    type="button"
                                                >
                                                    Siguiente
                                                </Button>
                                            </div>
                                        )}
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {selectedIds.size > 0 && destinoId && (
                        <div className="mt-6 flex items-center justify-between rounded-lg border bg-muted/50 p-4">
                            <div className="flex items-center gap-3 text-sm">
                                <Users className="h-5 w-5 text-muted-foreground" />
                                <span>
                                    <strong>{selectedIds.size}</strong> trabajador(es) seleccionado(s)
                                </span>
                                <ArrowRight className="h-4 w-4 text-muted-foreground" />
                                <Building2 className="h-5 w-5 text-muted-foreground" />
                                <span>{destinoContratista?.label ?? '...'}</span>
                            </div>
                            <Button
                                type="button"
                                onClick={handlePreview}
                                disabled={previewLoading}
                            >
                                {previewLoading ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <ChevronRight className="mr-2 h-4 w-4" />
                                )}
                                Previsualizar Traspaso
                            </Button>
                        </div>
                    )}
                </form>

                <Dialog open={showPreview} onOpenChange={setShowPreview}>
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <ArrowLeftRight className="h-5 w-5" />
                                Confirmar Traspaso
                            </DialogTitle>
                            <DialogDescription>
                                Revise el resumen antes de confirmar la operación
                            </DialogDescription>
                        </DialogHeader>

                        {preview && (
                            <div className="space-y-4">
                                <div className="rounded-lg border bg-muted/30 p-4">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Origen:</span>
                                        <span className="font-medium">{origenContratista?.label}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Destino:</span>
                                        <span className="font-medium">{destinoContratista?.label}</span>
                                    </div>
                                    <Separator className="my-2" />
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Trabajadores a transferir:</span>
                                        <span className="text-lg font-bold">{preview.total_trabajadores}</span>
                                    </div>
                                </div>

                                {preview.faenas_activas_a_cerrar.length > 0 && (
                                    <div className="space-y-1">
                                        <p className="flex items-center gap-2 text-sm font-medium text-amber-600">
                                            <AlertTriangle className="h-4 w-4" />
                                            Faenas que se cerrarán
                                        </p>
                                        <div className="max-h-32 space-y-1 overflow-y-auto text-sm text-muted-foreground">
                                            {preview.faenas_activas_a_cerrar.map((f) => (
                                                <div key={f.id} className="flex justify-between rounded bg-amber-50 px-3 py-1.5 dark:bg-amber-950/30">
                                                    <span>{f.nombre}</span>
                                                    <span className="font-medium">{f.total_trabajadores} trab.</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {preview.tarjetas_qr_a_desasignar > 0 && (
                                    <p className="flex items-center gap-2 text-sm text-amber-600">
                                        <CreditCard className="h-4 w-4" />
                                        {preview.tarjetas_qr_a_desasignar} tarjeta(s) QR serán desasignadas
                                    </p>
                                )}

                                {faenaDestinoIds.length > 0 && (
                                    <p className="flex items-center gap-2 text-sm text-green-600">
                                        <FileText className="h-4 w-4" />
                                        {selectedIds.size} trabajador(es) serán asignados a {faenaDestinoIds.length} faena(s) del destino
                                    </p>
                                )}
                            </div>
                        )}

                        <DialogFooter className="gap-2">
                            <Button variant="outline" onClick={() => setShowPreview(false)} disabled={submitting}>
                                Cancelar
                            </Button>
                            <Button onClick={handleSubmit} disabled={submitting}>
                                {submitting ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Transfiriendo...
                                    </>
                                ) : (
                                    'Confirmar Traspaso'
                                )}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

Transferencia.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
