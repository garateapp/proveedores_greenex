<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CentroCargaContratistaDocumentoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_centro_carga_contratista_upload_stores_document_with_period_and_expiry_date(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create(['estado' => 'activo']);
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoDocumento = $this->createContratistaTipoDocumento();

        $response = $this->actingAs($user)->postJson(
            route('centro-carga-contratistas.documentos.store'),
            [
                'contratista_id' => $contratista->id,
                'tipo_documento_id' => $tipoDocumento->id,
                'periodo_ano' => now()->year,
                'periodo_mes' => now()->month,
                'archivo' => UploadedFile::fake()->create('f30.pdf', 220, 'application/pdf'),
                'expiry_date' => '2026-12-31',
            ],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.contratista_id', $contratista->id);
        $response->assertJsonPath('data.tipo_documento_id', $tipoDocumento->id);

        $documento = Documento::query()->first();
        $this->assertNotNull($documento);
        $this->assertSame('2026-12-31', $documento->fecha_vencimiento?->toDateString());
        Storage::disk('private')->assertExists($documento->archivo_ruta);
    }

    public function test_centro_carga_contratista_upload_is_forbidden_for_other_contratista(): void
    {
        Storage::fake('private');

        $ownerContratista = Contratista::factory()->create(['estado' => 'activo']);
        $otherContratista = Contratista::factory()->create(['estado' => 'activo']);

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $ownerContratista->id,
        ]);

        $tipoDocumento = $this->createContratistaTipoDocumento();

        $this->actingAs($user)->postJson(route('centro-carga-contratistas.documentos.store'), [
            'contratista_id' => $otherContratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => now()->year,
            'periodo_mes' => now()->month,
            'archivo' => UploadedFile::fake()->create('f30.pdf', 120, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_centro_carga_contratista_allows_multiple_documents_in_same_period_when_tipo_allows_it(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create(['estado' => 'activo']);
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoDocumento = $this->createContratistaTipoDocumento([
            'permite_multiples_en_mes' => true,
        ]);

        $payload = [
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => now()->year,
            'periodo_mes' => now()->month,
        ];

        $this->actingAs($user)->postJson(
            route('centro-carga-contratistas.documentos.store'),
            [
                ...$payload,
                'archivo' => UploadedFile::fake()->create('f30-1.pdf', 200, 'application/pdf'),
            ],
        )->assertCreated();

        $this->actingAs($user)->postJson(
            route('centro-carga-contratistas.documentos.store'),
            [
                ...$payload,
                'archivo' => UploadedFile::fake()->create('f30-2.pdf', 200, 'application/pdf'),
            ],
        )->assertCreated();

        $this->assertSame(
            2,
            Documento::query()
                ->where('contratista_id', $contratista->id)
                ->where('tipo_documento_id', $tipoDocumento->id)
                ->where('periodo_ano', now()->year)
                ->where('periodo_mes', now()->month)
                ->count(),
        );
    }

    public function test_centro_carga_contratista_blocks_second_document_in_same_period_when_tipo_does_not_allow_multiple(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create(['estado' => 'activo']);
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoDocumento = $this->createContratistaTipoDocumento([
            'permite_multiples_en_mes' => false,
        ]);

        $payload = [
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => now()->year,
            'periodo_mes' => now()->month,
        ];

        $this->actingAs($user)->postJson(
            route('centro-carga-contratistas.documentos.store'),
            [
                ...$payload,
                'archivo' => UploadedFile::fake()->create('f30-1.pdf', 200, 'application/pdf'),
            ],
        )->assertCreated();

        $this->actingAs($user)->postJson(
            route('centro-carga-contratistas.documentos.store'),
            [
                ...$payload,
                'archivo' => UploadedFile::fake()->create('f30-2.pdf', 200, 'application/pdf'),
            ],
        )->assertStatus(422)->assertJsonValidationErrors(['tipo_documento_id']);
    }

    public function test_search_contratistas_in_centro_carga_respects_contratista_scope(): void
    {
        $ownerContratista = Contratista::factory()->create([
            'estado' => 'activo',
            'rut' => '11111111-1',
            'razon_social' => 'Servicios Greenex Uno SpA',
        ]);
        $otherContratista = Contratista::factory()->create([
            'estado' => 'activo',
            'rut' => '22222222-2',
            'razon_social' => 'Servicios Greenex Dos SpA',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $ownerContratista->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('centro-carga-contratistas.contratistas.search', ['search' => 'Greenex']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $ownerContratista->id);
        $response->assertJsonMissing(['id' => $otherContratista->id]);
    }

    public function test_contratista_requirements_include_keywords_from_registered_document_types(): void
    {
        $contratista = Contratista::factory()->create(['estado' => 'activo']);
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoDocumento = $this->createContratistaTipoDocumento([
            'nombre' => 'Certificado Tributario',
            'codigo' => 'CERT_TRIB',
            'descripcion' => 'Certificado tributario anual ante SII',
            'instrucciones' => 'Incluir timbre y firma digital legible',
        ]);

        $response = $this->actingAs($user)->getJson(
            route('centro-carga-contratistas.contratistas.requirements', $contratista->id).'?periodo_ano='.now()->year,
        );

        $response->assertOk();
        $response->assertJsonPath('tipos_documentos.0.id', $tipoDocumento->id);

        $keywords = $response->json('tipos_documentos.0.palabras_clave');
        $this->assertIsArray($keywords);
        $this->assertContains('tributario', $keywords);
    }

    private function createContratistaTipoDocumento(array $overrides = []): TipoDocumento
    {
        return TipoDocumento::create(array_merge([
            'nombre' => 'Formulario 30',
            'codigo' => 'F30',
            'descripcion' => 'Certificado de cumplimiento laboral',
            'periodicidad' => 'mensual',
            'permite_multiples_en_mes' => false,
            'es_obligatorio' => true,
            'es_documento_trabajador' => false,
            'dias_vencimiento' => 30,
            'formatos_permitidos' => ['pdf', 'jpg', 'png'],
            'tamano_maximo_kb' => 3072,
            'requiere_validacion' => true,
            'instrucciones' => 'Adjuntar documento oficial vigente',
            'activo' => true,
        ], $overrides));
    }
}
