import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import {
    DndContext,
    type DragEndEvent,
    useDraggable,
    useDroppable,
} from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    CircleDashed,
    ClipboardCheck,
    FileText,
    LoaderCircle,
    Search,
    Sparkles,
    Trash2,
    UploadCloud,
} from 'lucide-react';
import {
    type ChangeEvent,
    type DragEvent,
    type ReactNode,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import Tesseract from 'tesseract.js';

interface TrabajadorOption {
    id: string;
    documento: string;
    nombre: string;
    apellido: string;
    nombre_completo: string;
    estado: string;
}

interface TipoDocumentoRequirement {
    id: number;
    nombre: string;
    codigo: string;
    es_obligatorio: boolean;
    permite_multiples_en_mes: boolean;
    formatos_permitidos: string[] | null;
    tamano_maximo_kb: number;
    dias_vencimiento: number | null;
    palabras_clave: string[];
}

interface RequirementsResponse {
    tipos_documentos: TipoDocumentoRequirement[];
    tipos_documentos_cargados: number[];
    sin_faena_activa: boolean;
}

interface OCRSuggestion {
    tipoDocumentoId: number;
    label: string;
    score: number;
}

interface CargaArchivoItem {
    id: string;
    file: File;
    previewUrl: string | null;
    ocrStatus: 'queued' | 'processing' | 'done' | 'failed';
    ocrProgress: number;
    uploadStatus: 'idle' | 'uploading' | 'success' | 'error';
    uploadProgress: number;
    extractedText: string;
    suggestion: OCRSuggestion | null;
    matchedTipoDocumentoId: number | null;
    expiryDate: string;
    errorMessage: string | null;
}

interface Props {
    initialTrabajador: TrabajadorOption | null;
    initialRequirements: RequirementsResponse;
}

interface SearchTrabajadoresResponse {
    data: TrabajadorOption[];
}

interface UploadResponse {
    message: string;
    data: {
        id: number;
        trabajador_id: string;
        tipo_documento_id: number;
        archivo_nombre_original: string;
        fecha_vencimiento: string | null;
    };
}

interface OCRLoggerMessage {
    status: string;
    progress: number;
    userJobId?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Personal', href: '/trabajadores' },
    { title: 'Centro de Carga', href: '/centro-carga' },
];

const ACCEPTED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'] as const;
const DEFAULT_OCR_WORKER_COUNT = 2;
const MAX_OCR_WORKER_COUNT = 4;
const OCR_WORKER_COUNT = (() => {
    const rawValue = Number(import.meta.env.VITE_OCR_WORKERS);

    if (!Number.isInteger(rawValue) || rawValue < 1) {
        return DEFAULT_OCR_WORKER_COUNT;
    }

    return Math.min(rawValue, MAX_OCR_WORKER_COUNT);
})();
const OCR_JOB_SEPARATOR = '::';

function normalizeText(value: string): string {
    return value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function dateToIso(value: string): string | null {
    const match = value.match(/^(\d{2})[./-](\d{2})[./-](\d{4})$/);

    if (!match) {
        return null;
    }

    const [, day, month, year] = match;
    const dayNumber = Number(day);
    const monthNumber = Number(month);
    const yearNumber = Number(year);

    if (
        monthNumber < 1 ||
        monthNumber > 12 ||
        dayNumber < 1 ||
        dayNumber > 31
    ) {
        return null;
    }

    const date = new Date(Date.UTC(yearNumber, monthNumber - 1, dayNumber));
    if (
        date.getUTCFullYear() !== yearNumber ||
        date.getUTCMonth() !== monthNumber - 1 ||
        date.getUTCDate() !== dayNumber
    ) {
        return null;
    }

    return `${year}-${month}-${day}`;
}

function extractExpiryDate(text: string): string {
    const normalized = normalizeText(text);
    const patterns = [
        /(vencimiento|expiracion|expira|vigencia|validez|vence)[^0-9]{0,24}(\d{2}[./-]\d{2}[./-]\d{4})/i,
        /(\d{2}[./-]\d{2}[./-]\d{4})[^a-z0-9]{0,24}(vencimiento|expiracion|expira|vigencia|validez|vence)/i,
    ];

    for (const pattern of patterns) {
        const match = normalized.match(pattern);
        if (!match) {
            continue;
        }

        const dateCandidate = match[2] ?? match[1];
        const isoDate = dateToIso(dateCandidate);
        if (isoDate) {
            return isoDate;
        }
    }

    return '';
}

function tokenizeKeywords(value: string): string[] {
    return normalizeText(value)
        .split(/[^a-z0-9]+/g)
        .map((token) => token.trim())
        .filter((token) => token.length >= 3);
}

function buildKeywordsForRequirement(
    requirement: TipoDocumentoRequirement,
): string[] {
    const serverKeywords =
        requirement.palabras_clave?.map((keyword) => normalizeText(keyword)) ??
        [];
    const fallbackKeywords = [
        ...tokenizeKeywords(requirement.nombre),
        ...tokenizeKeywords(requirement.codigo),
    ];

    return Array.from(new Set([...serverKeywords, ...fallbackKeywords])).filter(
        (keyword) => keyword.length >= 3,
    );
}

function suggestDocumentType(
    extractedText: string,
    requirements: TipoDocumentoRequirement[],
): OCRSuggestion | null {
    const normalizedText = normalizeText(extractedText);

    if (!normalizedText) {
        return null;
    }

    let bestMatch: OCRSuggestion | null = null;

    for (const requirement of requirements) {
        const keywords = buildKeywordsForRequirement(requirement);
        const score = keywords.reduce((total, keyword) => {
            if (!normalizedText.includes(keyword)) {
                return total;
            }

            return total + (keyword.includes(' ') ? 2 : 1);
        }, 0);

        if (score <= 0) {
            continue;
        }

        if (!bestMatch || score > bestMatch.score) {
            bestMatch = {
                tipoDocumentoId: requirement.id,
                label: requirement.nombre,
                score,
            };
        }
    }

    return bestMatch;
}

function isAcceptedFile(file: File): boolean {
    const extension = file.name.split('.').pop()?.toLowerCase();
    return (
        !!extension &&
        ACCEPTED_EXTENSIONS.includes(
            extension as (typeof ACCEPTED_EXTENSIONS)[number],
        )
    );
}

function isPdfFile(file: File): boolean {
    const extension = file.name.split('.').pop()?.toLowerCase();
    return extension === 'pdf' || file.type.toLowerCase().includes('pdf');
}

function buildOCRJobId(generation: number, itemId: string): string {
    return `${generation}${OCR_JOB_SEPARATOR}${itemId}`;
}

function parseOCRJobId(
    rawJobId?: string,
): { generation: number; itemId: string } | null {
    if (!rawJobId || !rawJobId.includes(OCR_JOB_SEPARATOR)) {
        return null;
    }

    const [generationToken, itemId] = rawJobId.split(OCR_JOB_SEPARATOR);
    const generation = Number(generationToken);
    if (itemId.length === 0 || Number.isNaN(generation)) {
        return null;
    }

    return {
        generation,
        itemId,
    };
}

function getCookieValue(name: string): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = document.cookie.match(
        new RegExp(`(?:^|;\\s*)${escapedName}=([^;]*)`),
    );

    if (!match) {
        return '';
    }

    return decodeURIComponent(match[1]);
}

function getCsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function readableFileSize(kb: number): string {
    if (kb < 1024) {
        return `${kb.toFixed(0)} KB`;
    }

    return `${(kb / 1024).toFixed(2)} MB`;
}

function ProgressBar({
    value,
    className,
}: {
    value: number;
    className?: string;
}) {
    const safeValue = Math.max(0, Math.min(100, value));

    return (
        <div
            className={cn(
                'h-2 w-full overflow-hidden rounded-full bg-muted/80',
                className,
            )}
        >
            <div
                className="h-full rounded-full bg-gradient-to-r from-[var(--brand-green)] via-[var(--brand-lime)] to-[var(--brand-orange)] transition-all duration-300"
                style={{ width: `${safeValue}%` }}
            />
        </div>
    );
}

function RequirementTarget({
    requirement,
    isUploaded,
    matchedCount,
}: {
    requirement: TipoDocumentoRequirement;
    isUploaded: boolean;
    matchedCount: number;
}) {
    const { setNodeRef, isOver } = useDroppable({
        id: `target-${requirement.id}`,
        disabled: isUploaded,
    });

    return (
        <div
            ref={setNodeRef}
            className={cn(
                'rounded-xl border bg-white/75 p-3 transition',
                isUploaded
                    ? 'border-[var(--brand-green)]/35 bg-[var(--brand-lime)]/10'
                    : 'border-border/70',
                isOver &&
                    !isUploaded &&
                    'border-[var(--brand-orange)] bg-[var(--brand-orange)]/10',
            )}
        >
            <div className="flex items-start justify-between gap-2">
                <div>
                    <p className="text-sm font-semibold text-foreground">
                        {requirement.nombre}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {requirement.codigo}
                    </p>
                </div>
                {isUploaded ? (
                    <Badge className="bg-[var(--brand-green)] text-[var(--primary-foreground)]">
                        Cargado
                    </Badge>
                ) : requirement.es_obligatorio ? (
                    <Badge variant="secondary">Obligatorio</Badge>
                ) : (
                    <Badge variant="outline">Opcional</Badge>
                )}
            </div>

            <div className="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                <span>
                    Max: {readableFileSize(requirement.tamano_maximo_kb)}
                </span>
                <span>{matchedCount} archivo(s) asignado(s)</span>
            </div>
        </div>
    );
}

function UploadFileCard({
    item,
    requirements,
    uploadedTipoIds,
    onRemove,
    onConfirmSuggestion,
    onMatchChange,
    onExpiryChange,
}: {
    item: CargaArchivoItem;
    requirements: TipoDocumentoRequirement[];
    uploadedTipoIds: Set<number>;
    onRemove: (id: string) => void;
    onConfirmSuggestion: (id: string) => void;
    onMatchChange: (id: string, tipoDocumentoId: number | null) => void;
    onExpiryChange: (id: string, expiryDate: string) => void;
}) {
    const { attributes, listeners, setNodeRef, transform, isDragging } =
        useDraggable({
            id: `file-${item.id}`,
        });

    const transformStyle = transform
        ? { transform: CSS.Translate.toString(transform) }
        : undefined;

    return (
        <article
            ref={setNodeRef}
            style={transformStyle}
            className={cn(
                'rounded-xl border bg-white/80 p-3 transition',
                item.suggestion
                    ? 'border-[var(--brand-orange)]/45 shadow-md'
                    : 'border-border/70',
                isDragging && 'opacity-70 ring-2 ring-[var(--brand-lime)]/50',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <button
                    type="button"
                    className="inline-flex cursor-grab items-center gap-2 rounded-lg border border-border/70 bg-background/80 px-2 py-1 text-xs text-muted-foreground active:cursor-grabbing"
                    {...listeners}
                    {...attributes}
                >
                    <FileText className="size-3.5" />
                    Arrastrar
                </button>
                <button
                    type="button"
                    onClick={() => onRemove(item.id)}
                    className="rounded-lg border border-border/70 p-1.5 text-muted-foreground transition hover:bg-muted"
                >
                    <Trash2 className="size-3.5" />
                </button>
            </div>

            <div className="mt-2">
                <p className="truncate text-sm font-semibold text-foreground">
                    {item.file.name}
                </p>
                <p className="text-xs text-muted-foreground">
                    {(item.file.size / 1024).toFixed(0)} KB ·{' '}
                    {item.file.type || 'tipo no informado'}
                </p>
            </div>

            <div className="mt-3 overflow-hidden rounded-lg border border-border/60 bg-muted/20">
                {item.previewUrl ? (
                    isPdfFile(item.file) ? (
                        <object
                            data={item.previewUrl}
                            type="application/pdf"
                            className="h-44 w-full md:h-56"
                        >
                            <div className="flex h-44 items-center justify-center text-xs font-semibold text-muted-foreground md:h-56">
                                Vista previa PDF no disponible en este navegador
                            </div>
                        </object>
                    ) : (
                        <img
                            src={item.previewUrl}
                            alt={item.file.name}
                            className="h-44 w-full object-cover md:h-56"
                        />
                    )
                ) : (
                    <div className="flex h-44 items-center justify-center text-xs font-semibold text-muted-foreground md:h-56">
                        Vista previa no disponible
                    </div>
                )}
            </div>

            {item.suggestion && (
                <div className="mt-3 rounded-lg border border-[var(--brand-orange)]/35 bg-[var(--brand-orange)]/10 px-3 py-2">
                    <p className="text-xs font-semibold text-[var(--brand-orange-strong)]">
                        Parece ser: {item.suggestion.label}
                    </p>
                    <div className="mt-2 flex items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => onConfirmSuggestion(item.id)}
                            disabled={uploadedTipoIds.has(
                                item.suggestion.tipoDocumentoId,
                            )}
                        >
                            Confirmar sugerencia
                        </Button>
                    </div>
                </div>
            )}

            <div className="mt-3 space-y-2 text-xs">
                <div className="flex items-center justify-between">
                    <span className="text-muted-foreground">OCR</span>
                    <span className="font-medium text-foreground">
                        {item.ocrStatus === 'queued' && 'En cola'}
                        {item.ocrStatus === 'processing' && 'Procesando'}
                        {item.ocrStatus === 'done' && 'Listo'}
                        {item.ocrStatus === 'failed' && 'Falló'}
                    </span>
                </div>
                <ProgressBar value={item.ocrProgress} />
            </div>

            <div className="mt-3 space-y-2 text-xs">
                <div className="flex items-center justify-between">
                    <span className="text-muted-foreground">Carga</span>
                    <span className="font-medium text-foreground">
                        {item.uploadStatus === 'idle' && 'Pendiente'}
                        {item.uploadStatus === 'uploading' && 'Subiendo'}
                        {item.uploadStatus === 'success' && 'Cargado'}
                        {item.uploadStatus === 'error' && 'Error'}
                    </span>
                </div>
                <ProgressBar value={item.uploadProgress} />
            </div>

            <div className="mt-3 space-y-2">
                <Label className="text-xs font-semibold text-muted-foreground">
                    Clasificación manual
                </Label>
                <select
                    value={item.matchedTipoDocumentoId?.toString() ?? ''}
                    onChange={(event) =>
                        onMatchChange(
                            item.id,
                            event.target.value
                                ? Number(event.target.value)
                                : null,
                        )
                    }
                    className="w-full rounded-md border border-input bg-background px-2 py-2 text-sm"
                >
                    <option value="">Sin asignar</option>
                    {requirements.map((requirement) => (
                        <option
                            key={requirement.id}
                            value={requirement.id}
                            disabled={uploadedTipoIds.has(requirement.id)}
                        >
                            {requirement.nombre}
                        </option>
                    ))}
                </select>
            </div>

            <div className="mt-3 space-y-2">
                <Label
                    htmlFor={`expiry-${item.id}`}
                    className="text-xs font-semibold text-muted-foreground"
                >
                    Fecha de vencimiento (opcional)
                </Label>
                <Input
                    id={`expiry-${item.id}`}
                    type="date"
                    value={item.expiryDate}
                    onChange={(event) =>
                        onExpiryChange(item.id, event.target.value)
                    }
                />
            </div>

            {item.extractedText && (
                <p className="mt-3 line-clamp-3 rounded-lg border border-border/60 bg-muted/40 px-2 py-1.5 text-xs text-muted-foreground">
                    {item.extractedText}
                </p>
            )}

            {item.errorMessage && (
                <p className="mt-3 rounded-lg border border-destructive/40 bg-destructive/10 px-2 py-1.5 text-xs text-destructive">
                    {item.errorMessage}
                </p>
            )}
        </article>
    );
}

export default function CentroCarga({
    initialTrabajador,
    initialRequirements,
}: Props) {
    const [search, setSearch] = useState(
        initialTrabajador
            ? `${initialTrabajador.documento} · ${initialTrabajador.nombre_completo}`
            : '',
    );
    const [searchResults, setSearchResults] = useState<TrabajadorOption[]>([]);
    const [searching, setSearching] = useState(false);
    const [selectedTrabajador, setSelectedTrabajador] =
        useState<TrabajadorOption | null>(initialTrabajador);
    const [requirements, setRequirements] =
        useState<RequirementsResponse>(initialRequirements);
    const [loadingRequirements, setLoadingRequirements] = useState(false);
    const [items, setItems] = useState<CargaArchivoItem[]>([]);
    const [dropzoneError, setDropzoneError] = useState('');
    const [uploadingAll, setUploadingAll] = useState(false);

    const itemsRef = useRef<CargaArchivoItem[]>([]);
    const requirementsRef = useRef<RequirementsResponse>(initialRequirements);
    const ocrSchedulerRef = useRef<Tesseract.Scheduler | null>(null);
    const ocrSchedulerPromiseRef = useRef<Promise<Tesseract.Scheduler> | null>(
        null,
    );
    const queuedOCRJobIdsRef = useRef<Set<string>>(new Set());
    const ocrGenerationRef = useRef(0);
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    useEffect(() => {
        itemsRef.current = items;
    }, [items]);

    useEffect(() => {
        requirementsRef.current = requirements;
    }, [requirements]);

    const updateItem = useCallback(
        (
            id: string,
            updater: (item: CargaArchivoItem) => CargaArchivoItem,
        ): void => {
            setItems((previous) =>
                previous.map((item) => (item.id === id ? updater(item) : item)),
            );
        },
        [],
    );

    const terminateOCRScheduler = useCallback((): void => {
        ocrGenerationRef.current += 1;
        queuedOCRJobIdsRef.current.clear();

        const currentScheduler = ocrSchedulerRef.current;
        ocrSchedulerRef.current = null;
        ocrSchedulerPromiseRef.current = null;

        if (currentScheduler) {
            void currentScheduler.terminate().catch(() => null);
        }
    }, []);

    useEffect(() => {
        return () => {
            itemsRef.current.forEach((item) => {
                if (item.previewUrl) {
                    URL.revokeObjectURL(item.previewUrl);
                }
            });

            terminateOCRScheduler();
        };
    }, [terminateOCRScheduler]);

    const getOCRScheduler =
        useCallback(async (): Promise<Tesseract.Scheduler> => {
            if (ocrSchedulerRef.current) {
                return ocrSchedulerRef.current;
            }

            if (ocrSchedulerPromiseRef.current) {
                return ocrSchedulerPromiseRef.current;
            }

            ocrSchedulerPromiseRef.current = (async () => {
                const schedulerGeneration = ocrGenerationRef.current;
                const scheduler = Tesseract.createScheduler();

                const workers = await Promise.all(
                    Array.from({ length: OCR_WORKER_COUNT }, async () =>
                        Tesseract.createWorker(
                            'spa+eng',
                            Tesseract.OEM.LSTM_ONLY,
                            {
                                logger: (message: OCRLoggerMessage) => {
                                    const parsedJob = parseOCRJobId(
                                        message.userJobId,
                                    );
                                    if (
                                        !parsedJob ||
                                        parsedJob.generation !==
                                            ocrGenerationRef.current
                                    ) {
                                        return;
                                    }

                                    if (
                                        message.status
                                            .toLowerCase()
                                            .includes('recognizing')
                                    ) {
                                        updateItem(
                                            parsedJob.itemId,
                                            (current) => ({
                                                ...current,
                                                ocrStatus: 'processing',
                                                ocrProgress: Math.max(
                                                    current.ocrProgress,
                                                    Math.round(
                                                        (message.progress ||
                                                            0) * 100,
                                                    ),
                                                ),
                                            }),
                                        );
                                    }
                                },
                            },
                        ),
                    ),
                );

                workers.forEach((worker) => {
                    scheduler.addWorker(worker);
                });

                if (schedulerGeneration !== ocrGenerationRef.current) {
                    await scheduler.terminate();
                    throw new Error('stale_ocr_scheduler');
                }

                ocrSchedulerRef.current = scheduler;
                ocrSchedulerPromiseRef.current = null;

                return scheduler;
            })().catch((error) => {
                ocrSchedulerPromiseRef.current = null;
                throw error;
            });

            return ocrSchedulerPromiseRef.current;
        }, [updateItem]);

    const processOCRItem = useCallback(
        async ({ id, file }: { id: string; file: File }): Promise<void> => {
            const generation = ocrGenerationRef.current;
            const jobId = buildOCRJobId(generation, id);

            updateItem(id, (item) => ({
                ...item,
                ocrStatus: 'processing',
                ocrProgress: Math.max(item.ocrProgress, 5),
                errorMessage: null,
            }));

            try {
                if (isPdfFile(file)) {
                    const fallbackText = `pdf ${file.name.replace(
                        /[_-]/g,
                        ' ',
                    )}`;
                    const suggestion = suggestDocumentType(
                        fallbackText,
                        requirementsRef.current.tipos_documentos,
                    );

                    updateItem(id, (item) => ({
                        ...item,
                        ocrStatus: 'done',
                        ocrProgress: 100,
                        extractedText:
                            'PDF detectado. OCR de PDF no está habilitado en este motor de navegador, pero puedes confirmar la sugerencia o clasificar manualmente.',
                        suggestion,
                        matchedTipoDocumentoId:
                            item.matchedTipoDocumentoId ??
                            suggestion?.tipoDocumentoId ??
                            null,
                    }));

                    return;
                }

                const scheduler = await getOCRScheduler();
                const result = await scheduler.addJob(
                    'recognize',
                    file,
                    {},
                    { text: true },
                    jobId,
                );

                if (generation !== ocrGenerationRef.current) {
                    return;
                }

                const extractedText = result.data.text ?? '';
                const suggestion = suggestDocumentType(
                    extractedText,
                    requirementsRef.current.tipos_documentos,
                );
                const expiryDate = extractExpiryDate(extractedText);

                updateItem(id, (item) => ({
                    ...item,
                    ocrStatus: 'done',
                    ocrProgress: 100,
                    extractedText,
                    suggestion,
                    matchedTipoDocumentoId:
                        item.matchedTipoDocumentoId ??
                        suggestion?.tipoDocumentoId ??
                        null,
                    expiryDate: item.expiryDate || expiryDate,
                }));
            } catch {
                if (generation !== ocrGenerationRef.current) {
                    return;
                }

                updateItem(id, (item) => ({
                    ...item,
                    ocrStatus: 'failed',
                    ocrProgress: 100,
                    errorMessage:
                        'El OCR no pudo clasificar este archivo. Puedes asignarlo manualmente.',
                }));
            }
        },
        [getOCRScheduler, updateItem],
    );

    const enqueueOCR = useCallback(
        (
            entries: Array<{
                id: string;
                file: File;
            }>,
        ): void => {
            entries.forEach((entry) => {
                if (queuedOCRJobIdsRef.current.has(entry.id)) {
                    return;
                }

                queuedOCRJobIdsRef.current.add(entry.id);
                void processOCRItem(entry).finally(() => {
                    queuedOCRJobIdsRef.current.delete(entry.id);
                });
            });
        },
        [processOCRItem],
    );

    const selectedWorkerId = selectedTrabajador?.id ?? '';
    const uploadedTipoIdsSet = useMemo(
        () => new Set(requirements.tipos_documentos_cargados),
        [requirements.tipos_documentos_cargados],
    );

    useEffect(() => {
        const trimmedSearch = search.trim();

        if (
            selectedTrabajador &&
            trimmedSearch ===
                `${selectedTrabajador.documento} · ${selectedTrabajador.nombre_completo}`
        ) {
            setSearchResults([]);
            return;
        }

        if (trimmedSearch.length < 2) {
            setSearchResults([]);
            return;
        }

        const timeout = setTimeout(async () => {
            setSearching(true);

            try {
                const response = await fetch(
                    `/centro-carga/trabajadores?search=${encodeURIComponent(trimmedSearch)}`,
                    {
                        headers: {
                            Accept: 'application/json',
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error('search_failed');
                }

                const payload =
                    (await response.json()) as SearchTrabajadoresResponse;
                setSearchResults(payload.data);
            } catch {
                setSearchResults([]);
            } finally {
                setSearching(false);
            }
        }, 280);

        return () => clearTimeout(timeout);
    }, [search, selectedTrabajador]);

    const clearItems = useCallback(() => {
        setItems((previous) => {
            previous.forEach((item) => {
                if (item.previewUrl) {
                    URL.revokeObjectURL(item.previewUrl);
                }
            });

            return [];
        });

        terminateOCRScheduler();
    }, [terminateOCRScheduler]);

    const loadRequirements = useCallback(
        async (trabajadorId: string): Promise<void> => {
            setLoadingRequirements(true);

            try {
                const response = await fetch(
                    `/centro-carga/trabajadores/${trabajadorId}/requerimientos`,
                    {
                        headers: {
                            Accept: 'application/json',
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error('requirements_failed');
                }

                const payload = (await response.json()) as RequirementsResponse;
                setRequirements(payload);
            } finally {
                setLoadingRequirements(false);
            }
        },
        [],
    );

    const selectTrabajador = useCallback(
        async (trabajador: TrabajadorOption): Promise<void> => {
            setSelectedTrabajador(trabajador);
            setSearch(
                `${trabajador.documento} · ${trabajador.nombre_completo}`,
            );
            setSearchResults([]);
            clearItems();
            await loadRequirements(trabajador.id);
        },
        [clearItems, loadRequirements],
    );

    const addFiles = useCallback(
        (incomingFiles: File[]) => {
            if (!selectedWorkerId) {
                setDropzoneError(
                    'Selecciona un trabajador antes de cargar archivos.',
                );
                return;
            }

            const validFiles = incomingFiles.filter(isAcceptedFile);
            const invalidCount = incomingFiles.length - validFiles.length;

            if (invalidCount > 0) {
                setDropzoneError(
                    'Se ignoraron archivos no permitidos. Solo PDF, JPG o PNG.',
                );
            } else {
                setDropzoneError('');
            }

            if (validFiles.length === 0) {
                return;
            }

            const newItems = validFiles.map<CargaArchivoItem>((file, index) => {
                return {
                    id: `${Date.now()}-${index}-${Math.random().toString(16).slice(2, 8)}`,
                    file,
                    previewUrl: URL.createObjectURL(file),
                    ocrStatus: 'queued',
                    ocrProgress: 0,
                    uploadStatus: 'idle',
                    uploadProgress: 0,
                    extractedText: '',
                    suggestion: null,
                    matchedTipoDocumentoId: null,
                    expiryDate: '',
                    errorMessage: null,
                };
            });

            setItems((previous) => [...previous, ...newItems]);
            enqueueOCR(
                newItems.map((item) => ({ id: item.id, file: item.file })),
            );
        },
        [enqueueOCR, selectedWorkerId],
    );

    const handleDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        const droppedFiles = Array.from(event.dataTransfer.files);
        addFiles(droppedFiles);
    };

    const handleInputChange = (event: ChangeEvent<HTMLInputElement>) => {
        const selectedFiles = event.target.files
            ? Array.from(event.target.files)
            : [];
        addFiles(selectedFiles);
        event.target.value = '';
    };

    const handleDragEnd = (event: DragEndEvent) => {
        if (!event.over) {
            return;
        }

        const activeId = String(event.active.id);
        const overId = String(event.over.id);

        if (!activeId.startsWith('file-') || !overId.startsWith('target-')) {
            return;
        }

        const fileId = activeId.replace('file-', '');
        const tipoDocumentoId = Number(overId.replace('target-', ''));

        if (uploadedTipoIdsSet.has(tipoDocumentoId)) {
            return;
        }

        updateItem(fileId, (item) => ({
            ...item,
            matchedTipoDocumentoId: tipoDocumentoId,
        }));
    };

    const setMatchForItem = useCallback(
        (id: string, tipoDocumentoId: number | null): void => {
            updateItem(id, (item) => ({
                ...item,
                matchedTipoDocumentoId: tipoDocumentoId,
                errorMessage: null,
            }));
        },
        [updateItem],
    );

    const setExpiryForItem = useCallback(
        (id: string, expiryDate: string): void => {
            updateItem(id, (item) => ({
                ...item,
                expiryDate,
                errorMessage: null,
            }));
        },
        [updateItem],
    );

    const confirmSuggestion = useCallback(
        (id: string): void => {
            updateItem(id, (item) => {
                if (
                    !item.suggestion ||
                    uploadedTipoIdsSet.has(item.suggestion.tipoDocumentoId)
                ) {
                    return item;
                }

                return {
                    ...item,
                    matchedTipoDocumentoId: item.suggestion.tipoDocumentoId,
                };
            });
        },
        [updateItem, uploadedTipoIdsSet],
    );

    const removeItem = useCallback((id: string): void => {
        setItems((previous) => {
            const target = previous.find((item) => item.id === id);
            if (target?.previewUrl) {
                URL.revokeObjectURL(target.previewUrl);
            }

            return previous.filter((item) => item.id !== id);
        });
    }, []);

    const uploadSingle = useCallback(
        async (item: CargaArchivoItem): Promise<void> => {
            if (!selectedTrabajador || item.matchedTipoDocumentoId === null) {
                return;
            }

            const tipoDocumentoId = item.matchedTipoDocumentoId;

            await new Promise<void>((resolve) => {
                const formData = new FormData();
                formData.append('trabajador_id', selectedTrabajador.id);
                formData.append(
                    'tipo_documento_id',
                    tipoDocumentoId.toString(),
                );
                formData.append('archivo', item.file);
                if (item.expiryDate) {
                    formData.append('expiry_date', item.expiryDate);
                }

                updateItem(item.id, (current) => ({
                    ...current,
                    uploadStatus: 'uploading',
                    uploadProgress: 0,
                    errorMessage: null,
                }));

                const csrfMetaToken = getCsrfToken();
                const xsrfCookieToken = getCookieValue('XSRF-TOKEN');

                if (!csrfMetaToken && !xsrfCookieToken) {
                    updateItem(item.id, (current) => ({
                        ...current,
                        uploadStatus: 'error',
                        uploadProgress: 100,
                        errorMessage:
                            'Sesion expirada o token CSRF ausente. Recarga la pagina e intenta nuevamente.',
                    }));
                    resolve();
                    return;
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/centro-carga/documentos', true);
                xhr.withCredentials = true;
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                if (xsrfCookieToken) {
                    xhr.setRequestHeader('X-XSRF-TOKEN', xsrfCookieToken);
                } else if (csrfMetaToken) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfMetaToken);
                }

                xhr.upload.onprogress = (event) => {
                    if (!event.lengthComputable) {
                        return;
                    }

                    const percentage = Math.round(
                        (event.loaded / event.total) * 100,
                    );
                    updateItem(item.id, (current) => ({
                        ...current,
                        uploadProgress: percentage,
                    }));
                };

                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const payload = JSON.parse(
                            xhr.responseText,
                        ) as UploadResponse;

                        updateItem(item.id, (current) => ({
                            ...current,
                            uploadStatus: 'success',
                            uploadProgress: 100,
                            errorMessage: null,
                        }));

                        setRequirements((previous) => {
                            const requirement = previous.tipos_documentos.find(
                                (item) =>
                                    item.id === payload.data.tipo_documento_id,
                            );

                            if (requirement?.permite_multiples_en_mes) {
                                return previous;
                            }

                            return {
                                ...previous,
                                tipos_documentos_cargados:
                                    previous.tipos_documentos_cargados.includes(
                                        payload.data.tipo_documento_id,
                                    )
                                        ? previous.tipos_documentos_cargados
                                        : [
                                              ...previous.tipos_documentos_cargados,
                                              payload.data.tipo_documento_id,
                                          ],
                            };
                        });

                        resolve();
                        return;
                    }

                    let errorMessage = 'No fue posible subir el archivo.';
                    try {
                        const payload = JSON.parse(xhr.responseText) as {
                            message?: string;
                            errors?: Record<string, string[]>;
                        };

                        if (payload.errors) {
                            const firstError = Object.values(
                                payload.errors,
                            )[0]?.[0];
                            if (firstError) {
                                errorMessage = firstError;
                            }
                        } else if (payload.message) {
                            errorMessage = payload.message;
                        }
                    } catch {
                        errorMessage = 'Error inesperado al subir el archivo.';
                    }

                    updateItem(item.id, (current) => ({
                        ...current,
                        uploadStatus: 'error',
                        uploadProgress: 100,
                        errorMessage,
                    }));
                    resolve();
                };

                xhr.onerror = () => {
                    updateItem(item.id, (current) => ({
                        ...current,
                        uploadStatus: 'error',
                        uploadProgress: 100,
                        errorMessage: 'Error de red durante la carga.',
                    }));
                    resolve();
                };

                xhr.send(formData);
            });
        },
        [selectedTrabajador, updateItem],
    );

    const uploadAllMatched = async (): Promise<void> => {
        const toUpload = itemsRef.current.filter(
            (item) =>
                item.matchedTipoDocumentoId !== null &&
                item.uploadStatus !== 'success' &&
                !uploadedTipoIdsSet.has(item.matchedTipoDocumentoId),
        );

        if (toUpload.length === 0) {
            return;
        }

        setUploadingAll(true);
        for (const item of toUpload) {
            await uploadSingle(item);
        }
        setUploadingAll(false);
    };

    const matchCountByRequirement = useMemo(() => {
        const count = new Map<number, number>();

        for (const item of items) {
            if (item.matchedTipoDocumentoId === null) {
                continue;
            }

            count.set(
                item.matchedTipoDocumentoId,
                (count.get(item.matchedTipoDocumentoId) ?? 0) + 1,
            );
        }

        return count;
    }, [items]);

    const classifiedCount = useMemo(
        () =>
            items.filter((item) => item.matchedTipoDocumentoId !== null).length,
        [items],
    );

    const ocrQueueStats = useMemo(
        () => ({
            queued: items.filter((item) => item.ocrStatus === 'queued').length,
            processing: items.filter((item) => item.ocrStatus === 'processing')
                .length,
            done: items.filter((item) => item.ocrStatus === 'done').length,
            failed: items.filter((item) => item.ocrStatus === 'failed').length,
        }),
        [items],
    );

    const readyToUploadCount = useMemo(
        () =>
            items.filter(
                (item) =>
                    item.matchedTipoDocumentoId !== null &&
                    item.uploadStatus !== 'success' &&
                    !uploadedTipoIdsSet.has(item.matchedTipoDocumentoId),
            ).length,
        [items, uploadedTipoIdsSet],
    );

    return (
        <>
            <Head title="Centro de Carga" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Centro de Carga
                        </h1>
                        <p className="text-muted-foreground">
                            Carga masiva de documentos con OCR cliente y
                            matching asistido.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href="/trabajadores">
                            <Button variant="outline">Volver a Personal</Button>
                        </Link>
                        <Button
                            onClick={uploadAllMatched}
                            disabled={
                                !selectedTrabajador ||
                                readyToUploadCount === 0 ||
                                uploadingAll
                            }
                            className="bg-gradient-to-r from-[var(--brand-green)] via-[var(--brand-forest)] to-[var(--brand-orange)] text-[var(--primary-foreground)]"
                        >
                            {uploadingAll ? (
                                <>
                                    <LoaderCircle className="mr-2 size-4 animate-spin" />
                                    Subiendo...
                                </>
                            ) : (
                                <>
                                    <UploadCloud className="mr-2 size-4" />
                                    Subir clasificados ({readyToUploadCount})
                                </>
                            )}
                        </Button>
                    </div>
                </div>

                <Card className="border-[var(--brand-green)]/25 bg-gradient-to-r from-white/80 via-white/70 to-[var(--brand-lime)]/10">
                    <CardHeader>
                        <CardTitle>Trabajador objetivo</CardTitle>
                        <CardDescription>
                            Busca por RUT o nombre para habilitar la
                            clasificación documental.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                className="pl-10"
                                placeholder="Ejemplo: 12345678-5, Juan Perez..."
                            />
                        </div>

                        {searching && (
                            <p className="text-xs text-muted-foreground">
                                Buscando trabajadores...
                            </p>
                        )}

                        {searchResults.length > 0 && (
                            <div className="max-h-56 space-y-2 overflow-auto rounded-lg border border-border/70 bg-white/85 p-2">
                                {searchResults.map((trabajador) => (
                                    <button
                                        key={trabajador.id}
                                        type="button"
                                        onClick={() =>
                                            void selectTrabajador(trabajador)
                                        }
                                        className="flex w-full items-center justify-between rounded-md border border-transparent px-3 py-2 text-left transition hover:border-[var(--brand-green)]/40 hover:bg-[var(--brand-lime)]/10"
                                    >
                                        <span>
                                            <span className="block text-sm font-semibold text-foreground">
                                                {trabajador.nombre_completo}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {trabajador.documento}
                                            </span>
                                        </span>
                                        <Badge
                                            variant={
                                                trabajador.estado === 'activo'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {trabajador.estado}
                                        </Badge>
                                    </button>
                                ))}
                            </div>
                        )}

                        {selectedTrabajador && (
                            <div className="flex flex-wrap items-center gap-2 rounded-lg border border-[var(--brand-green)]/30 bg-[var(--brand-lime)]/10 px-3 py-2 text-sm">
                                <CheckCircle2 className="size-4 text-[var(--brand-green)]" />
                                <span className="font-semibold">
                                    {selectedTrabajador.nombre_completo}
                                </span>
                                <span className="text-muted-foreground">
                                    {selectedTrabajador.documento}
                                </span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <DndContext onDragEnd={handleDragEnd}>
                    <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                        <Card className="border-[var(--brand-green)]/20">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <UploadCloud className="size-5 text-[var(--brand-green)]" />
                                    Depósito de archivos
                                </CardTitle>
                                <CardDescription>
                                    Arrastra múltiples PDF/JPG/PNG. Cada archivo
                                    pasa por OCR en worker y se sugiere su tipo.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div
                                    onDrop={handleDrop}
                                    onDragOver={(event) =>
                                        event.preventDefault()
                                    }
                                    className={cn(
                                        'rounded-xl border border-dashed p-8 text-center transition',
                                        selectedTrabajador
                                            ? 'border-[var(--brand-green)]/45 bg-[var(--brand-lime)]/8'
                                            : 'border-border/70 bg-muted/20',
                                    )}
                                >
                                    <CircleDashed className="mx-auto size-8 text-[var(--brand-green)]" />
                                    <p className="mt-3 text-sm font-semibold">
                                        Suelta archivos aquí para procesarlos
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        OCR cliente con fallback manual sin
                                        bloquear la UI
                                    </p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="mt-4"
                                        onClick={() =>
                                            fileInputRef.current?.click()
                                        }
                                        disabled={!selectedTrabajador}
                                    >
                                        Seleccionar archivos
                                    </Button>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        className="hidden"
                                        onChange={handleInputChange}
                                    />
                                </div>

                                {dropzoneError && (
                                    <div className="flex items-center gap-2 rounded-lg border border-[var(--brand-orange)]/35 bg-[var(--brand-orange)]/10 px-3 py-2 text-sm text-[var(--brand-orange-strong)]">
                                        <AlertCircle className="size-4" />
                                        {dropzoneError}
                                    </div>
                                )}

                                <div className="flex flex-wrap gap-2 text-xs">
                                    <Badge variant="outline">
                                        Total archivos: {items.length}
                                    </Badge>
                                    <Badge variant="outline">
                                        Workers OCR: {OCR_WORKER_COUNT}
                                    </Badge>
                                    <Badge variant="outline">
                                        En cola: {ocrQueueStats.queued}
                                    </Badge>
                                    <Badge variant="outline">
                                        Procesando: {ocrQueueStats.processing}
                                    </Badge>
                                    <Badge variant="outline">
                                        Clasificados: {classifiedCount}
                                    </Badge>
                                    <Badge variant="outline">
                                        OCR listos: {ocrQueueStats.done}
                                    </Badge>
                                    <Badge variant="outline">
                                        OCR fallidos: {ocrQueueStats.failed}
                                    </Badge>
                                </div>

                                {items.length === 0 ? (
                                    <div className="rounded-xl border border-border/70 bg-muted/25 px-4 py-10 text-center text-sm text-muted-foreground">
                                        No hay archivos en cola.
                                    </div>
                                ) : (
                                    <div className="grid gap-3">
                                        {items.map((item) => (
                                            <UploadFileCard
                                                key={item.id}
                                                item={item}
                                                requirements={
                                                    requirements.tipos_documentos
                                                }
                                                uploadedTipoIds={
                                                    uploadedTipoIdsSet
                                                }
                                                onRemove={removeItem}
                                                onConfirmSuggestion={
                                                    confirmSuggestion
                                                }
                                                onMatchChange={setMatchForItem}
                                                onExpiryChange={
                                                    setExpiryForItem
                                                }
                                            />
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card className="border-[var(--brand-orange)]/25">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ClipboardCheck className="size-5 text-[var(--brand-orange-strong)]" />
                                    Requerimientos objetivo
                                </CardTitle>
                                <CardDescription>
                                    Arrastra cada archivo a su tipo de documento
                                    o confirma la sugerencia OCR.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {loadingRequirements ? (
                                    <div className="rounded-xl border border-border/70 bg-muted/20 px-3 py-6 text-center text-sm text-muted-foreground">
                                        Cargando requerimientos...
                                    </div>
                                ) : requirements.sin_faena_activa ? (
                                    <div className="rounded-xl border border-[var(--brand-orange)]/35 bg-[var(--brand-orange)]/10 px-3 py-4 text-sm text-[var(--brand-orange-strong)]">
                                        El trabajador no tiene faena activa con
                                        tipo definido.
                                    </div>
                                ) : requirements.tipos_documentos.length ===
                                  0 ? (
                                    <div className="rounded-xl border border-border/70 bg-muted/20 px-3 py-6 text-center text-sm text-muted-foreground">
                                        No hay tipos de documentos configurados
                                        para este trabajador.
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {requirements.tipos_documentos.map(
                                            (requirement) => (
                                                <RequirementTarget
                                                    key={requirement.id}
                                                    requirement={requirement}
                                                    isUploaded={uploadedTipoIdsSet.has(
                                                        requirement.id,
                                                    )}
                                                    matchedCount={
                                                        matchCountByRequirement.get(
                                                            requirement.id,
                                                        ) ?? 0
                                                    }
                                                />
                                            ),
                                        )}
                                    </div>
                                )}

                                <div className="rounded-xl border border-[var(--brand-green)]/30 bg-[var(--brand-lime)]/8 px-3 py-3">
                                    <div className="flex items-center gap-2 text-sm font-semibold text-[var(--brand-green)]">
                                        <Sparkles className="size-4" />
                                        Matching asistido activo
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        OCR usa palabras clave derivadas de los
                                        tipos de documento registrados en la app
                                        y detecta fechas de vencimiento
                                        asociadas.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </DndContext>

                {!selectedTrabajador && (
                    <div className="flex items-center gap-2 rounded-xl border border-border/70 bg-muted/20 px-4 py-3 text-sm text-muted-foreground">
                        <AlertCircle className="size-4" />
                        Selecciona un trabajador para habilitar el Centro de
                        Carga.
                    </div>
                )}
            </div>
        </>
    );
}

CentroCarga.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);
