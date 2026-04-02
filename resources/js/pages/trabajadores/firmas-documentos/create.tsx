import { type FormEventHandler, type MouseEvent, type TouchEvent, useEffect, useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Eraser, Save } from 'lucide-react';
import { type ReactElement } from 'react';

interface TrabajadorData {
    id: string;
    documento: string;
    nombre_completo: string;
}

interface PlantillaData {
    id: number;
    nombre: string;
    tipo_documento_nombre: string | null;
    tipo_documento_codigo: string | null;
    rendered_html: string;
    fuente_tamano: number;
    color_texto: string;
    formato_papel: string;
    font_family: string;
}

interface Props {
    trabajador: TrabajadorData;
    plantilla: PlantillaData;
    availableVariables: string[];
}

interface PaperPreviewSpec {
    width: string;
    minHeight: string;
    padding: string;
}

const breadcrumbs = (trabajadorId: string): BreadcrumbItem[] => [
    { title: 'Personal', href: '/trabajadores' },
    { title: `Trabajador ${trabajadorId}`, href: `/trabajadores/${trabajadorId}` },
    { title: 'Firmas Digitales', href: `/trabajadores/${trabajadorId}/firmas-documentos` },
    {
        title: 'Captura de Firma',
        href: `/trabajadores/${trabajadorId}/firmas-documentos`,
    },
];

type SignaturePointerEvent = MouseEvent<HTMLCanvasElement> | TouchEvent<HTMLCanvasElement>;

const paperPreviewSpec = (paperFormat: string): PaperPreviewSpec => {
    if (paperFormat.toLowerCase() === 'a4') {
        return {
            width: '210mm',
            minHeight: '297mm',
            padding: '16mm 14mm',
        };
    }

    return {
        width: '8.5in',
        minHeight: '11in',
        padding: '14mm 12mm',
    };
};

export default function FirmasDocumentosTrabajadorCreate({ trabajador, plantilla, availableVariables }: Props) {
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const contextRef = useRef<CanvasRenderingContext2D | null>(null);
    const [isDrawing, setIsDrawing] = useState(false);
    const [hasSignature, setHasSignature] = useState(false);
    const [signatureError, setSignatureError] = useState<string | null>(null);
    const { post, processing, errors, transform } = useForm({
        signature_data_url: '',
    });

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return;
        }

        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.strokeStyle = '#111827';
        context.lineWidth = 2.5;
        contextRef.current = context;
    }, []);

    const getCoordinates = (event: SignaturePointerEvent): { x: number; y: number } | null => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return null;
        }

        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;

        if ('touches' in event) {
            if (event.touches.length === 0) {
                return null;
            }

            const touch = event.touches[0];

            return {
                x: (touch.clientX - rect.left) * scaleX,
                y: (touch.clientY - rect.top) * scaleY,
            };
        }

        return {
            x: (event.clientX - rect.left) * scaleX,
            y: (event.clientY - rect.top) * scaleY,
        };
    };

    const startDrawing = (event: SignaturePointerEvent): void => {
        event.preventDefault();
        const coordinates = getCoordinates(event);
        const context = contextRef.current;

        if (!coordinates || !context) {
            return;
        }

        context.beginPath();
        context.moveTo(coordinates.x, coordinates.y);
        setIsDrawing(true);
        setSignatureError(null);
    };

    const draw = (event: SignaturePointerEvent): void => {
        event.preventDefault();
        if (!isDrawing) {
            return;
        }

        const coordinates = getCoordinates(event);
        const context = contextRef.current;

        if (!coordinates || !context) {
            return;
        }

        context.lineTo(coordinates.x, coordinates.y);
        context.stroke();
        setHasSignature(true);
    };

    const stopDrawing = (): void => {
        if (!isDrawing) {
            return;
        }

        contextRef.current?.closePath();
        setIsDrawing(false);
    };

    const clearSignature = (): void => {
        const canvas = canvasRef.current;
        const context = contextRef.current;

        if (!canvas || !context) {
            return;
        }

        context.clearRect(0, 0, canvas.width, canvas.height);
        setHasSignature(false);
        setSignatureError(null);
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        const canvas = canvasRef.current;

        if (!canvas || !hasSignature) {
            setSignatureError('Debe capturar la firma antes de guardar.');

            return;
        }

        const signatureDataUrl = canvas.toDataURL('image/png');
        transform(() => ({
            signature_data_url: signatureDataUrl,
        }));
        post(`/trabajadores/${trabajador.id}/firmas-documentos/${plantilla.id}`, {
            onFinish: () => {
                transform((data) => data);
            },
        });
    };

    const documentPreviewStyle = {
        fontFamily: `${plantilla.font_family}, sans-serif`,
        color: plantilla.color_texto,
        fontSize: `${plantilla.fuente_tamano}px`,
        lineHeight: 1.5,
    } as const;
    const previewPaperSpec = paperPreviewSpec(plantilla.formato_papel);
    const previewPaperStyle = {
        width: previewPaperSpec.width,
        minHeight: previewPaperSpec.minHeight,
    } as const;
    const previewDocumentStyle = {
        ...documentPreviewStyle,
        padding: previewPaperSpec.padding,
    } as const;

    return (
        <>
            <Head title={`Firmar documento - ${trabajador.nombre_completo}`} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <Link href={`/trabajadores/${trabajador.id}/firmas-documentos`}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{plantilla.nombre}</CardTitle>
                        <CardDescription>
                            {plantilla.tipo_documento_nombre} ({plantilla.tipo_documento_codigo})
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            Trabajador: {trabajador.nombre_completo} ({trabajador.documento})
                        </p>
                        <div className="rounded-md border border-border bg-muted/20 p-3">
                            <p className="text-xs font-medium text-muted-foreground">Variables soportadas</p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {availableVariables.map((variable) => (
                                    <span
                                        key={variable}
                                        className="rounded border border-border bg-background px-2 py-1 text-xs"
                                    >
                                        {variable}
                                    </span>
                                ))}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Documento a firmar</CardTitle>
                        <CardDescription>
                            Revise el contenido final antes de capturar la firma. Formato PDF: {plantilla.formato_papel.toUpperCase()}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto rounded-md border border-dashed border-border bg-muted/30 p-4">
                            <div className="mx-auto bg-white shadow-sm" style={previewPaperStyle}>
                                <div
                                    className="document-preview-content max-w-none"
                                    style={previewDocumentStyle}
                                    dangerouslySetInnerHTML={{ __html: plantilla.rendered_html }}
                                />
                            </div>
                        </div>
                        <style>
                            {`
                                .document-preview-content,
                                .document-preview-content * {
                                    box-sizing: border-box;
                                    max-width: 100%;
                                }

                                .document-preview-content p,
                                .document-preview-content li,
                                .document-preview-content div,
                                .document-preview-content span {
                                    overflow-wrap: anywhere;
                                    word-break: break-word;
                                }

                                .document-preview-content pre,
                                .document-preview-content code {
                                    white-space: pre-wrap;
                                    overflow-wrap: anywhere;
                                    word-break: break-word;
                                }

                                .document-preview-content table {
                                    width: 100%;
                                    table-layout: fixed;
                                    border-collapse: collapse;
                                }

                                .document-preview-content th,
                                .document-preview-content td {
                                    overflow-wrap: anywhere;
                                    word-break: break-word;
                                    vertical-align: top;
                                }

                                .document-preview-content img,
                                .document-preview-content svg,
                                .document-preview-content canvas {
                                    max-width: 100%;
                                    height: auto;
                                }
                            `}
                        </style>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Captura de firma</CardTitle>
                        <CardDescription>
                            El trabajador debe firmar en el recuadro. La fecha de firma se fija al guardar.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="overflow-hidden rounded-md border border-border">
                                <canvas
                                    ref={canvasRef}
                                    width={1200}
                                    height={300}
                                    className="h-56 w-full bg-white"
                                    onMouseDown={startDrawing}
                                    onMouseMove={draw}
                                    onMouseUp={stopDrawing}
                                    onMouseLeave={stopDrawing}
                                    onTouchStart={startDrawing}
                                    onTouchMove={draw}
                                    onTouchEnd={stopDrawing}
                                />
                            </div>

                            {(signatureError || errors.signature_data_url) && (
                                <p className="text-sm text-destructive">
                                    {signatureError || errors.signature_data_url}
                                </p>
                            )}

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    Guardar firma y generar PDF
                                </Button>
                                <Button type="button" variant="outline" onClick={clearSignature} disabled={processing}>
                                    <Eraser className="mr-2 h-4 w-4" />
                                    Limpiar
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

FirmasDocumentosTrabajadorCreate.layout = (page: ReactElement<Props>) => (
    <AppLayout breadcrumbs={breadcrumbs(page.props.trabajador.id)}>{page}</AppLayout>
);
