import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Eraser, PenSquare, Save } from 'lucide-react';
import {
    type FormEventHandler,
    type ReactElement,
    type PointerEvent as ReactPointerEvent,
    useEffect,
    useRef,
    useState,
} from 'react';

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
    {
        title: `Trabajador ${trabajadorId}`,
        href: `/trabajadores/${trabajadorId}`,
    },
    {
        title: 'Firmas Digitales',
        href: `/trabajadores/${trabajadorId}/firmas-documentos`,
    },
    {
        title: 'Captura de Firma',
        href: `/trabajadores/${trabajadorId}/firmas-documentos`,
    },
];

type SignaturePointerEvent = ReactPointerEvent<HTMLCanvasElement>;

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

export default function FirmasDocumentosTrabajadorCreate({
    trabajador,
    plantilla,
    availableVariables,
}: Props) {
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const contextRef = useRef<CanvasRenderingContext2D | null>(null);
    const isDrawingRef = useRef(false);
    const [hasSignature, setHasSignature] = useState(false);
    const [signatureError, setSignatureError] = useState<string | null>(null);
    const [isSignatureModalOpen, setIsSignatureModalOpen] = useState(false);
    const [capturedSignatureUrl, setCapturedSignatureUrl] = useState('');
    const { data, setData, post, processing, errors } = useForm({
        signature_data_url: '',
    });

    useEffect(() => {
        if (!isSignatureModalOpen) {
            return;
        }

        const animationFrame = window.requestAnimationFrame(() => {
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
            context.lineWidth = 3;
            context.clearRect(0, 0, canvas.width, canvas.height);
            contextRef.current = context;
        });

        return () => {
            window.cancelAnimationFrame(animationFrame);
        };
    }, [isSignatureModalOpen]);

    const getCoordinates = (
        event: SignaturePointerEvent,
    ): { x: number; y: number } | null => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return null;
        }

        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;

        return {
            x: (event.clientX - rect.left) * scaleX,
            y: (event.clientY - rect.top) * scaleY,
        };
    };

    const startDrawing = (event: SignaturePointerEvent): void => {
        event.preventDefault();
        event.currentTarget.setPointerCapture(event.pointerId);
        const coordinates = getCoordinates(event);
        const context = contextRef.current;

        if (!coordinates || !context) {
            return;
        }

        context.beginPath();
        context.moveTo(coordinates.x, coordinates.y);
        context.lineTo(coordinates.x, coordinates.y);
        context.stroke();
        isDrawingRef.current = true;
        setHasSignature(true);
        setSignatureError(null);
    };

    const draw = (event: SignaturePointerEvent): void => {
        event.preventDefault();
        if (!isDrawingRef.current) {
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
        if (!isDrawingRef.current) {
            return;
        }

        contextRef.current?.closePath();
        isDrawingRef.current = false;
    };

    const stopDrawingWithPointer = (event: SignaturePointerEvent): void => {
        if (event.currentTarget.hasPointerCapture(event.pointerId)) {
            event.currentTarget.releasePointerCapture(event.pointerId);
        }

        stopDrawing();
    };

    const clearSignature = (): void => {
        const canvas = canvasRef.current;
        const context = contextRef.current;

        if (!canvas || !context) {
            return;
        }

        context.clearRect(0, 0, canvas.width, canvas.height);
        isDrawingRef.current = false;
        setHasSignature(false);
        setSignatureError(null);
    };

    const clearCapturedSignature = (): void => {
        setCapturedSignatureUrl('');
        setData('signature_data_url', '');
        clearSignature();
    };

    const confirmSignature = (): void => {
        const canvas = canvasRef.current;

        if (!canvas || !hasSignature) {
            setSignatureError('Debe capturar la firma antes de confirmar.');

            return;
        }

        const signatureDataUrl = canvas.toDataURL('image/png');
        setCapturedSignatureUrl(signatureDataUrl);
        setData('signature_data_url', signatureDataUrl);
        setSignatureError(null);
        setIsSignatureModalOpen(false);
    };

    const openSignatureModal = (): void => {
        isDrawingRef.current = false;
        setHasSignature(false);
        setSignatureError(null);
        setIsSignatureModalOpen(true);
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        if (!data.signature_data_url) {
            setSignatureError('Debe capturar la firma antes de guardar.');

            return;
        }

        post(
            `/trabajadores/${trabajador.id}/firmas-documentos/${plantilla.id}`,
            {
                preserveScroll: true,
            },
        );
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
                    <Link
                        href={`/trabajadores/${trabajador.id}/firmas-documentos`}
                    >
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
                            {plantilla.tipo_documento_nombre} (
                            {plantilla.tipo_documento_codigo})
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            Trabajador: {trabajador.nombre_completo} (
                            {trabajador.documento})
                        </p>
                        <div className="rounded-md border border-border bg-muted/20 p-3">
                            <p className="text-xs font-medium text-muted-foreground">
                                Variables soportadas
                            </p>
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
                            Revise el contenido final antes de capturar la
                            firma. Formato PDF:{' '}
                            {plantilla.formato_papel.toUpperCase()}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto rounded-md border border-dashed border-border bg-muted/30 p-4">
                            <div
                                className="mx-auto bg-white shadow-sm"
                                style={previewPaperStyle}
                            >
                                <div
                                    className="document-preview-content max-w-none"
                                    style={previewDocumentStyle}
                                    dangerouslySetInnerHTML={{
                                        __html: plantilla.rendered_html,
                                    }}
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
                            Abra el modal de firma para capturarla con más
                            espacio y sin que el scroll interfiera en tablet.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="rounded-lg border border-dashed border-border bg-muted/20 p-4">
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium text-foreground">
                                            Firma del trabajador
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {capturedSignatureUrl
                                                ? 'La firma fue capturada y está lista para generar el PDF.'
                                                : 'Abra el modal para firmar con una superficie amplia.'}
                                        </p>
                                    </div>
                                    <div className="flex flex-col gap-2 sm:flex-row">
                                        <Button
                                            type="button"
                                            variant={
                                                capturedSignatureUrl
                                                    ? 'outline'
                                                    : 'default'
                                            }
                                            onClick={openSignatureModal}
                                        >
                                            <PenSquare className="mr-2 h-4 w-4" />
                                            {capturedSignatureUrl
                                                ? 'Volver a firmar'
                                                : 'Abrir modal de firma'}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={clearCapturedSignature}
                                            disabled={
                                                !capturedSignatureUrl ||
                                                processing
                                            }
                                        >
                                            <Eraser className="mr-2 h-4 w-4" />
                                            Limpiar firma
                                        </Button>
                                    </div>
                                </div>

                                <div className="mt-4 overflow-hidden rounded-md border border-border bg-white">
                                    {capturedSignatureUrl ? (
                                        <img
                                            src={capturedSignatureUrl}
                                            alt="Vista previa de la firma"
                                            className="h-40 w-full object-contain"
                                        />
                                    ) : (
                                        <div className="flex h-40 items-center justify-center px-6 text-center text-sm text-muted-foreground">
                                            Aún no hay una firma confirmada.
                                        </div>
                                    )}
                                </div>
                            </div>

                            {(signatureError || errors.signature_data_url) && (
                                <p className="text-sm text-destructive">
                                    {signatureError ||
                                        errors.signature_data_url}
                                </p>
                            )}

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    Guardar firma y generar PDF
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={isSignatureModalOpen}
                onOpenChange={setIsSignatureModalOpen}
            >
                <DialogContent className="flex h-[92vh] max-h-[92vh] w-[96vw] max-w-5xl flex-col gap-0 overflow-hidden p-0 sm:h-[88vh]">
                    <DialogHeader className="border-b border-border px-6 py-4">
                        <DialogTitle>Captura de firma</DialogTitle>
                        <DialogDescription>
                            Pida al trabajador que firme dentro del recuadro. El
                            modal reduce el scroll accidental en tablet.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex-1 overflow-hidden bg-muted/30 p-4 sm:p-6">
                        <div className="flex h-full flex-col gap-4">
                            <div className="rounded-md border border-border bg-background px-4 py-3 text-sm text-muted-foreground">
                                Trabajador:{' '}
                                <span className="font-medium text-foreground">
                                    {trabajador.nombre_completo}
                                </span>
                            </div>

                            <div className="flex-1 overflow-hidden rounded-xl border border-border bg-white shadow-sm">
                                <canvas
                                    ref={canvasRef}
                                    width={1600}
                                    height={700}
                                    className="h-full min-h-[320px] w-full bg-white"
                                    style={{
                                        touchAction: 'none',
                                        cursor: 'crosshair',
                                    }}
                                    onPointerDown={startDrawing}
                                    onPointerMove={draw}
                                    onPointerUp={stopDrawingWithPointer}
                                    onPointerLeave={stopDrawingWithPointer}
                                    onPointerCancel={stopDrawingWithPointer}
                                />
                            </div>
                        </div>
                    </div>

                    <DialogFooter className="border-t border-border px-6 py-4 sm:justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={clearSignature}
                            disabled={processing}
                        >
                            <Eraser className="mr-2 h-4 w-4" />
                            Limpiar trazo
                        </Button>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsSignatureModalOpen(false)}
                                disabled={processing}
                            >
                                Cerrar
                            </Button>
                            <Button
                                type="button"
                                onClick={confirmSignature}
                                disabled={processing}
                            >
                                Confirmar firma
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

FirmasDocumentosTrabajadorCreate.layout = (page: ReactElement<Props>) => (
    <AppLayout breadcrumbs={breadcrumbs(page.props.trabajador.id)}>
        {page}
    </AppLayout>
);
