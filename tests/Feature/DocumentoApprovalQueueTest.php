<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentoApprovalQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_documents_in_approval_queue(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'contratista_id' => null,
        ]);

        $contratista = Contratista::factory()->create([
            'estado' => 'activo',
        ]);

        $tipoDocumento = $this->createCompanyDocumentType();

        $pendiente = Documento::create([
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 2,
            'archivo_nombre_original' => 'pendiente.pdf',
            'archivo_ruta' => 'documentos/pendiente.pdf',
            'archivo_tamano_kb' => 200,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        Documento::create([
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 3,
            'archivo_nombre_original' => 'aprobado.pdf',
            'archivo_ruta' => 'documentos/aprobado.pdf',
            'archivo_tamano_kb' => 200,
            'estado' => 'aprobado',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
            'validado_por' => $admin->id,
            'validado_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('documentos.aprobaciones'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('documentos/aprobaciones')
                ->has('documentos.data', 1)
                ->where('documentos.data.0.id', $pendiente->id)
                ->where('documentos.data.0.estado', 'pendiente_validacion')
            );
    }

    public function test_non_admin_cannot_access_approval_queue(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $this->actingAs($user)
            ->get(route('documentos.aprobaciones'))
            ->assertForbidden();
    }

    public function test_admin_can_approve_and_reject_documents_from_approval_queue(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'contratista_id' => null,
        ]);

        $contratista = Contratista::factory()->create([
            'estado' => 'activo',
        ]);

        $tipoDocumento = $this->createCompanyDocumentType();

        $documentoAprobar = Documento::create([
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 2,
            'archivo_nombre_original' => 'aprobar.pdf',
            'archivo_ruta' => 'documentos/aprobar.pdf',
            'archivo_tamano_kb' => 200,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('documentos.aprobaciones'))
            ->post(route('documentos.approve', $documentoAprobar))
            ->assertRedirect(route('documentos.aprobaciones'));

        $this->assertDatabaseHas(Documento::class, [
            'id' => $documentoAprobar->id,
            'estado' => 'aprobado',
            'validado_por' => $admin->id,
            'motivo_rechazo' => null,
        ]);

        $documentoRechazar = Documento::create([
            'contratista_id' => $contratista->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'periodo_ano' => 2026,
            'periodo_mes' => 3,
            'archivo_nombre_original' => 'rechazar.pdf',
            'archivo_ruta' => 'documentos/rechazar.pdf',
            'archivo_tamano_kb' => 200,
            'estado' => 'pendiente_validacion',
            'observaciones' => null,
            'motivo_rechazo' => null,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
            'validado_por' => null,
            'validado_at' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('documentos.aprobaciones'))
            ->post(route('documentos.reject', $documentoRechazar), [
                'motivo_rechazo' => 'Archivo ilegible.',
            ])
            ->assertRedirect(route('documentos.aprobaciones'));

        $this->assertDatabaseHas(Documento::class, [
            'id' => $documentoRechazar->id,
            'estado' => 'rechazado',
            'validado_por' => $admin->id,
            'motivo_rechazo' => 'Archivo ilegible.',
        ]);
    }

    private function createCompanyDocumentType(): TipoDocumento
    {
        return TipoDocumento::create([
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
    }
}
