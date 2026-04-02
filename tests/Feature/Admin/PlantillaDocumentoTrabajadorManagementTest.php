<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantillaDocumentoTrabajadorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_worker_signature_template(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $tipoDocumento = $this->createWorkerTipoDocumento();

        $response = $this->actingAs($admin)->post(route('admin.plantillas-documentos-trabajador.store'), [
            'nombre' => 'Recepción EPP',
            'tipo_documento_id' => $tipoDocumento->id,
            'contenido_html' => '<p>Con fecha {{fecha}} el trabajador {{trabajador_nombre}} recibe EPP.</p>',
            'fuente_nombre' => 'times',
            'fuente_tamano' => 13,
            'color_texto' => '#1F2937',
            'formato_papel' => 'a4',
            'activo' => true,
        ]);

        $response->assertRedirect(route('admin.plantillas-documentos-trabajador.index'));

        $this->assertDatabaseHas('plantillas_documentos_trabajador', [
            'nombre' => 'Recepción EPP',
            'tipo_documento_id' => $tipoDocumento->id,
            'activo' => true,
            'creado_por' => $admin->id,
            'fuente_nombre' => 'times',
            'fuente_tamano' => 13,
            'color_texto' => '#1F2937',
            'formato_papel' => 'a4',
        ]);
    }

    public function test_template_creation_rejects_unknown_variable(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $tipoDocumento = $this->createWorkerTipoDocumento();

        $response = $this->actingAs($admin)->from(route('admin.plantillas-documentos-trabajador.create'))
            ->post(route('admin.plantillas-documentos-trabajador.store'), [
                'nombre' => 'Plantilla inválida',
                'tipo_documento_id' => $tipoDocumento->id,
                'contenido_html' => '<p>{{variable_inexistente}}</p>',
                'fuente_nombre' => 'dejavu_sans',
                'fuente_tamano' => 12,
                'color_texto' => '#111827',
                'formato_papel' => 'letter',
                'activo' => true,
            ]);

        $response->assertRedirect(route('admin.plantillas-documentos-trabajador.create'));
        $response->assertSessionHasErrors('contenido_html');
    }

    private function createWorkerTipoDocumento(): TipoDocumento
    {
        return TipoDocumento::query()->create([
            'nombre' => 'Recepción de EPP',
            'codigo' => 'REC_EPP',
            'descripcion' => 'Documento formal de recepción',
            'periodicidad' => 'unico',
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => null,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 5120,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);
    }
}
