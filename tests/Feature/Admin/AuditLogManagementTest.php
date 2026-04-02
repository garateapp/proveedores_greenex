<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Contratista;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_logs_index(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'contratista_id' => null,
        ]);

        $actor = User::factory()->create([
            'role' => UserRole::Supervisor,
            'contratista_id' => null,
        ]);

        AuditLog::query()->create([
            'auditable_type' => Contratista::class,
            'auditable_id' => '123',
            'event' => 'updated',
            'user_id' => $actor->id,
            'old_values' => ['estado' => 'activo'],
            'new_values' => ['estado' => 'bloqueado'],
            'url' => 'http://localhost/admin/contratistas/123/edit',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', [
                'search' => '123',
                'event' => 'updated',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/audit-logs/index')
                ->has('auditLogs.data', 1)
                ->where('auditLogs.data.0.event', 'updated')
                ->where('auditLogs.data.0.auditable_id', '123')
                ->where('auditLogs.data.0.user.name', $actor->name)
            );
    }

    public function test_non_admin_cannot_view_audit_logs_index(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Supervisor,
            'contratista_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }
}
