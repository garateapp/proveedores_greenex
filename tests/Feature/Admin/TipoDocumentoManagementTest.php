<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\TipoDocumento;
use App\Models\TipoFaena;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipoDocumentoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_tipo_documento(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Planta',
            'codigo' => 'PLANTA',
            'descripcion' => null,
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('tipo-documentos.store'), [
            'nombre' => 'Certificado F30',
            'codigo' => 'F30',
            'descripcion' => 'Certificado mensual emitido por DT.',
            'periodicidad' => 'mensual',
            'permite_multiples_en_mes' => true,
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => 30,
            'formatos_permitidos' => ['pdf'],
            'tipo_faena_ids' => [$tipoFaena->id],
            'tamano_maximo_kb' => 2048,
            'requiere_validacion' => true,
            'instrucciones' => 'Debe ser original emitido por DT.',
            'activo' => true,
        ]);

        $response->assertRedirect(route('tipo-documentos.index'));

        $this->assertDatabaseHas(TipoDocumento::class, [
            'codigo' => 'F30',
            'nombre' => 'Certificado F30',
            'permite_multiples_en_mes' => true,
            'es_documento_trabajador' => true,
            'activo' => true,
        ]);

        $tipoDocumento = TipoDocumento::where('codigo', 'F30')->firstOrFail();

        $this->assertDatabaseHas('tipo_documento_tipo_faena', [
            'tipo_documento_id' => $tipoDocumento->id,
            'tipo_faena_id' => $tipoFaena->id,
        ]);
    }
}
