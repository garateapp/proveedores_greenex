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

class DocumentoTrabajadorUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_contratista_can_upload_trabajador_documento(): void
    {
        Storage::fake('private');

        $contratista = Contratista::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $trabajador = Trabajador::create([
            'id' => '12345678',
            'documento' => '12345678-5',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now(),
        ]);

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Planta',
            'codigo' => 'PLANTA',
            'descripcion' => null,
            'activo' => true,
        ]);

        $faena = Faena::create([
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

        $tipoDocumento = TipoDocumento::create([
            'nombre' => 'Contrato de trabajo',
            'codigo' => 'CONTRATO_TRABAJO',
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

        $file = UploadedFile::fake()->create('contrato.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post(
            route('trabajadores.documentos.store', $trabajador),
            [
                'tipo_documento_id' => $tipoDocumento->id,
                'archivo' => $file,
            ],
        );

        $response->assertRedirect();

        $documento = DocumentoTrabajador::first();

        $this->assertNotNull($documento);
        $this->assertSame($trabajador->id, $documento->trabajador_id);
        $this->assertSame($tipoDocumento->id, $documento->tipo_documento_id);
        $this->assertSame('contrato.pdf', $documento->archivo_nombre_original);

        Storage::disk('private')->assertExists($documento->archivo_ruta);
    }
}
