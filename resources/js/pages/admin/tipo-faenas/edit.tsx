import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '@/layouts/app';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowLeft } from 'lucide-react';

interface TipoFaena {
    id: number;
    nombre: string;
    codigo: string;
    descripcion: string | null;
    activo: boolean;
}

interface Props {
    tipo: TipoFaena;
}

export default function TipoFaenaEdit({ tipo }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        nombre: tipo.nombre,
        codigo: tipo.codigo,
        descripcion: tipo.descripcion ?? '',
        activo: tipo.activo,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/tipo-faenas/${tipo.id}`);
    };

    return (
        <>
            <Head title="Editar Tipo de Faena" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/tipo-faenas">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Editar Tipo de Faena</h1>
                        <p className="text-muted-foreground">Actualice la configuracion del tipo.</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos del Tipo</CardTitle>
                        <CardDescription>Modifique los campos requeridos.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="nombre">Nombre</Label>
                                    <Input
                                        id="nombre"
                                        value={data.nombre}
                                        onChange={(e) => setData('nombre', e.target.value)}
                                        required
                                    />
                                    {errors.nombre && (
                                        <p className="text-sm text-destructive">{errors.nombre}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="codigo">Codigo</Label>
                                    <Input
                                        id="codigo"
                                        value={data.codigo}
                                        onChange={(e) => setData('codigo', e.target.value)}
                                        required
                                    />
                                    {errors.codigo && (
                                        <p className="text-sm text-destructive">{errors.codigo}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="descripcion">Descripcion</Label>
                                <textarea
                                    id="descripcion"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    rows={3}
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-destructive">{errors.descripcion}</p>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="activo"
                                    checked={data.activo}
                                    onCheckedChange={(checked) => setData('activo', Boolean(checked))}
                                />
                                <Label htmlFor="activo">Activo</Label>
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Link href="/tipo-faenas">
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

TipoFaenaEdit.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
