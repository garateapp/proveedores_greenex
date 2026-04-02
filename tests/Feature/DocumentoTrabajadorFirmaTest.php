<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\DocumentoTrabajador;
use App\Models\Faena;
use App\Models\PlantillaDocumentoTrabajador;
use App\Models\TipoDocumento;
use App\Models\TipoFaena;
use App\Models\Trabajador;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentoTrabajadorFirmaTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_sign_worker_document_and_generate_pdf(): void
    {
        Storage::fake('private');

        [
            'trabajador' => $trabajador,
            'plantilla' => $plantilla,
            'supervisor' => $supervisor,
        ] = $this->createSignatureContext();

        $response = $this->actingAs($supervisor)->post(
            route('trabajadores.firmas.store', [$trabajador, $plantilla]),
            ['signature_data_url' => $this->validSignatureDataUrl()],
        );

        $response->assertRedirect(route('trabajadores.firmas.index', $trabajador));

        $documentoFirmado = DocumentoTrabajador::query()
            ->where('trabajador_id', $trabajador->id)
            ->where('plantilla_documento_trabajador_id', $plantilla->id)
            ->first();

        $this->assertNotNull($documentoFirmado);
        $this->assertSame('firma_digital', $documentoFirmado->origen);
        $this->assertSame($supervisor->id, $documentoFirmado->firmado_por);
        $this->assertNotNull($documentoFirmado->firmado_at);
        $this->assertNotNull($documentoFirmado->contenido_hash);
        $this->assertNotNull($documentoFirmado->variables_snapshot);

        Storage::disk('private')->assertExists($documentoFirmado->archivo_ruta);
        Storage::disk('private')->assertExists((string) $documentoFirmado->firma_imagen_ruta);
    }

    public function test_worker_document_type_allows_multiple_signed_history_records(): void
    {
        Storage::fake('private');

        [
            'trabajador' => $trabajador,
            'plantilla' => $plantilla,
            'supervisor' => $supervisor,
        ] = $this->createSignatureContext();

        $this->actingAs($supervisor)->post(
            route('trabajadores.firmas.store', [$trabajador, $plantilla]),
            ['signature_data_url' => $this->validSignatureDataUrl()],
        )->assertRedirect();

        $this->actingAs($supervisor)->post(
            route('trabajadores.firmas.store', [$trabajador, $plantilla]),
            ['signature_data_url' => $this->validSignatureDataUrl()],
        )->assertRedirect();

        $this->assertDatabaseCount('documentos_trabajadores', 2);
        $this->assertSame(
            2,
            DocumentoTrabajador::query()
                ->where('trabajador_id', $trabajador->id)
                ->where('tipo_documento_id', $plantilla->tipo_documento_id)
                ->where('origen', 'firma_digital')
                ->count(),
        );
    }

    public function test_contratista_cannot_sign_worker_document(): void
    {
        Storage::fake('private');

        [
            'trabajador' => $trabajador,
            'plantilla' => $plantilla,
            'contratistaUser' => $contratistaUser,
        ] = $this->createSignatureContext();

        $this->actingAs($contratistaUser)->post(
            route('trabajadores.firmas.store', [$trabajador, $plantilla]),
            ['signature_data_url' => $this->validSignatureDataUrl()],
        )->assertForbidden();
    }

    public function test_pdf_template_view_applies_overflow_rules_to_keep_content_inside_page_margins(): void
    {
        $plantilla = new PlantillaDocumentoTrabajador([
            'nombre' => 'Entrega EPP',
            'formato_papel' => PlantillaDocumentoTrabajador::FORMATO_PAPEL_A4,
        ]);

        $tipoDocumento = new TipoDocumento([
            'nombre' => 'Recepción EPP',
            'codigo' => 'REC_EPP',
        ]);

        $trabajador = new Trabajador([
            'documento' => '12345678-5',
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
        ]);

        $html = view('pdf.documento-trabajador-firmado', [
            'plantilla' => $plantilla,
            'trabajador' => $trabajador,
            'tipoDocumento' => $tipoDocumento,
            'contenidoHtml' => '<table><tr><td>AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA</td></tr></table>',
            'signatureDataUrl' => $this->validSignatureDataUrl(),
            'variables' => [],
            'signedAt' => CarbonImmutable::parse('2026-03-12 10:30:00'),
            'fontFamily' => 'DejaVu Sans',
            'fontSize' => 12,
            'textColor' => '#111827',
        ])->render();

        $this->assertStringContainsString('@page {', $html);
        $this->assertStringContainsString('size: A4 portrait;', $html);
        $this->assertStringContainsString('word-wrap: break-word;', $html);
        $this->assertStringContainsString('table-layout: fixed;', $html);
    }

    /**
     * @return array{
     *     trabajador: Trabajador,
     *     plantilla: PlantillaDocumentoTrabajador,
     *     supervisor: User,
     *     contratistaUser: User
     * }
     */
    private function createSignatureContext(): array
    {
        $contratista = Contratista::factory()->create();

        $supervisor = User::factory()->create([
            'role' => UserRole::Supervisor,
            'contratista_id' => $contratista->id,
        ]);

        $contratistaUser = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $trabajador = Trabajador::query()->create([
            'id' => '12345678',
            'documento' => '12345678-5',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now(),
        ]);

        $tipoFaena = TipoFaena::query()->create([
            'nombre' => 'Planta',
            'codigo' => 'PLANTA',
            'descripcion' => null,
            'activo' => true,
        ]);

        $faena = Faena::query()->create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Faena Planta',
            'codigo' => 'FAENA_PLANTA',
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

        $tipoDocumento = TipoDocumento::query()->create([
            'nombre' => 'Recepción EPP',
            'codigo' => 'REC_EPP',
            'descripcion' => null,
            'periodicidad' => 'unico',
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => null,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 2048,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);
        $tipoDocumento->tiposFaena()->attach($tipoFaena->id);

        $plantilla = PlantillaDocumentoTrabajador::query()->create([
            'tipo_documento_id' => $tipoDocumento->id,
            'nombre' => 'Plantilla entrega EPP',
            'contenido_html' => '<p>Con fecha {{fecha}} se deja constancia de entrega a {{trabajador_nombre}}.</p>',
            'activo' => true,
            'creado_por' => $supervisor->id,
            'actualizado_por' => $supervisor->id,
        ]);

        return [
            'trabajador' => $trabajador,
            'plantilla' => $plantilla,
            'supervisor' => $supervisor,
            'contratistaUser' => $contratistaUser,
        ];
    }

    private function validSignatureDataUrl(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO4BLqQAAAAASUVORK5CYII=';
    }
}
