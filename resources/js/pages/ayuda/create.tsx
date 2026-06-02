import { FormEventHandler, useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Upload } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Centro de Ayuda', href: '/ayuda' },
    { title: 'Subir Documento', href: '/ayuda/crear' },
];

export default function AyudaCreate() {
    const { data, setData, post, processing, errors, progress, recentlySuccessful } = useForm({
        nombre: '',
        descripcion: '',
        archivo: null as File | null,
    });

    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const [isDragging, setIsDragging] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/ayuda', {
            forceFormData: true,
        });
    };

    const handleFileChange = (file: File | null) => {
        if (!file) {
            setData('archivo', null);
            return;
        }

        setData('archivo', file);
    };

    const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileChange(e.dataTransfer.files[0]);
        }
    };

    const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(false);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Subir Documento de Ayuda" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/ayuda">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 size-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Subir Documento</h1>
                        <p className="text-muted-foreground">
                            Adjunte un documento de referencia para los usuarios del sistema.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Información del Documento</CardTitle>
                        <CardDescription>
                            Solo se permiten archivos Word, PDF, Excel y PowerPoint (máx. 20 MB).
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="nombre">Nombre del documento</Label>
                                <Input
                                    id="nombre"
                                    value={data.nombre}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    placeholder="Ej: Manual de usuario"
                                    required
                                />
                                {errors.nombre && (
                                    <p className="text-sm text-destructive">{errors.nombre}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="descripcion">Descripción (opcional)</Label>
                                <textarea
                                    id="descripcion"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    rows={3}
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                    placeholder="Breve descripción del contenido del documento"
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-destructive">{errors.descripcion}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="archivo">Archivo</Label>

                                <div
                                    className={`flex flex-col items-center justify-center rounded-md border border-dashed p-6 text-sm transition ${
                                        isDragging
                                            ? 'border-primary bg-primary/5'
                                            : 'border-border hover:border-primary'
                                    }`}
                                    onDrop={handleDrop}
                                    onDragOver={handleDragOver}
                                    onDragLeave={handleDragLeave}
                                >
                                    <p className="text-center text-muted-foreground">
                                        Arrastra y suelta el archivo aquí
                                    </p>
                                    <p className="text-center text-muted-foreground">o</p>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => fileInputRef.current?.click()}
                                        className="mt-2"
                                    >
                                        Seleccionar archivo
                                    </Button>
                                    {data.archivo && (
                                        <p className="mt-3 text-center text-foreground">
                                            {data.archivo.name}
                                        </p>
                                    )}
                                    <Input
                                        id="archivo"
                                        ref={fileInputRef}
                                        type="file"
                                        className="hidden"
                                        accept=".doc,.docx,.pdf,.xls,.xlsx,.ppt,.pptx"
                                        onChange={(e) => handleFileChange(e.target.files?.[0] || null)}
                                        required
                                    />
                                </div>

                                {progress && (
                                    <p className="text-sm text-muted-foreground">
                                        Subiendo: {progress.percentage}%
                                    </p>
                                )}
                                {errors.archivo && (
                                    <p className="text-sm text-destructive">{errors.archivo}</p>
                                )}
                                {recentlySuccessful && (
                                    <p className="text-sm text-green-600">
                                        Documento subido correctamente.
                                    </p>
                                )}
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    <Upload className="mr-2 size-4" />
                                    {processing ? 'Subiendo...' : 'Subir documento'}
                                </Button>
                                <Link href="/ayuda">
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
