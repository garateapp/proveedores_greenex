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
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { AppLayout } from '@/layouts/app';
import { Head, router } from '@inertiajs/react';
import { Eye, Search } from 'lucide-react';
import { type ReactNode, useMemo, useState } from 'react';

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

interface AuditLogRow {
    id: number;
    event: string;
    event_label: string;
    auditable_type: string;
    auditable_model: string;
    auditable_id: string;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
    changed_fields: string[];
    old_values: Record<string, unknown>;
    new_values: Record<string, unknown>;
    url: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string | null;
}

interface EventOption {
    value: string;
    label: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    auditLogs: {
        data: AuditLogRow[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        event?: string;
    };
    events: EventOption[];
}

function getEventBadgeVariant(
    event: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (event) {
        case 'created':
            return 'default';
        case 'updated':
            return 'secondary';
        case 'deleted':
        case 'force_deleted':
            return 'destructive';
        default:
            return 'outline';
    }
}

export default function AuditLogsIndex({ auditLogs, filters, events }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [eventFilter, setEventFilter] = useState(filters.event || 'all');
    const [selectedLog, setSelectedLog] = useState<AuditLogRow | null>(null);

    const selectedLogOldValues = useMemo(() => {
        if (!selectedLog) {
            return '{}';
        }

        return JSON.stringify(selectedLog.old_values ?? {}, null, 2);
    }, [selectedLog]);

    const selectedLogNewValues = useMemo(() => {
        if (!selectedLog) {
            return '{}';
        }

        return JSON.stringify(selectedLog.new_values ?? {}, null, 2);
    }, [selectedLog]);

    const handleSearch = (): void => {
        router.get(
            '/admin/audit-logs',
            {
                search: search.trim() || undefined,
                event: eventFilter === 'all' ? undefined : eventFilter,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Logs de Auditoría" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Logs de Auditoría
                    </h1>
                    <p className="text-muted-foreground">
                        Registro de acciones y cambios críticos en el sistema.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Busque por usuario, modelo, ID de registro o URL.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-[1fr,220px,140px]">
                            <Input
                                placeholder="Ej: Usuario, Documento, Contratista..."
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        handleSearch();
                                    }
                                }}
                            />
                            <Select
                                value={eventFilter}
                                onValueChange={setEventFilter}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los eventos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todos los eventos
                                    </SelectItem>
                                    {events.map((eventOption) => (
                                        <SelectItem
                                            key={eventOption.value}
                                            value={eventOption.value}
                                        >
                                            {eventOption.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button onClick={handleSearch}>
                                <Search className="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Evento</TableHead>
                                    <TableHead>Registro</TableHead>
                                    <TableHead>Usuario</TableHead>
                                    <TableHead>Cambios</TableHead>
                                    <TableHead>Contexto</TableHead>
                                    <TableHead className="text-right">
                                        Detalle
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {auditLogs.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="text-center"
                                        >
                                            No se encontraron logs de auditoría
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    auditLogs.data.map((auditLog) => (
                                        <TableRow key={auditLog.id}>
                                            <TableCell className="text-sm whitespace-nowrap">
                                                {auditLog.created_at || '-'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={getEventBadgeVariant(
                                                        auditLog.event,
                                                    )}
                                                >
                                                    {auditLog.event_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                <div className="flex flex-col">
                                                    <span className="font-medium">
                                                        {
                                                            auditLog.auditable_model
                                                        }
                                                    </span>
                                                    <span className="text-muted-foreground">
                                                        ID:{' '}
                                                        {auditLog.auditable_id}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {auditLog.user ? (
                                                    <div className="flex flex-col">
                                                        <span>
                                                            {auditLog.user.name}
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {
                                                                auditLog.user
                                                                    .email
                                                            }
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Sistema
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {auditLog.changed_fields
                                                        .length === 0 ? (
                                                        <span className="text-sm text-muted-foreground">
                                                            -
                                                        </span>
                                                    ) : (
                                                        auditLog.changed_fields
                                                            .slice(0, 3)
                                                            .map((field) => (
                                                                <Badge
                                                                    key={`${auditLog.id}-${field}`}
                                                                    variant="outline"
                                                                >
                                                                    {field}
                                                                </Badge>
                                                            ))
                                                    )}
                                                    {auditLog.changed_fields
                                                        .length > 3 && (
                                                        <Badge variant="secondary">
                                                            +
                                                            {auditLog
                                                                .changed_fields
                                                                .length - 3}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="max-w-[260px] text-xs text-muted-foreground">
                                                <div className="space-y-1">
                                                    <p>
                                                        IP:{' '}
                                                        {auditLog.ip_address ||
                                                            '-'}
                                                    </p>
                                                    <p className="truncate">
                                                        {auditLog.url || '-'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setSelectedLog(auditLog)
                                                    }
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {auditLogs.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {auditLogs.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => {
                                    if (link.url) {
                                        router.visit(link.url);
                                    }
                                }}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            <Dialog
                open={selectedLog !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedLog(null);
                    }
                }}
            >
                <DialogContent className="max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>Detalle de auditoría</DialogTitle>
                        <DialogDescription>
                            {selectedLog
                                ? `${selectedLog.event_label} · ${selectedLog.auditable_model} #${selectedLog.auditable_id}`
                                : 'Detalle'}
                        </DialogDescription>
                    </DialogHeader>

                    {selectedLog && (
                        <div className="space-y-4">
                            <div className="grid gap-3 rounded-lg border border-border/70 bg-muted/20 p-3 md:grid-cols-3">
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Fecha
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {selectedLog.created_at || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        Usuario
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {selectedLog.user?.name || 'Sistema'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground uppercase">
                                        IP
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {selectedLog.ip_address || '-'}
                                    </p>
                                </div>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                <div className="space-y-2">
                                    <p className="text-sm font-semibold">
                                        Valores anteriores
                                    </p>
                                    <pre className="max-h-72 overflow-auto rounded-lg border border-border/70 bg-muted/20 p-3 text-xs">
                                        {selectedLogOldValues}
                                    </pre>
                                </div>
                                <div className="space-y-2">
                                    <p className="text-sm font-semibold">
                                        Valores nuevos
                                    </p>
                                    <pre className="max-h-72 overflow-auto rounded-lg border border-border/70 bg-muted/20 p-3 text-xs">
                                        {selectedLogNewValues}
                                    </pre>
                                </div>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

AuditLogsIndex.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
