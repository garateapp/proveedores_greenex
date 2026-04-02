<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\DocumentoTrabajador;
use App\Models\Faena;
use App\Models\TipoDocumento;
use App\Models\TipoFaena;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CentroCargaDocumentoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_centro_carga_upload_stores_worker_document_with_expiry_date(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        [$trabajador, $tipoDocumento] = $this->createWorkerWithRequirements($contratista->id);

        $response = $this->actingAs($user)->postJson(route('centro-carga.documentos.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo' => UploadedFile::fake()->create('antecedentes.pdf', 220, 'application/pdf'),
            'expiry_date' => '2026-12-31',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.trabajador_id', $trabajador->id);
        $response->assertJsonPath('data.tipo_documento_id', $tipoDocumento->id);

        $documento = DocumentoTrabajador::query()->first();
        $this->assertNotNull($documento);
        $this->assertSame('2026-12-31', $documento->fecha_vencimiento?->toDateString());
        Storage::disk('private')->assertExists($documento->archivo_ruta);
    }

    public function test_centro_carga_upload_is_forbidden_for_worker_from_other_contratista(): void
    {
        Storage::fake('private');

        $ownerContratista = Contratista::factory()->create();
        $otherContratista = Contratista::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $ownerContratista->id,
        ]);

        [$trabajador, $tipoDocumento] = $this->createWorkerWithRequirements($otherContratista->id);

        $this->actingAs($user)->postJson(route('centro-carga.documentos.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo' => UploadedFile::fake()->create('cedula.pdf', 120, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_centro_carga_upload_allows_multiple_worker_documents_when_tipo_allows_it(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        [$trabajador, $tipoDocumento] = $this->createWorkerWithRequirements(
            $contratista->id,
            ['permite_multiples_en_mes' => true],
        );

        $this->actingAs($user)->postJson(route('centro-carga.documentos.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo' => UploadedFile::fake()->create('liquidacion-1.pdf', 220, 'application/pdf'),
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('centro-carga.documentos.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo' => UploadedFile::fake()->create('liquidacion-2.pdf', 220, 'application/pdf'),
        ])->assertCreated();

        $this->assertSame(
            2,
            DocumentoTrabajador::query()
                ->where('trabajador_id', $trabajador->id)
                ->where('tipo_documento_id', $tipoDocumento->id)
                ->where('origen', 'carga_manual')
                ->count(),
        );
    }

    public function test_centro_carga_upload_blocks_second_worker_document_when_tipo_does_not_allow_multiple(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        [$trabajador, $tipoDocumento] = $this->createWorkerWithRequirements($contratista->id);

        $this->actingAs($user)->postJson(route('centro-carga.documentos.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo' => UploadedFile::fake()->create('cedula-1.pdf', 220, 'application/pdf'),
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('centro-carga.documentos.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo' => UploadedFile::fake()->create('cedula-2.pdf', 220, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors(['tipo_documento_id']);
    }

    public function test_search_trabajadores_in_centro_carga_respects_contratista_scope(): void
    {
        $ownerContratista = Contratista::factory()->create();
        $otherContratista = Contratista::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $ownerContratista->id,
        ]);

        Trabajador::create([
            'id' => '12345678',
            'documento' => '12345678-5',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'contratista_id' => $ownerContratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now(),
        ]);

        Trabajador::create([
            'id' => '87654321',
            'documento' => '87654321-2',
            'nombre' => 'Pedro',
            'apellido' => 'Vega',
            'contratista_id' => $otherContratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('centro-carga.trabajadores.search', ['search' => '123']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', '12345678');
    }

    public function test_requirements_include_keywords_from_registered_document_types(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        [$trabajador, $tipoDocumento] = $this->createWorkerWithRequirements($contratista->id);
        $tipoDocumento->update([
            'descripcion' => 'Documento de antecedentes penales vigente',
            'instrucciones' => 'Incluir certificado actualizado y firma visible',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('centro-carga.trabajadores.requirements', $trabajador));

        $response->assertOk();
        $response->assertJsonPath('tipos_documentos.0.id', $tipoDocumento->id);

        $keywords = $response->json('tipos_documentos.0.palabras_clave');

        $this->assertIsArray($keywords);
        $this->assertContains('antecedentes', $keywords);
    }

    /**
     * @return array{Trabajador, TipoDocumento}
     */
    private function createWorkerWithRequirements(
        int $contratistaId,
        array $tipoDocumentoOverrides = [],
    ): array {
        $trabajador = Trabajador::create([
            'id' => '11111111',
            'documento' => '11111111-1',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'contratista_id' => $contratistaId,
            'estado' => 'activo',
            'fecha_ingreso' => now(),
        ]);

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => null,
            'activo' => true,
        ]);

        $faena = Faena::create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Centro Norte',
            'codigo' => 'CENTRO_NORTE',
            'descripcion' => null,
            'ubicacion' => null,
            'estado' => 'activa',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => null,
        ]);

        $faena->trabajadores()->attach($trabajador->id, [
            'fecha_asignacion' => now()->toDateString(),
            'fecha_desasignacion' => null,
        ]);

        $tipoDocumento = TipoDocumento::create(array_merge([
            'nombre' => 'Certificado de Antecedentes',
            'codigo' => 'CERT_ANTECEDENTES',
            'descripcion' => null,
            'periodicidad' => 'anual',
            'permite_multiples_en_mes' => false,
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => 365,
            'formatos_permitidos' => ['pdf', 'jpg', 'png'],
            'tamano_maximo_kb' => 3072,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ], $tipoDocumentoOverrides));

        $tipoDocumento->tiposFaena()->attach($tipoFaena->id);

        return [$trabajador, $tipoDocumento];
    }
}
