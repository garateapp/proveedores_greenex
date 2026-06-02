import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { BookOpen, Download, FileText, FileSpreadsheet, FileType, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Centro de Ayuda', href: '/ayuda' },
];

interface HelpDocumentItem {
    id: number;
    nombre: string;
    descripcion: string | null;
    archivo_nombre_original: string;
    archivo_tamano_kb: number;
    tipo_extension: string;
    tamano_formateado: string;
    download_url: string;
    created_at: string;
}

interface Props {
    documentos: HelpDocumentItem[];
}

function FileIcon({ ext }: { ext: string }) {
    switch (ext) {
        case 'pdf':
            return <FileType className="size-5 text-destructive" />;
        case 'doc':
        case 'docx':
            return <FileText className="size-5 text-blue-600" />;
        case 'xls':
        case 'xlsx':
            return <FileSpreadsheet className="size-5 text-emerald-600" />;
        default:
            return <FileText className="size-5 text-muted-foreground" />;
    }
}

export default function AyudaIndex({ documentos }: Props) {
    const page = usePage<SharedData>();
    const isAdmin = page.props.auth?.user?.isAdmin ?? false;

    const handleDelete = (id: number, nombre: string) => {
        if (!confirm(`¿Eliminar el documento "${nombre}"?`)) {
            return;
        }

        router.delete(`/ayuda/${id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Centro de Ayuda" />

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">Centro de Ayuda</h1>
                        <p className="text-muted-foreground">
                            Documentos de referencia, manuales y plantillas disponibles para descarga.
                        </p>
                    </div>
                    {isAdmin && (
                        <Link href="/ayuda/crear">
                            <Button>
                                <BookOpen className="mr-2 size-4" />
                                Subir documento
                            </Button>
                        </Link>
                    )}
                </div>

                {documentos.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                            <BookOpen className="mb-4 size-12 text-muted-foreground/40" />
                            <p className="text-lg font-semibold text-muted-foreground">
                                No hay documentos disponibles
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground/70">
                                {isAdmin
                                    ? 'Suba el primer documento desde el botón "Subir documento".'
                                    : 'Consulte a su administrador para que suba documentos de ayuda.'}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Documentos</CardTitle>
                            <CardDescription>
                                {documentos.length} documento{documentos.length !== 1 ? 's' : ''} disponible
                                {documentos.length !== 1 ? 's' : ''}.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead>Archivo</TableHead>
                                        <TableHead>Tamaño</TableHead>
                                        <TableHead>Subido</TableHead>
                                        <TableHead className="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {documentos.map((doc) => (
                                        <TableRow key={doc.id}>
                                            <TableCell className="font-medium">{doc.nombre}</TableCell>
                                            <TableCell className="max-w-xs truncate text-muted-foreground">
                                                {doc.descripcion ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <FileIcon ext={doc.tipo_extension} />
                                                    <span className="text-sm">{doc.archivo_nombre_original}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {doc.tamano_formateado}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {doc.created_at}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <a href={doc.download_url}>
                                                        <Button variant="ghost" size="sm">
                                                            <Download className="mr-2 size-4" />
                                                            Descargar
                                                        </Button>
                                                    </a>
                                                    {isAdmin && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-destructive"
                                                            onClick={() => handleDelete(doc.id, doc.nombre)}
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
