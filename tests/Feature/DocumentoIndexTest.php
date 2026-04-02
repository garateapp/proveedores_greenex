<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentoIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure documentos index only works with non-worker document types.
     */
    public function test_documentos_index_filters_out_trabajador_document_types(): void
    {
        $contratista = Contratista::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoEmpresa = TipoDocumento::create([
            'nombre' => 'Certificado de cumplimiento',
            'codigo' => 'CERT_CUMPLIMIENTO',
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

        $tipoTrabajador = TipoDocumento::create([
            'nombre' => 'Contrato de trabajador',
            'codigo' => 'CONTRATO_TRABAJADOR',
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

        Documento::create([
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoEmpresa->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 1,
            'archivo_nombre_original' => 'cumplimiento.pdf',
            'archivo_ruta' => 'documentos/cumplimiento.pdf',
            'archivo_tamano_kb' => 250,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $user->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        Documento::create([
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoTrabajador->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 2,
            'archivo_nombre_original' => 'trabajador.pdf',
            'archivo_ruta' => 'documentos/trabajador.pdf',
            'archivo_tamano_kb' => 250,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $user->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('documentos.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('documentos/index')
                ->has('documentos.data', 1)
                ->where('documentos.data.0.tipo_documento_id', $tipoEmpresa->id)
                ->has('tiposDocumentos', 1)
                ->where('tiposDocumentos.0.id', $tipoEmpresa->id)
            );
    }

    public function test_admin_can_filter_documentos_by_contratista(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'contratista_id' => null,
        ]);

        $contratistaUno = Contratista::factory()->create([
            'razon_social' => 'Contratista Uno SpA',
            'estado' => 'activo',
        ]);
        $contratistaDos = Contratista::factory()->create([
            'razon_social' => 'Contratista Dos SpA',
            'estado' => 'activo',
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

        Documento::create([
            'contratista_id' => $contratistaUno->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 1,
            'archivo_nombre_original' => 'uno.pdf',
            'archivo_ruta' => 'documentos/uno.pdf',
            'archivo_tamano_kb' => 240,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        Documento::create([
            'contratista_id' => $contratistaDos->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 1,
            'archivo_nombre_original' => 'dos.pdf',
            'archivo_ruta' => 'documentos/dos.pdf',
            'archivo_tamano_kb' => 260,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('documentos.index', ['contratista_id' => $contratistaUno->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('documentos/index')
                ->has('documentos.data', 1)
                ->where('documentos.data.0.contratista_id', $contratistaUno->id)
            );
    }

    public function test_admin_can_filter_documentos_by_trabajador_contratista(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'contratista_id' => null,
        ]);

        $contratistaUno = Contratista::factory()->create([
            'estado' => 'activo',
        ]);
        $contratistaDos = Contratista::factory()->create([
            'estado' => 'activo',
        ]);

        Trabajador::create([
            'id' => '11112222',
            'documento' => '11112222-3',
            'nombre' => 'Luis',
            'apellido' => 'Fuentes',
            'contratista_id' => $contratistaUno->id,
            'estado' => 'activo',
            'fecha_ingreso' => now(),
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

        Documento::create([
            'contratista_id' => $contratistaUno->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 1,
            'archivo_nombre_original' => 'uno.pdf',
            'archivo_ruta' => 'documentos/uno.pdf',
            'archivo_tamano_kb' => 240,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        Documento::create([
            'contratista_id' => $contratistaDos->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 1,
            'archivo_nombre_original' => 'dos.pdf',
            'archivo_ruta' => 'documentos/dos.pdf',
            'archivo_tamano_kb' => 260,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('documentos.index', ['trabajador_id' => '11112222']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('documentos/index')
                ->has('documentos.data', 1)
                ->where('documentos.data.0.contratista_id', $contratistaUno->id)
            );
    }
}
