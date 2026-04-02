import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '@/layouts/app';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowLeft } from 'lucide-react';

export default function CreateContratista() {
    const { data, setData, post, processing, errors } = useForm({
        rut: '',
        razon_social: '',
        nombre_fantasia: '',
        direccion: '',
        comuna: '',
        region: '',
        telefono: '',
        email: '',
        observaciones: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/contratistas');
    };

    return (
        <>
            <Head title="Crear Contratista" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/admin/contratistas">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Crear Contratista</h1>
                        <p className="text-muted-foreground">
                            Registre un nuevo contratista en el sistema
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos del Contratista</CardTitle>
                        <CardDescription>Complete la información básica</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="rut">RUT</Label>
                                    <Input
                                        id="rut"
                                        placeholder="12345678-9"
                                        value={data.rut}
                                        onChange={(e) => setData('rut', e.target.value)}
                                        required
                                    />
                                    {errors.rut && (
                                        <p className="text-sm text-destructive">{errors.rut}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="razon_social">Razón Social</Label>
                                    <Input
                                        id="razon_social"
                                        value={data.razon_social}
                                        onChange={(e) => setData('razon_social', e.target.value)}
                                        required
                                    />
                                    {errors.razon_social && (
                                        <p className="text-sm text-destructive">
                                            {errors.razon_social}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="nombre_fantasia">Nombre de Fantasía</Label>
                                    <Input
                                        id="nombre_fantasia"
                                        value={data.nombre_fantasia}
                                        onChange={(e) => setData('nombre_fantasia', e.target.value)}
                                    />
                                    {errors.nombre_fantasia && (
                                        <p className="text-sm text-destructive">
                                            {errors.nombre_fantasia}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="telefono">Teléfono</Label>
                                    <Input
                                        id="telefono"
                                        value={data.telefono}
                                        onChange={(e) => setData('telefono', e.target.value)}
                                        required
                                    />
                                    {errors.telefono && (
                                        <p className="text-sm text-destructive">{errors.telefono}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-destructive">{errors.email}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="direccion">Dirección</Label>
                                    <Input
                                        id="direccion"
                                        value={data.direccion}
                                        onChange={(e) => setData('direccion', e.target.value)}
                                        required
                                    />
                                    {errors.direccion && (
                                        <p className="text-sm text-destructive">{errors.direccion}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="comuna">Comuna</Label>
                                    <Input
                                        id="comuna"
                                        value={data.comuna}
                                        onChange={(e) => setData('comuna', e.target.value)}
                                        required
                                    />
                                    {errors.comuna && (
                                        <p className="text-sm text-destructive">{errors.comuna}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="region">Región</Label>
                                    <Input
                                        id="region"
                                        value={data.region}
                                        onChange={(e) => setData('region', e.target.value)}
                                        required
                                    />
                                    {errors.region && (
                                        <p className="text-sm text-destructive">{errors.region}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="observaciones">Observaciones</Label>
                                <textarea
                                    id="observaciones"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    rows={3}
                                    value={data.observaciones}
                                    onChange={(e) => setData('observaciones', e.target.value)}
                                />
                                {errors.observaciones && (
                                    <p className="text-sm text-destructive">
                                        {errors.observaciones}
                                    </p>
                                )}
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    Crear Contratista
                                </Button>
                                <Link href="/admin/contratistas">
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreateContratista.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
