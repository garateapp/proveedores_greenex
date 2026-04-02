<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\DocumentoTrabajador;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentoTrabajadorPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_preview_worker_document_inline(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $trabajador = Trabajador::create([
            'id' => '12345678',
            'documento' => '12345678-9',
            'nombre' => 'Mario',
            'apellido' => 'Paredes',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->subMonth(),
        ]);

        $tipoDocumento = $this->createWorkerDocumentType();

        $path = "documentos-trabajadores/{$trabajador->id}/{$tipoDocumento->codigo}/cedula.pdf";
        Storage::disk('private')->put($path, '%PDF-1.4 fake');

        $documento = DocumentoTrabajador::create([
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo_nombre_original' => 'cedula.pdf',
            'archivo_ruta' => $path,
            'archivo_tamano_kb' => 100,
            'fecha_vencimiento' => now()->addYear()->toDateString(),
            'cargado_por' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('documentos-trabajadores.preview', $documento));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'inline; filename="cedula.pdf"');
    }

    public function test_owner_can_download_worker_document(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $trabajador = Trabajador::create([
            'id' => '99998888',
            'documento' => '99998888-1',
            'nombre' => 'Laura',
            'apellido' => 'Diaz',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->subMonth(),
        ]);

        $tipoDocumento = $this->createWorkerDocumentType();

        $path = "documentos-trabajadores/{$trabajador->id}/{$tipoDocumento->codigo}/contrato.pdf";
        Storage::disk('private')->put($path, '%PDF-1.4 fake');

        $documento = DocumentoTrabajador::create([
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo_nombre_original' => 'contrato.pdf',
            'archivo_ruta' => $path,
            'archivo_tamano_kb' => 110,
            'fecha_vencimiento' => null,
            'cargado_por' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('documentos-trabajadores.download', $documento));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_preview_is_forbidden_for_document_from_other_contratista(): void
    {
        Storage::fake('private');

        $ownerContratista = Contratista::factory()->create();
        $otherContratista = Contratista::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $ownerContratista->id,
        ]);

        $trabajador = Trabajador::create([
            'id' => '77776666',
            'documento' => '77776666-0',
            'nombre' => 'Patricio',
            'apellido' => 'Rojas',
            'contratista_id' => $otherContratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->subMonth(),
        ]);

        $tipoDocumento = $this->createWorkerDocumentType();

        $path = "documentos-trabajadores/{$trabajador->id}/{$tipoDocumento->codigo}/otro.pdf";
        Storage::disk('private')->put($path, '%PDF-1.4 fake');

        $documento = DocumentoTrabajador::create([
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo_nombre_original' => 'otro.pdf',
            'archivo_ruta' => $path,
            'archivo_tamano_kb' => 90,
            'fecha_vencimiento' => null,
            'cargado_por' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('documentos-trabajadores.preview', $documento))
            ->assertForbidden();
    }

    private function createWorkerDocumentType(): TipoDocumento
    {
        return TipoDocumento::create([
            'nombre' => 'Cédula de Identidad',
            'codigo' => 'CEDULA_IDENTIDAD',
            'descripcion' => null,
            'periodicidad' => 'anual',
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => 365,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 2048,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);
    }
}
