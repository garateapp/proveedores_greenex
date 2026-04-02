import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    Clock3,
    FileCheck2,
    Radar,
    ShieldCheck,
    Users,
    Zap,
} from 'lucide-react';

const platformSignals = [
    {
        title: 'Cumplimiento en vivo',
        description: 'Visibilidad continua de estados, vencimientos y validaciones criticas.',
        icon: Radar,
    },
    {
        title: 'Trazabilidad documental',
        description: 'Cada carga y aprobacion queda registrada con historial auditable.',
        icon: FileCheck2,
    },
    {
        title: 'Operacion multi-contratista',
        description: 'Gobierno centralizado con segmentacion por perfiles y empresas.',
        icon: Building2,
    },
];

const dashboardMetrics = [
    { value: '99.9%', label: 'Disponibilidad operativa' },
    { value: '< 3 min', label: 'Tiempo promedio de carga' },
    { value: '24/7', label: 'Monitoreo de cumplimiento' },
];

const portalModules = [
    { name: 'Personal', icon: Users },
    { name: 'Documentos', icon: FileCheck2 },
    { name: 'Asistencia', icon: Clock3 },
    { name: 'Estados de Pago', icon: ShieldCheck },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Portal Proveedores">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>

            <div className="relative min-h-screen overflow-hidden p-4 text-[var(--foreground)] sm:p-6">
                <div className="pointer-events-none absolute -left-24 -top-20 h-72 w-72 rounded-full bg-[var(--brand-lime)]/25 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 top-14 h-80 w-80 rounded-full bg-[var(--brand-orange)]/20 blur-3xl" />

                <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                    <header className="portal-panel flex flex-wrap items-center justify-between gap-3 rounded-2xl px-4 py-3 sm:px-5">
                        <div className="flex items-center gap-3">
                            <div className="rounded-xl border border-[var(--brand-green)]/30 bg-white/70 p-2.5">
                                <img
                                    src="/img/iconogreenex.png"
                                    alt="Greenex"
                                    className="h-7 w-7 object-contain"
                                />
                            </div>
                            <div>
                                <p className="text-sm font-bold tracking-wide text-[var(--brand-green)]">
                                    PORTAL PROVEEDORES
                                </p>
                                <p className="text-xs text-[var(--muted-foreground)]">
                                    Operacion documental inteligente
                                </p>
                            </div>
                        </div>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center gap-2 rounded-xl border border-[var(--brand-green)]/35 bg-[var(--brand-green)] px-4 py-2 text-sm font-semibold text-[var(--primary-foreground)] transition-colors hover:bg-[var(--brand-forest)]"
                                >
                                    Ir al dashboard
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            ) : (

                                    <Link
                                        href={login()}
                                        className="rounded-xl border border-[var(--brand-green)]/30 bg-white/60 px-4 py-2 text-sm font-semibold text-[var(--brand-green)] transition-colors hover:bg-[var(--brand-lime)]/15"
                                    >
                                        Iniciar sesion
                                    </Link>

                            )}
                        </nav>
                    </header>

                    <main className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                        <section className="portal-panel relative overflow-hidden rounded-2xl p-6 sm:p-8">
                            <div className="pointer-events-none absolute -right-24 top-8 h-64 w-64 rounded-full bg-[var(--brand-green)]/16 blur-3xl" />
                            <div className="pointer-events-none absolute left-8 top-4 h-px w-24 bg-[var(--brand-orange)]/60" />

                            <span className="inline-flex items-center gap-2 rounded-full border border-[var(--brand-green)]/30 bg-white/65 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-green)]">
                                <Zap className="h-3.5 w-3.5" />
                                Greenex Control Layer
                            </span>

                            <h1 className="mt-4 max-w-2xl text-4xl leading-tight font-black tracking-tight sm:text-5xl">
                                Cumplimiento laboral con una interfaz{' '}
                                <span className="text-[var(--brand-green)]">amigable</span>,
                                clara y accionable.
                            </h1>

                            <p className="mt-4 max-w-2xl text-base text-[var(--muted-foreground)]">
                                Un centro unico para gestionar personal, documentacion
                                critica, asistencia y estados de pago en tiempo real.
                            </p>

                            <div className="mt-6 grid gap-3 sm:grid-cols-3">
                                {dashboardMetrics.map((metric) => (
                                    <div
                                        key={metric.label}
                                        className="rounded-xl border border-[var(--brand-green)]/20 bg-white/70 p-3"
                                    >
                                        <p className="text-2xl font-black text-[var(--brand-green)]">
                                            {metric.value}
                                        </p>
                                        <p className="text-xs text-[var(--muted-foreground)]">
                                            {metric.label}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="portal-panel rounded-2xl p-6 sm:p-8">
                            <h2 className="text-sm font-bold tracking-[0.14em] text-[var(--brand-green)] uppercase">
                                Modulos centrales
                            </h2>
                            <div className="mt-4 space-y-2">
                                {portalModules.map((module) => (
                                    <div
                                        key={module.name}
                                        className="flex items-center justify-between rounded-xl border border-[var(--brand-green)]/20 bg-white/65 px-3 py-2.5"
                                    >
                                        <span className="inline-flex items-center gap-2 text-sm font-semibold text-[var(--foreground)]">
                                            <module.icon className="h-4 w-4 text-[var(--brand-green)]" />
                                            {module.name}
                                        </span>
                                        <ArrowRight className="h-4 w-4 text-[var(--brand-orange-strong)]" />
                                    </div>
                                ))}
                            </div>

                            <div className="mt-5 rounded-xl border border-[var(--brand-orange)]/35 bg-[var(--brand-orange)]/10 px-4 py-3">
                                <p className="text-sm font-semibold text-[var(--brand-orange-strong)]">
                                    Pipeline activo
                                </p>
                                <p className="mt-1 text-sm text-[var(--muted-foreground)]">
                                    Integracion de procesos y validaciones en ciclos continuos.
                                </p>
                            </div>
                        </section>
                    </main>

                    <section className="grid gap-4 md:grid-cols-3">
                        {platformSignals.map((signal) => (
                            <article
                                key={signal.title}
                                className="portal-panel rounded-2xl p-5"
                            >
                                <div className="inline-flex rounded-lg border border-[var(--brand-green)]/30 bg-white/70 p-2.5">
                                    <signal.icon className="h-5 w-5 text-[var(--brand-green)]" />
                                </div>
                                <h3 className="mt-3 text-lg font-bold text-[var(--foreground)]">
                                    {signal.title}
                                </h3>
                                <p className="mt-2 text-sm leading-6 text-[var(--muted-foreground)]">
                                    {signal.description}
                                </p>
                            </article>
                        ))}
                    </section>
                </div>
            </div>
        </>
    );
}
