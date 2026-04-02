import { FormEventHandler, useMemo } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '@/layouts/app';
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
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowLeft } from 'lucide-react';

interface Option {
    value: string;
    label: string;
}

interface TipoFaenaOption {
    value: number;
    label: string;
}

interface TipoDocumento {
    id: number;
    nombre: string;
    codigo: string;
    descripcion: string | null;
    periodicidad: string;
    permite_multiples_en_mes: boolean;
    es_obligatorio: boolean;
    es_documento_trabajador: boolean;
    dias_vencimiento: number | null;
    formatos_permitidos: string[];
    tipo_faena_ids: number[];
    tamano_maximo_kb: number;
    requiere_validacion: boolean;
    instrucciones: string | null;
    activo: boolean;
}

interface Props {
    tipo: TipoDocumento;
    periodicidades: Option[];
    extensiones: Option[];
    tiposFaena: TipoFaenaOption[];
}

export default function TipoDocumentoEdit({ tipo, periodicidades, extensiones, tiposFaena }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        nombre: tipo.nombre,
        codigo: tipo.codigo,
        descripcion: tipo.descripcion ?? '',
        periodicidad: tipo.periodicidad,
        permite_multiples_en_mes: tipo.permite_multiples_en_mes,
        es_obligatorio: tipo.es_obligatorio,
        es_documento_trabajador: tipo.es_documento_trabajador,
        dias_vencimiento: tipo.dias_vencimiento ?? '',
        formatos_permitidos: tipo.formatos_permitidos ?? [],
        tipo_faena_ids: tipo.tipo_faena_ids ?? [],
        tamano_maximo_kb: tipo.tamano_maximo_kb,
        requiere_validacion: tipo.requiere_validacion,
        instrucciones: tipo.instrucciones ?? '',
        activo: tipo.activo,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/tipo-documentos/${tipo.id}`);
    };

    const toggleFormato = (value: string) => {
        setData(
            'formatos_permitidos',
            data.formatos_permitidos.includes(value)
                ? data.formatos_permitidos.filter((f: string) => f !== value)
                : [...data.formatos_permitidos, value],
        );
    };

    const allFormatsSelected = useMemo(
        () => data.formatos_permitidos.length === extensiones.length,
        [data.formatos_permitidos, extensiones.length],
    );

    const toggleAllFormats = () => {
        setData(
            'formatos_permitidos',
            allFormatsSelected ? [] : extensiones.map((ext) => ext.value),
        );
    };

    const toggleTipoFaena = (value: number) => {
        setData(
            'tipo_faena_ids',
            data.tipo_faena_ids.includes(value)
                ? data.tipo_faena_ids.filter((id) => id !== value)
                : [...data.tipo_faena_ids, value],
        );
    };

    return (
        <>
            <Head title="Editar Tipo de Documento" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/tipo-documentos">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Editar Tipo</h1>
                        <p className="text-muted-foreground">
                            Actualice la configuracion del tipo de documento.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos del Tipo</CardTitle>
                        <CardDescription>Modifique los datos necesarios.</CardDescription>
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
                                <div className="space-y-2">
                                    <Label htmlFor="periodicidad">Periodicidad</Label>
                                    <Select
                                        value={data.periodicidad}
                                        onValueChange={(value) => setData('periodicidad', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {periodicidades.map((periodo) => (
                                                <SelectItem key={periodo.value} value={periodo.value}>
                                                    {periodo.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.periodicidad && (
                                        <p className="text-sm text-destructive">{errors.periodicidad}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="dias_vencimiento">Dias de vencimiento (opcional)</Label>
                                    <Input
                                        id="dias_vencimiento"
                                        type="number"
                                        min={0}
                                        value={data.dias_vencimiento}
                                        onChange={(e) => setData('dias_vencimiento', e.target.value)}
                                        placeholder="Ej: 30"
                                    />
                                    {errors.dias_vencimiento && (
                                        <p className="text-sm text-destructive">{errors.dias_vencimiento}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="tamano_maximo_kb">Tamano maximo (KB)</Label>
                                    <Input
                                        id="tamano_maximo_kb"
                                        type="number"
                                        min={1}
                                        value={data.tamano_maximo_kb}
                                        onChange={(e) =>
                                            setData('tamano_maximo_kb', Number(e.target.value) || '')
                                        }
                                        required
                                    />
                                    {errors.tamano_maximo_kb && (
                                        <p className="text-sm text-destructive">
                                            {errors.tamano_maximo_kb}
                                        </p>
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

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Formatos permitidos</Label>
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="formatos_all"
                                            checked={allFormatsSelected}
                                            onCheckedChange={toggleAllFormats}
                                        />
                                        <Label htmlFor="formatos_all" className="text-sm">
                                            Seleccionar todos
                                        </Label>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2 pt-2">
                                        {extensiones.map((ext) => (
                                            <label
                                                key={ext.value}
                                                className="flex items-center gap-2 rounded-md border border-border/60 px-3 py-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={data.formatos_permitidos.includes(ext.value)}
                                                    onCheckedChange={() => toggleFormato(ext.value)}
                                                />
                                                <span>{ext.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                    {errors.formatos_permitidos && (
                                        <p className="text-sm text-destructive">
                                            {errors.formatos_permitidos as unknown as string}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label>Tipos de faena aplicables</Label>
                                    <div className="grid grid-cols-1 gap-2 pt-2 sm:grid-cols-2">
                                        {tiposFaena.map((tipoFaena) => (
                                            <label
                                                key={tipoFaena.value}
                                                className="flex items-center gap-2 rounded-md border border-border/60 px-3 py-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={data.tipo_faena_ids.includes(tipoFaena.value)}
                                                    onCheckedChange={() => toggleTipoFaena(tipoFaena.value)}
                                                />
                                                <span>{tipoFaena.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                    {errors.tipo_faena_ids && (
                                        <p className="text-sm text-destructive">
                                            {errors.tipo_faena_ids as unknown as string}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="permite_multiples_en_mes"
                                        checked={data.permite_multiples_en_mes}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'permite_multiples_en_mes',
                                                Boolean(checked),
                                            )
                                        }
                                    />
                                    <Label htmlFor="permite_multiples_en_mes">
                                        Permite múltiples cargas en el mes
                                    </Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="es_obligatorio"
                                        checked={data.es_obligatorio}
                                        onCheckedChange={(checked) =>
                                            setData('es_obligatorio', Boolean(checked))
                                        }
                                    />
                                    <Label htmlFor="es_obligatorio">Obligatorio</Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="es_documento_trabajador"
                                        checked={data.es_documento_trabajador}
                                        onCheckedChange={(checked) =>
                                            setData('es_documento_trabajador', Boolean(checked))
                                        }
                                    />
                                    <Label htmlFor="es_documento_trabajador">
                                        Documento de trabajador
                                    </Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="requiere_validacion"
                                        checked={data.requiere_validacion}
                                        onCheckedChange={(checked) =>
                                            setData('requiere_validacion', Boolean(checked))
                                        }
                                    />
                                    <Label htmlFor="requiere_validacion">
                                        Requiere validacion manual
                                    </Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="activo"
                                        checked={data.activo}
                                        onCheckedChange={(checked) =>
                                            setData('activo', Boolean(checked))
                                        }
                                    />
                                    <Label htmlFor="activo">Activo</Label>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="instrucciones">Instrucciones (opcional)</Label>
                                <textarea
                                    id="instrucciones"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    rows={3}
                                    value={data.instrucciones}
                                    onChange={(e) => setData('instrucciones', e.target.value)}
                                />
                                {errors.instrucciones && (
                                    <p className="text-sm text-destructive">{errors.instrucciones}</p>
                                )}
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Link href="/tipo-documentos">
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

TipoDocumentoEdit.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
