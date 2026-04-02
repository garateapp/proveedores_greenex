<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\TipoFaena;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditable_models_write_create_update_delete_events(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin);

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Inicial',
            'codigo' => 'INICIAL',
            'descripcion' => null,
            'activo' => true,
        ]);

        $tipoFaena->update([
            'nombre' => 'Actualizada',
        ]);

        $tipoFaena->delete();

        $events = AuditLog::query()
            ->where('auditable_type', TipoFaena::class)
            ->where('auditable_id', (string) $tipoFaena->id)
            ->pluck('event')
            ->all();

        $this->assertContains('created', $events);
        $this->assertContains('updated', $events);
        $this->assertContains('deleted', $events);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => TipoFaena::class,
            'auditable_id' => (string) $tipoFaena->id,
            'event' => 'created',
            'user_id' => $admin->id,
        ]);
    }
}
