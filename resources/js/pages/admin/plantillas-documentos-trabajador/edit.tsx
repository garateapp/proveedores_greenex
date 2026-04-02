import { type FormEventHandler, type ReactNode, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '@/layouts/app';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ArrowLeft, Braces } from 'lucide-react';
import { Editor } from 'react-draft-wysiwyg';
import {
    ContentState,
    convertToRaw,
    EditorState,
    Modifier,
} from 'draft-js';
import draftToHtml from 'draftjs-to-html';
import htmlToDraft from 'html-to-draftjs';

interface TipoDocumentoOption {
    value: number;
    label: string;
}

interface Option {
    value: string;
    label: string;
}

interface Plantilla {
    id: number;
    nombre: string;
    tipo_documento_id: number;
    contenido_html: string;
    fuente_nombre: string;
    fuente_tamano: number;
    color_texto: string;
    formato_papel: string;
    activo: boolean;
}

interface Props {
    plantilla: Plantilla;
    tiposDocumentos: TipoDocumentoOption[];
    availableVariables: string[];
    fontOptions: Option[];
    paperOptions: Option[];
}

const editorToolbar = {
    options: [
        'inline',
        'fontSize',
        'fontFamily',
        'colorPicker',
        'list',
        'textAlign',
        'link',
        'history',
        'remove',
    ],
    inline: {
        inDropdown: false,
    },
    fontFamily: {
        options: [
            'Arial',
            'Georgia',
            'Times New Roman',
            'Verdana',
            'Tahoma',
            'Courier New',
            'DejaVu Sans',
        ],
    },
    fontSize: {
        options: [9, 10, 11, 12, 13, 14, 16, 18, 20, 24],
    },
    colorPicker: {
        colors: [
            '#111827',
            '#374151',
            '#1F2937',
            '#047857',
            '#B45309',
            '#B91C1C',
            '#1D4ED8',
            '#7C3AED',
        ],
    },
} as const;

const createEditorStateFromHtml = (html: string): EditorState => {
    if (html.trim() === '') {
        return EditorState.createEmpty();
    }

    const parsedHtml = htmlToDraft(html);
    if (!parsedHtml || parsedHtml.contentBlocks.length === 0) {
        return EditorState.createEmpty();
    }

    const contentState = ContentState.createFromBlockArray(
        parsedHtml.contentBlocks,
        parsedHtml.entityMap,
    );

    return EditorState.createWithContent(contentState);
};

export default function PlantillaDocumentoTrabajadorEdit({
    plantilla,
    tiposDocumentos,
    availableVariables,
    fontOptions,
    paperOptions,
}: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        nombre: plantilla.nombre,
        tipo_documento_id: plantilla.tipo_documento_id.toString(),
        contenido_html: plantilla.contenido_html,
        fuente_nombre: plantilla.fuente_nombre,
        fuente_tamano: plantilla.fuente_tamano.toString(),
        color_texto: plantilla.color_texto,
        formato_papel: plantilla.formato_papel,
        activo: plantilla.activo,
    });
    const [editorState, setEditorState] = useState<EditorState>(() =>
        createEditorStateFromHtml(plantilla.contenido_html),
    );

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(`/admin/plantillas-documentos-trabajador/${plantilla.id}`);
    };

    const appendVariable = (variable: string) => {
        const textToInsert = ` ${variable} `;
        const currentContent = editorState.getCurrentContent();
        const currentSelection = editorState.getSelection();
        const contentWithVariable = Modifier.insertText(
            currentContent,
            currentSelection,
            textToInsert,
        );
        const nextEditorState = EditorState.push(
            editorState,
            contentWithVariable,
            'insert-characters',
        );
        const selectionAfterInsert = contentWithVariable.getSelectionAfter();
        const focusedEditorState = EditorState.forceSelection(
            nextEditorState,
            selectionAfterInsert,
        );
        setEditorState(focusedEditorState);
        setData(
            'contenido_html',
            draftToHtml(convertToRaw(nextEditorState.getCurrentContent())),
        );
    };

    const handleEditorStateChange = (nextEditorState: EditorState) => {
        setEditorState(nextEditorState);
        setData(
            'contenido_html',
            draftToHtml(convertToRaw(nextEditorState.getCurrentContent())),
        );
    };

    return (
        <>
            <Head title="Editar Plantilla de Firma" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/admin/plantillas-documentos-trabajador">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Editar Plantilla</h1>
                        <p className="text-muted-foreground">
                            Actualiza la estructura y contenido del documento firmado.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la plantilla</CardTitle>
                        <CardDescription>
                            Define contenido enriquecido, variables dinámicas y formato de salida PDF.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="nombre">Nombre</Label>
                                    <Input
                                        id="nombre"
                                        value={data.nombre}
                                        onChange={(event) => setData('nombre', event.target.value)}
                                        required
                                    />
                                    {errors.nombre && <p className="text-sm text-destructive">{errors.nombre}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tipo_documento_id">Tipo de documento</Label>
                                    <Select
                                        value={data.tipo_documento_id}
                                        onValueChange={(value) => setData('tipo_documento_id', value)}
                                    >
                                        <SelectTrigger id="tipo_documento_id">
                                            <SelectValue placeholder="Seleccione tipo de documento" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {tiposDocumentos.map((tipoDocumento) => (
                                                <SelectItem
                                                    key={tipoDocumento.value}
                                                    value={tipoDocumento.value.toString()}
                                                >
                                                    {tipoDocumento.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.tipo_documento_id && (
                                        <p className="text-sm text-destructive">{errors.tipo_documento_id}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                                <div className="space-y-2">
                                    <Label htmlFor="fuente_nombre">Fuente PDF</Label>
                                    <Select
                                        value={data.fuente_nombre}
                                        onValueChange={(value) => setData('fuente_nombre', value)}
                                    >
                                        <SelectTrigger id="fuente_nombre">
                                            <SelectValue placeholder="Seleccione fuente" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {fontOptions.map((fontOption) => (
                                                <SelectItem key={fontOption.value} value={fontOption.value}>
                                                    {fontOption.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.fuente_nombre && (
                                        <p className="text-sm text-destructive">{errors.fuente_nombre}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="fuente_tamano">Tamaño fuente (pt)</Label>
                                    <Input
                                        id="fuente_tamano"
                                        type="number"
                                        min={9}
                                        max={18}
                                        value={data.fuente_tamano}
                                        onChange={(event) => setData('fuente_tamano', event.target.value)}
                                    />
                                    {errors.fuente_tamano && (
                                        <p className="text-sm text-destructive">{errors.fuente_tamano}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="color_texto">Color texto</Label>
                                    <Input
                                        id="color_texto"
                                        type="color"
                                        value={data.color_texto}
                                        onChange={(event) => setData('color_texto', event.target.value)}
                                    />
                                    {errors.color_texto && (
                                        <p className="text-sm text-destructive">{errors.color_texto}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="formato_papel">Formato papel PDF</Label>
                                    <Select
                                        value={data.formato_papel}
                                        onValueChange={(value) => setData('formato_papel', value)}
                                    >
                                        <SelectTrigger id="formato_papel">
                                            <SelectValue placeholder="Seleccione formato" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {paperOptions.map((paperOption) => (
                                                <SelectItem key={paperOption.value} value={paperOption.value}>
                                                    {paperOption.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.formato_papel && (
                                        <p className="text-sm text-destructive">{errors.formato_papel}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-3">
                                    <Label>Contenido de plantilla (Editor enriquecido)</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {availableVariables.map((variable) => (
                                            <Button
                                                key={variable}
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => appendVariable(variable)}
                                            >
                                                <Braces className="mr-1 h-3.5 w-3.5" />
                                                {variable}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                                <div className="rounded-md border border-border bg-background p-2">
                                    <Editor
                                        editorState={editorState}
                                        onEditorStateChange={handleEditorStateChange}
                                        toolbar={editorToolbar}
                                        editorStyle={{ minHeight: 320, padding: '0 8px' }}
                                        toolbarClassName="border-b border-border"
                                        wrapperClassName="w-full"
                                        placeholder="Diseña la plantilla del documento y agrega variables con los botones."
                                    />
                                </div>
                                {errors.contenido_html && (
                                    <p className="text-sm text-destructive">{errors.contenido_html}</p>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="activo"
                                    checked={data.activo}
                                    onCheckedChange={(checked) => setData('activo', Boolean(checked))}
                                />
                                <Label htmlFor="activo">Plantilla activa</Label>
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Link href="/admin/plantillas-documentos-trabajador">
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

PlantillaDocumentoTrabajadorEdit.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
