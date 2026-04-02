<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentoPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_preview_document_inline(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoDocumento = TipoDocumento::create([
            'nombre' => 'Formulario 30',
            'codigo' => 'F30',
            'descripcion' => null,
            'periodicidad' => 'mensual',
            'es_obligatorio' => true,
            'es_documento_trabajador' => false,
            'dias_vencimiento' => 30,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 2048,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);

        $path = "documentos/{$contratista->id}/F30/formulario-30.pdf";
        Storage::disk('private')->put($path, '%PDF-1.4 fake');

        $documento = Documento::create([
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => now()->year,
            'periodo_mes' => now()->month,
            'archivo_nombre_original' => 'formulario-30.pdf',
            'archivo_ruta' => $path,
            'archivo_tamano_kb' => 120,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $user->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('documentos.preview', $documento));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'inline; filename="formulario-30.pdf"');
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

        $tipoDocumento = TipoDocumento::create([
            'nombre' => 'Formulario 30',
            'codigo' => 'F30',
            'descripcion' => null,
            'periodicidad' => 'mensual',
            'es_obligatorio' => true,
            'es_documento_trabajador' => false,
            'dias_vencimiento' => 30,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 2048,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);

        $path = "documentos/{$otherContratista->id}/F30/formulario-30.pdf";
        Storage::disk('private')->put($path, '%PDF-1.4 fake');

        $documento = Documento::create([
            'contratista_id' => $otherContratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => now()->year,
            'periodo_mes' => now()->month,
            'archivo_nombre_original' => 'formulario-30.pdf',
            'archivo_ruta' => $path,
            'archivo_tamano_kb' => 120,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $user->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('documentos.preview', $documento))
            ->assertForbidden();
    }
}
