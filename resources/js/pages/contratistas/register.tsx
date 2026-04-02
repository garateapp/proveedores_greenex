import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form } from '@inertiajs/react';
import { Head } from '@inertiajs/react';

export default function Register() {
    return (
        <>
            <Head title="Registro de Contratista" />

            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 dark:bg-gray-900 sm:px-6 lg:px-8">
                <div className="w-full max-w-4xl">
                    <div className="mb-8 text-center">
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Portal de Proveedores
                        </h1>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Registro de Empresa Contratista
                        </p>
                    </div>

                    <Form action="/contratistas/registro" method="post">
                        {({ errors, processing }) => (
                            <div className="space-y-6">
                                {/* Datos de la Empresa */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Datos de la Empresa</CardTitle>
                                        <CardDescription>
                                            Información legal de la empresa contratista
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="rut">RUT *</Label>
                                                <Input
                                                    id="rut"
                                                    name="rut"
                                                    type="text"
                                                    placeholder="12345678-9"
                                                    required
                                                    maxLength={20}
                                                />
                                                {errors.rut && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">
                                                        {errors.rut}
                                                    </p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="razon_social">Razón Social *</Label>
                                                <Input
                                                    id="razon_social"
                                                    name="razon_social"
                                                    type="text"
                                                    required
                                                />
                                                {errors.razon_social && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">
                                                        {errors.razon_social}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="nombre_fantasia">Nombre de Fantasía</Label>
                                            <Input
                                                id="nombre_fantasia"
                                                name="nombre_fantasia"
                                                type="text"
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="direccion">Dirección *</Label>
                                            <Input id="direccion" name="direccion" type="text" required />
                                            {errors.direccion && (
                                                <p className="text-sm text-red-600 dark:text-red-400">
                                                    {errors.direccion}
                                                </p>
                                            )}
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="comuna">Comuna *</Label>
                                                <Input id="comuna" name="comuna" type="text" required />
                                                {errors.comuna && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">
                                                        {errors.comuna}
                                                    </p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="region">Región *</Label>
                                                <Input id="region" name="region" type="text" required />
                                                {errors.region && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">
                                                        {errors.region}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="telefono">Teléfono *</Label>
                                                <Input
                                                    id="telefono"
                                                    name="telefono"
                                                    type="tel"
                                                    placeholder="+56912345678"
                                                    required
                                                />
                                                {errors.telefono && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">
                                                        {errors.telefono}
                                                    </p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="email">Email Empresa *</Label>
                                                <Input
                                                    id="email"
                                                    name="email"
                                                    type="email"
                                                    required
                                                />
                                                {errors.email && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">
                                                        {errors.email}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Datos del Administrador */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Usuario Administrador</CardTitle>
                                        <CardDescription>
                                            Credenciales para acceder al portal
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="admin_name">Nombre Completo *</Label>
                                            <Input
                                                id="admin_name"
                                                name="admin_name"
                                                type="text"
                                                required
                                            />
                                            {errors.admin_name && (
                                                <p className="text-sm text-red-600 dark:text-red-400">
                                                    {errors.admin_name}
                                                </p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="admin_email">Email *</Label>
                                            <Input
                                                id="admin_email"
                                                name="admin_email"
                                                type="email"
                                                required
                                            />
                                            {errors.admin_email && (
                                                <p className="text-sm text-red-600 dark:text-red-400">
                                                    {errors.admin_email}
                                                </p>
                                            )}
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="admin_password">Contraseña *</Label>
                                                <Input
                                                    id="admin_password"
                                                    name="admin_password"
                                                    type="password"
                                                    required
                                                />
                                                {errors.admin_password && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">
                                                        {errors.admin_password}
                                                    </p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="admin_password_confirmation">
                                                    Confirmar Contraseña *
                                                </Label>
                                                <Input
                                                    id="admin_password_confirmation"
                                                    name="admin_password_confirmation"
                                                    type="password"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Botones de Acción */}
                                <div className="flex items-center justify-between">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => (window.location.href = '/login')}
                                    >
                                        Volver al Login
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Registrando...' : 'Registrar Empresa'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}
