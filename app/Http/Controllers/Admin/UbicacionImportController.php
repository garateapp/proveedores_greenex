<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Ubicaciones\ImportUbicacionesCsvAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UbicacionImportRequest;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UbicacionImportController extends Controller
{
    public function __construct(
        private readonly ImportUbicacionesCsvAction $importUbicacionesCsvAction,
    ) {}

    public function import(UbicacionImportRequest $request): RedirectResponse
    {
        $result = $this->importUbicacionesCsvAction->execute($request->file('file'));

        return redirect()
            ->route('admin.ubicaciones.index')
            ->with('success', "Importación completada: {$result['imported']} ubicaciones creadas.");
    }

    public function template(): StreamedResponse
    {
        $filename = 'plantilla_ubicaciones.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = static function (): void {
            $file = fopen('php://output', 'w');

            if ($file === false) {
                return;
            }

            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['codigo', 'nombre', 'descripcion', 'tipo', 'padre_codigo', 'orden'], ';');
            fputcsv($file, ['UNI1', 'UNITEC 1', 'Área principal', 'principal', '', '1'], ';');
            fputcsv($file, ['UNI1-FILTRO', 'Filtro', 'Sub área', 'secundaria', 'UNI1', '2'], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
