<?php

namespace App\Http\Controllers;

use App\Http\Requests\HelpDocumentRequest;
use App\Models\HelpDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HelpDocumentController extends Controller
{
    public function index(): Response
    {
        $documentos = HelpDocument::query()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (HelpDocument $doc) => [
                'id' => $doc->id,
                'nombre' => $doc->nombre,
                'descripcion' => $doc->descripcion,
                'archivo_nombre_original' => $doc->archivo_nombre_original,
                'archivo_tamano_kb' => $doc->archivo_tamano_kb,
                'tipo_extension' => $doc->tipo_extension,
                'tamano_formateado' => $doc->sizeForHumans(),
                'download_url' => $doc->downloadUrl(),
                'created_at' => $doc->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('ayuda/index', [
            'documentos' => $documentos,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ayuda/create');
    }

    public function store(HelpDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('archivo');

        $archivoNombre = $file->getClientOriginalName();
        $archivoRuta = $file->store('help-documents', 'public');
        $archivoTamano = $file->getSize() / 1024;
        $extension = $file->getClientOriginalExtension();

        HelpDocument::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'archivo_nombre_original' => $archivoNombre,
            'archivo_ruta' => $archivoRuta,
            'archivo_tamano_kb' => (int) round($archivoTamano),
            'tipo_extension' => strtolower($extension),
            'subido_por' => $request->user()->id,
        ]);

        return redirect()->route('ayuda.index')
            ->with('success', 'Documento subido exitosamente.');
    }

    public function download(HelpDocument $helpDocument)
    {
        if (! Storage::disk('public')->exists($helpDocument->archivo_ruta)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('public')->download(
            $helpDocument->archivo_ruta,
            $helpDocument->archivo_nombre_original,
        );
    }

    public function destroy(Request $request, HelpDocument $helpDocument): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        Storage::disk('public')->delete($helpDocument->archivo_ruta);
        $helpDocument->delete();

        return redirect()->route('ayuda.index')
            ->with('success', 'Documento eliminado exitosamente.');
    }
}
