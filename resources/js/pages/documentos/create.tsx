import { FormEventHandler, useMemo, useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Upload } from 'lucide-react';

interface TipoDocumento {
    id: number;
    nombre: string;
    codigo: string;
    formatos_permitidos: string[];
}

interface ContratistaOption {
    value: number;
    label: string;
}

interface Props {
    tiposDocumentos: TipoDocumento[];
    contratistas: ContratistaOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documentos', href: '/documentos' },
    { title: 'Cargar', href: '/documentos/create' },
];

export default function DocumentoCreate({ tiposDocumentos, contratistas }: Props) {
    const defaultTipo = tiposDocumentos[0]?.id?.toString() ?? '';
    const { data, setData, post, processing, errors, progress, recentlySuccessful } = useForm({
        tipo_documento_id: defaultTipo,
        periodo_ano: new Date().getFullYear().toString(),
        periodo_mes: '',
        archivo: null as File | null,
        observaciones: '',
        contratista_id: contratistas[0]?.value?.toString() ?? '',
    });

    const [allowedExt, setAllowedExt] = useState<string[]>(
        tiposDocumentos.find((t) => t.id.toString() === defaultTipo)?.formatos_permitidos ?? [],
    );
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const [isDragging, setIsDragging] = useState(false);

    const isAdmin = useMemo(() => contratistas.length > 0, [contratistas.length]);

    const handleTipoChange = (value: string) => {
        setData('tipo_documento_id', value);
        const nextAllowed =
            tiposDocumentos.find((t) => t.id.toString() === value)?.formatos_permitidos ?? [];
        setAllowedExt(nextAllowed);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/documentos', {
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
        <>
            <Head title="Cargar Documento" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/documentos">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Cargar Documento</h1>
                        <p className="text-muted-foreground">
                            Adjunte el archivo según el tipo y período correspondiente.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Información del Documento</CardTitle>
                        <CardDescription>Revise los formatos permitidos antes de cargar.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="tipo_documento_id">Tipo de documento</Label>
                                    <Select
                                        value={data.tipo_documento_id}
                                        onValueChange={handleTipoChange}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione un tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {tiposDocumentos.map((tipo) => (
                                                <SelectItem key={tipo.id} value={tipo.id.toString()}>
                                                    {tipo.nombre} ({tipo.codigo})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.tipo_documento_id && (
                                        <p className="text-sm text-destructive">
                                            {errors.tipo_documento_id}
                                        </p>
                                    )}
                                </div>

                                {isAdmin && (
                                    <div className="space-y-2">
                                        <Label htmlFor="contratista_id">Contratista</Label>
                                        <Select
                                            value={data.contratista_id}
                                            onValueChange={(value) => setData('contratista_id', value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccione un contratista" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {contratistas.map((c) => (
                                                    <SelectItem key={c.value} value={c.value.toString()}>
                                                        {c.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.contratista_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.contratista_id}
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="periodo_ano">Año</Label>
                                    <Input
                                        id="periodo_ano"
                                        type="number"
                                        value={data.periodo_ano}
                                        onChange={(e) => setData('periodo_ano', e.target.value)}
                                        required
                                    />
                                    {errors.periodo_ano && (
                                        <p className="text-sm text-destructive">{errors.periodo_ano}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="periodo_mes">Mes (opcional)</Label>
                                    <Input
                                        id="periodo_mes"
                                        type="number"
                                        min={1}
                                        max={12}
                                        value={data.periodo_mes}
                                        onChange={(e) => setData('periodo_mes', e.target.value)}
                                        placeholder="1-12"
                                    />
                                    {errors.periodo_mes && (
                                        <p className="text-sm text-destructive">{errors.periodo_mes}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="archivo">
                                    Archivo {allowedExt.length ? `(formatos: ${allowedExt.join(', ')})` : ''}
                                </Label>

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
                                        accept={
                                            allowedExt.length ? allowedExt.map((ext) => `.${ext}`).join(',') : undefined
                                        }
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
                                    <p className="text-sm text-green-600">Archivo cargado correctamente.</p>
                                )}
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
                                    <p className="text-sm text-destructive">{errors.observaciones}</p>
                                )}
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    <Upload className="mr-2 h-4 w-4" />
                                    Cargar
                                </Button>
                                <Link href="/documentos">
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

DocumentoCreate.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);
