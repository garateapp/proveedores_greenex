<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\DocumentoTrabajador;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrabajadorDocumentosIndicatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_trabajadores_index_includes_documentos_obligatorios_indicator(): void
    {
        $contratista = Contratista::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoContrato = TipoDocumento::create([
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

        $tipoCedula = TipoDocumento::create([
            'nombre' => 'Cedula de identidad',
            'codigo' => 'CEDULA_IDENTIDAD',
            'descripcion' => null,
            'periodicidad' => 'anual',
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => 30,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 2048,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);

        $trabajadorCompleto = Trabajador::create([
            'id' => '11111111',
            'documento' => '11111111-1',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now(),
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        DocumentoTrabajador::create([
            'trabajador_id' => $trabajadorCompleto->id,
            'tipo_documento_id' => $tipoContrato->id,
            'archivo_nombre_original' => 'contrato.pdf',
            'archivo_ruta' => 'documentos-trabajadores/contrato.pdf',
            'archivo_tamano_kb' => 120,
            'cargado_por' => $user->id,
        ]);

        DocumentoTrabajador::create([
            'trabajador_id' => $trabajadorCompleto->id,
            'tipo_documento_id' => $tipoCedula->id,
            'archivo_nombre_original' => 'cedula.pdf',
            'archivo_ruta' => 'documentos-trabajadores/cedula.pdf',
            'archivo_tamano_kb' => 120,
            'cargado_por' => $user->id,
        ]);

        $trabajadorIncompleto = Trabajador::create([
            'id' => '22222222',
            'documento' => '22222222-2',
            'nombre' => 'Luis',
            'apellido' => 'Rojas',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('trabajadores.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('trabajadores/index')
                ->has('trabajadores.data', 2)
                ->where('trabajadores.data', function ($data) use ($trabajadorCompleto, $trabajadorIncompleto) {
                    $indexed = collect($data)->keyBy('id');

                    return $indexed[$trabajadorCompleto->id]['documentos_obligatorios_completos'] === true
                        && $indexed[$trabajadorCompleto->id]['documentos_obligatorios_total'] === 2
                        && $indexed[$trabajadorCompleto->id]['documentos_obligatorios_cargados'] === 2
                        && $indexed[$trabajadorCompleto->id]['documentos_obligatorios_pendientes'] === 0
                        && $indexed[$trabajadorCompleto->id]['documentos_obligatorios_porcentaje'] === 100
                        && $indexed[$trabajadorIncompleto->id]['documentos_obligatorios_completos'] === false
                        && $indexed[$trabajadorIncompleto->id]['documentos_obligatorios_total'] === 2
                        && $indexed[$trabajadorIncompleto->id]['documentos_obligatorios_cargados'] === 0
                        && $indexed[$trabajadorIncompleto->id]['documentos_obligatorios_pendientes'] === 2
                        && $indexed[$trabajadorIncompleto->id]['documentos_obligatorios_porcentaje'] === 0;
                })
            );
    }
}
