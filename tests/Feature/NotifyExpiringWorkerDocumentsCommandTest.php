<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\DocumentoTrabajador;
use App\Models\TipoDocumento;
use App\Models\Trabajador;
use App\Models\User;
use App\Notifications\DocumentoTrabajadorPorVencerNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyExpiringWorkerDocumentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_notifies_contratista_users_for_worker_documents_expiring_in_seven_days(): void
    {
        Notification::fake();

        $contratista = Contratista::factory()->create([
            'estado' => 'activo',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
            'is_active' => true,
        ]);

        $trabajador = Trabajador::create([
            'id' => '33333333',
            'documento' => '33333333-3',
            'nombre' => 'Carla',
            'apellido' => 'Nuñez',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->subMonths(4),
        ]);

        $tipoDocumento = TipoDocumento::create([
            'nombre' => 'Licencia de Conducir',
            'codigo' => 'LICENCIA',
            'descripcion' => null,
            'periodicidad' => 'anual',
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => 365,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 3072,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);

        DocumentoTrabajador::create([
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo_nombre_original' => 'licencia.pdf',
            'archivo_ruta' => 'docs/licencia.pdf',
            'archivo_tamano_kb' => 220,
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'cargado_por' => $user->id,
        ]);

        $this->artisan('alertas:notificar-documentos-trabajadores')
            ->assertSuccessful();

        Notification::assertSentTo($user, DocumentoTrabajadorPorVencerNotification::class);

        $this->assertDatabaseHas('alertas', [
            'contratista_id' => $contratista->id,
            'tipo' => 'documento_por_vencer',
            'titulo' => 'Documento de trabajador próximo a vencer',
            'prioridad' => 'alta',
        ]);
    }

    public function test_command_does_not_send_notifications_when_no_documents_match_target_date(): void
    {
        Notification::fake();

        $contratista = Contratista::factory()->create([
            'estado' => 'activo',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
            'is_active' => true,
        ]);

        $trabajador = Trabajador::create([
            'id' => '44444444',
            'documento' => '44444444-4',
            'nombre' => 'Diego',
            'apellido' => 'Paz',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->subMonths(2),
        ]);

        $tipoDocumento = TipoDocumento::create([
            'nombre' => 'Seguro Complementario',
            'codigo' => 'SEGURO_COMP',
            'descripcion' => null,
            'periodicidad' => 'anual',
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => 365,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 3072,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);

        DocumentoTrabajador::create([
            'trabajador_id' => $trabajador->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'archivo_nombre_original' => 'seguro.pdf',
            'archivo_ruta' => 'docs/seguro.pdf',
            'archivo_tamano_kb' => 200,
            'fecha_vencimiento' => now()->addDays(12)->toDateString(),
            'cargado_por' => $user->id,
        ]);

        $this->artisan('alertas:notificar-documentos-trabajadores')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}
