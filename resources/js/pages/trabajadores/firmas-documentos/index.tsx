import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ArrowLeft, Download, Eye, FileText } from 'lucide-react';
import { type ReactElement } from 'react';

interface TrabajadorData {
    id: string;
    documento: string;
    nombre_completo: string;
    contratista_nombre: string | null;
}

interface PlantillaData {
    id: number;
    nombre: string;
    tipo_documento_nombre: string | null;
    tipo_documento_codigo: string | null;
    updated_at: string | null;
}

interface DocumentoFirmadoData {
    id: number;
    tipo_documento_nombre: string | null;
    tipo_documento_codigo: string | null;
    plantilla_nombre: string | null;
    firmado_por_nombre: string | null;
    firmado_at: string | null;
    preview_url: string;
    download_url: string;
}

interface Props {
    trabajador: TrabajadorData;
    plantillas: PlantillaData[];
    documentosFirmados: DocumentoFirmadoData[];
}

const breadcrumbs = (trabajadorId: string): BreadcrumbItem[] => [
    { title: 'Personal', href: '/trabajadores' },
    { title: `Trabajador ${trabajadorId}`, href: `/trabajadores/${trabajadorId}` },
    { title: 'Firmas Digitales', href: `/trabajadores/${trabajadorId}/firmas-documentos` },
];

export default function FirmasDocumentosTrabajadorIndex({ trabajador, plantillas, documentosFirmados }: Props) {
    return (
        <>
            <Head title={`Firmas Digitales - ${trabajador.nombre_completo}`} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <Link href={`/trabajadores/${trabajador.id}`}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{trabajador.nombre_completo}</CardTitle>
                        <CardDescription>RUT: {trabajador.documento}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            Contratista: {trabajador.contratista_nombre || '—'}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Plantillas disponibles</CardTitle>
                        <CardDescription>
                            Seleccione una plantilla para capturar la firma digital del trabajador.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Plantilla</TableHead>
                                    <TableHead>Tipo documento</TableHead>
                                    <TableHead>Actualizada</TableHead>
                                    <TableHead className="text-right">Acción</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {plantillas.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center text-muted-foreground">
                                            No hay plantillas activas para este trabajador.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    plantillas.map((plantilla) => (
                                        <TableRow key={plantilla.id}>
                                            <TableCell className="font-medium">{plantilla.nombre}</TableCell>
                                            <TableCell>
                                                <div className="space-y-0.5">
                                                    <p>{plantilla.tipo_documento_nombre || '—'}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {plantilla.tipo_documento_codigo || '—'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>{plantilla.updated_at || '—'}</TableCell>
                                            <TableCell className="text-right">
                                                <Link
                                                    href={`/trabajadores/${trabajador.id}/firmas-documentos/${plantilla.id}/create`}
                                                >
                                                    <Button size="sm">
                                                        <FileText className="mr-2 h-4 w-4" />
                                                        Firmar
                                                    </Button>
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Historial de documentos firmados</CardTitle>
                        <CardDescription>
                            Registro de firmas digitales emitidas para este trabajador.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tipo documento</TableHead>
                                    <TableHead>Plantilla</TableHead>
                                    <TableHead>Firmado por</TableHead>
                                    <TableHead>Fecha firma</TableHead>
                                    <TableHead className="text-right">Archivo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {documentosFirmados.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-muted-foreground">
                                            Sin documentos firmados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    documentosFirmados.map((documento) => (
                                        <TableRow key={documento.id}>
                                            <TableCell>
                                                <div className="space-y-0.5">
                                                    <p>{documento.tipo_documento_nombre || '—'}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {documento.tipo_documento_codigo || '—'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>{documento.plantilla_nombre || '—'}</TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">{documento.firmado_por_nombre || '—'}</Badge>
                                            </TableCell>
                                            <TableCell>{documento.firmado_at || '—'}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <a href={documento.preview_url} target="_blank" rel="noreferrer">
                                                        <Button variant="ghost" size="sm">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </a>
                                                    <a href={documento.download_url}>
                                                        <Button variant="ghost" size="sm">
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                    </a>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

FirmasDocumentosTrabajadorIndex.layout = (page: ReactElement<Props>) => (
    <AppLayout breadcrumbs={breadcrumbs(page.props.trabajador.id)}>{page}</AppLayout>
);
