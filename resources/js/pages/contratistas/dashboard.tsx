import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowRight,
    CheckCircle2,
    Clock,
    FileClock,
    FileWarning,
    ShieldAlert,
    TrendingUp,
    UploadCloud,
    Users,
} from 'lucide-react';
import { type ReactNode } from 'react';

interface Stats {
    personal_activo: number;
    documentos_pendientes: number;
    documentos_vencidos: number;
    estado_cumplimiento: {
        estado: 'al_dia' | 'pendiente' | 'bloqueado';
        porcentaje: number;
        mensaje: string;
    };
}

interface Alerta {
    tipo: 'warning' | 'error' | 'info';
    mensaje: string;
    fecha?: string;
}

interface Contratista {
    id: number;
    rut: string;
    razon_social: string;
    nombre_fantasia: string | null;
    estado: 'activo' | 'inactivo' | 'bloqueado';
}

interface Props {
    contratista: Contratista;
    stats: Stats;
    alertas: Alerta[];
}

function KpiCard({
    title,
    value,
    subtitle,
    icon,
}: {
    title: string;
    value: string;
    subtitle: string;
    icon: ReactNode;
}) {
    return (
        <Card className="border-[var(--brand-green)]/20 bg-gradient-to-br from-white/90 via-white/85 to-[var(--brand-lime)]/10">
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between gap-3">
                    <CardTitle className="text-sm font-semibold text-foreground">{title}</CardTitle>
                    <div className="rounded-lg border border-[var(--brand-green)]/25 bg-[var(--brand-lime)]/12 p-2 text-[var(--brand-green)]">
                        {icon}
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-3xl font-bold tracking-tight text-foreground">{value}</p>
                <p className="mt-1 text-xs text-muted-foreground">{subtitle}</p>
            </CardContent>
        </Card>
    );
}

export default function ContratistaDashboard({ contratista, stats, alertas }: Props) {
    const cumplimiento = Math.min(100, Math.max(0, stats.estado_cumplimiento.porcentaje));

    const estadoConfig = {
        al_dia: {
            label: 'Al día',
            badgeClass: 'bg-[var(--brand-green)] text-[var(--primary-foreground)]',
            icon: CheckCircle2,
        },
        pendiente: {
            label: 'Pendiente',
            badgeClass: 'bg-[var(--brand-orange)] text-white',
            icon: Clock,
        },
        bloqueado: {
            label: 'Bloqueado',
            badgeClass: 'bg-destructive text-white',
            icon: ShieldAlert,
        },
    }[stats.estado_cumplimiento.estado];

    const EstadoIcon = estadoConfig.icon;

    const riesgoResumen =
        stats.documentos_vencidos > 0
            ? { label: 'Riesgo Alto', className: 'text-destructive' }
            : stats.documentos_pendientes > 0
              ? { label: 'Riesgo Medio', className: 'text-[var(--brand-orange-strong)]' }
              : { label: 'Riesgo Bajo', className: 'text-[var(--brand-green)]' };

    const alertStyles: Record<
        Alerta['tipo'],
        {
            title: string;
            borderClass: string;
            iconClass: string;
        }
    > = {
        error: {
            title: 'Error crítico',
            borderClass: 'border-destructive/35 bg-destructive/10',
            iconClass: 'text-destructive',
        },
        warning: {
            title: 'Advertencia',
            borderClass: 'border-[var(--brand-orange)]/35 bg-[var(--brand-orange)]/10',
            iconClass: 'text-[var(--brand-orange-strong)]',
        },
        info: {
            title: 'Información',
            borderClass: 'border-[var(--brand-green)]/35 bg-[var(--brand-lime)]/12',
            iconClass: 'text-[var(--brand-green)]',
        },
    };

    return (
        <AppLayout>
            <Head title="Dashboard - Contratista" />

            <div className="space-y-6">
                <section className="relative overflow-hidden rounded-2xl border border-[var(--brand-green)]/25 bg-gradient-to-r from-white/90 via-white/80 to-[var(--brand-lime)]/12 p-6 shadow-[0_20px_60px_-45px_rgba(3,140,52,0.55)]">
                    <div className="absolute -top-24 -right-12 size-56 rounded-full bg-[var(--brand-orange)]/15 blur-3xl" />
                    <div className="absolute -bottom-20 -left-8 size-52 rounded-full bg-[var(--brand-green)]/20 blur-3xl" />
                    <div className="relative flex flex-wrap items-start justify-between gap-4">
                        <div className="space-y-1">
                            <h1 className="text-3xl font-bold tracking-tight">Panel del Contratista</h1>
                            <p className="text-muted-foreground">
                                {contratista.nombre_fantasia || contratista.razon_social}
                            </p>
                            <div className="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                <Badge variant="outline">RUT: {contratista.rut}</Badge>
                                <Badge variant="outline">{stats.personal_activo} personal activo</Badge>
                                <Badge className={estadoConfig.badgeClass}>
                                    <EstadoIcon className="mr-1 size-3" />
                                    {estadoConfig.label}
                                </Badge>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href="/centro-carga">
                                    <UploadCloud className="mr-2 size-4" />
                                    Centro de Carga
                                </Link>
                            </Button>
                            <Button
                                asChild
                                className="bg-gradient-to-r from-[var(--brand-green)] via-[var(--brand-forest)] to-[var(--brand-orange)] text-[var(--primary-foreground)]"
                            >
                                <Link href="/estados-pago">
                                    <FileClock className="mr-2 size-4" />
                                    Estados de Pago
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <KpiCard
                        title="Personal Activo"
                        value={String(stats.personal_activo)}
                        subtitle="Trabajadores habilitados"
                        icon={<Users className="size-4" />}
                    />
                    <KpiCard
                        title="Documentos Pendientes"
                        value={String(stats.documentos_pendientes)}
                        subtitle="Requieren carga"
                        icon={<FileClock className="size-4" />}
                    />
                    <KpiCard
                        title="Documentos Vencidos"
                        value={String(stats.documentos_vencidos)}
                        subtitle="Atención inmediata"
                        icon={<FileWarning className="size-4" />}
                    />
                    <KpiCard
                        title="Cumplimiento"
                        value={`${cumplimiento}%`}
                        subtitle={stats.estado_cumplimiento.mensaje}
                        icon={<TrendingUp className="size-4" />}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <Card className="border-[var(--brand-green)]/22">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="size-5 text-[var(--brand-green)]" />
                                Estado de Cumplimiento
                            </CardTitle>
                            <CardDescription>
                                Resumen actual de la salud documental de la empresa.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="rounded-xl border border-border/70 bg-white/70 p-4">
                                <div className="mb-2 flex items-center justify-between text-sm">
                                    <p className="font-semibold">Progreso documental</p>
                                    <p className="font-semibold text-[var(--brand-green)]">{cumplimiento}%</p>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-muted/70">
                                    <div
                                        className="h-full rounded-full bg-gradient-to-r from-[var(--brand-green)] via-[var(--brand-lime)] to-[var(--brand-orange)] transition-all"
                                        style={{ width: `${Math.max(4, cumplimiento)}%` }}
                                    />
                                </div>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {stats.estado_cumplimiento.mensaje}
                                </p>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-3">
                                <div className="rounded-xl border border-border/70 bg-white/70 p-3">
                                    <p className="text-xs uppercase text-muted-foreground">Pendientes</p>
                                    <p className="mt-1 text-2xl font-bold">{stats.documentos_pendientes}</p>
                                </div>
                                <div className="rounded-xl border border-border/70 bg-white/70 p-3">
                                    <p className="text-xs uppercase text-muted-foreground">Vencidos</p>
                                    <p className="mt-1 text-2xl font-bold">{stats.documentos_vencidos}</p>
                                </div>
                                <div className="rounded-xl border border-border/70 bg-white/70 p-3">
                                    <p className="text-xs uppercase text-muted-foreground">Riesgo</p>
                                    <p className={`mt-1 text-lg font-bold ${riesgoResumen.className}`}>
                                        {riesgoResumen.label}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-[var(--brand-orange)]/25">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <AlertCircle className="size-5 text-[var(--brand-orange-strong)]" />
                                Alertas Recientes
                            </CardTitle>
                            <CardDescription>
                                Incidentes y advertencias vinculadas al cumplimiento.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {alertas.length === 0 ? (
                                <Alert className="border-[var(--brand-green)]/30 bg-[var(--brand-lime)]/10">
                                    <CheckCircle2 className="size-4 text-[var(--brand-green)]" />
                                    <AlertDescription>
                                        Sin alertas activas. El estado documental está bajo control.
                                    </AlertDescription>
                                </Alert>
                            ) : (
                                alertas.map((alerta, index) => {
                                    const style = alertStyles[alerta.tipo];

                                    return (
                                        <div key={`${alerta.tipo}-${index}`} className={`rounded-xl border px-3 py-3 ${style.borderClass}`}>
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="space-y-1">
                                                    <p className="text-sm font-semibold">{style.title}</p>
                                                    <p className="text-sm text-foreground">{alerta.mensaje}</p>
                                                </div>
                                                <AlertCircle className={`mt-0.5 size-4 shrink-0 ${style.iconClass}`} />
                                            </div>
                                            {alerta.fecha && (
                                                <p className="mt-2 text-xs text-muted-foreground">{alerta.fecha}</p>
                                            )}
                                        </div>
                                    );
                                })
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section>
                    <Card className="border-[var(--brand-green)]/25">
                        <CardHeader>
                            <CardTitle>Accesos Rápidos</CardTitle>
                            <CardDescription>
                                Gestión operativa con acceso directo a módulos clave.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <Button asChild variant="outline" className="h-auto justify-between py-4">
                                <Link href="/trabajadores">
                                    <span className="flex items-center gap-2">
                                        <Users className="size-4" />
                                        Personal
                                    </span>
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="h-auto justify-between py-4">
                                <Link href="/centro-carga">
                                    <span className="flex items-center gap-2">
                                        <UploadCloud className="size-4" />
                                        Centro de Carga
                                    </span>
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="h-auto justify-between py-4">
                                <Link href="/asistencias">
                                    <span className="flex items-center gap-2">
                                        <Clock className="size-4" />
                                        Asistencia
                                    </span>
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="h-auto justify-between py-4">
                                <Link href="/estados-pago">
                                    <span className="flex items-center gap-2">
                                        <FileClock className="size-4" />
                                        Estados de Pago
                                    </span>
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
