import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';
import { Radar, ShieldCheck, Sparkles } from 'lucide-react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}

const securitySignals = [
    {
        label: 'Sesion segura',
        value: 'TLS',
        icon: ShieldCheck,
    },
    {
        label: 'Revision',
        value: 'Activa',
        icon: Radar,
    },
    {
        label: 'Portal',
        value: 'Greenex',
        icon: Sparkles,
    },
];

export default function Login({
    status,
    canResetPassword,

}: LoginProps) {
    return (
        <AuthLayout
            title="Centro de acceso proveedor"
            description="Ingresa con tus credenciales para continuar en el control operacional."
        >
            <Head title="Iniciar sesion" />

            {status && (
                <div className="rounded-xl border border-[var(--brand-green)]/35 bg-[var(--brand-lime)]/14 px-3 py-2 text-center text-sm font-semibold text-[var(--brand-forest)]">
                    {status}
                </div>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="portal-login-form flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div
                            aria-hidden="true"
                            className="portal-login-aurora"
                        />

                        <div className="relative z-10 grid gap-5">
                            <div className="portal-login-hud rounded-xl p-3">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-[11px] font-semibold tracking-[0.16em] text-[var(--brand-green)] uppercase">
                                            Secure Gateway
                                        </p>
                                        <p className="mt-1 text-xs text-[var(--muted-foreground)]">
                                            Autenticacion reforzada y trazabilidad
                                            en tiempo real.
                                        </p>
                                    </div>
                                    <div className="portal-login-ping" />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="email"
                                    className="text-xs font-semibold tracking-[0.12em] text-[var(--brand-green)] uppercase"
                                >
                                    Correo corporativo
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="correo@empresa.cl"
                                    className="portal-login-input h-11 rounded-xl border-[var(--brand-green)]/30 bg-white/80 px-4"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password"
                                    className="text-xs font-semibold tracking-[0.12em] text-[var(--brand-green)] uppercase"
                                >
                                    Clave de acceso
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Tu clave"
                                    className="portal-login-input h-11 rounded-xl border-[var(--brand-green)]/30 bg-white/80 px-4"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center justify-between gap-3">
                                <div className="flex items-center space-x-3">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={3}
                                        className="border-[var(--brand-green)]/35"
                                    />
                                    <Label
                                        htmlFor="remember"
                                        className="text-sm text-[var(--foreground)]"
                                    >
                                        Mantener sesion
                                    </Label>
                                </div>

                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="text-xs font-semibold text-[var(--brand-orange-strong)] no-underline hover:underline"
                                        tabIndex={5}
                                    >
                                        Recuperar clave
                                    </TextLink>
                                )}
                            </div>

                            <Button
                                type="submit"
                                className="portal-login-button mt-2 h-11 w-full rounded-xl border border-[var(--brand-green)]/45 bg-gradient-to-r from-[var(--brand-green)] via-[var(--brand-forest)] to-[var(--brand-orange)] text-[var(--primary-foreground)] transition-all duration-300"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Iniciar sesion
                            </Button>
                        </div>

                        <div className="relative z-10 grid grid-cols-3 gap-2">
                            {securitySignals.map((signal) => (
                                <div
                                    key={signal.label}
                                    className="rounded-xl border border-[var(--brand-green)]/22 bg-white/68 px-2 py-2 text-center"
                                >
                                    <signal.icon className="mx-auto h-4 w-4 text-[var(--brand-green)]" />
                                    <p className="mt-1 text-[10px] font-semibold text-[var(--muted-foreground)] uppercase tracking-wide">
                                        {signal.label}
                                    </p>
                                    <p className="text-xs font-bold text-[var(--brand-green)]">
                                        {signal.value}
                                    </p>
                                </div>
                            ))}
                        </div>


                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
