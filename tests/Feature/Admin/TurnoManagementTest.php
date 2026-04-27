<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Turno;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TurnoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_turnos_index_for_selected_date(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $ubicacion = Ubicacion::factory()->create([
            'nombre' => 'Packing Central',
            'codigo' => 'PACK-CENTRAL',
        ]);

        $turno = Turno::create([
            'fecha' => '2026-04-27',
            'nombre' => 'Turno Dia',
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'activo' => true,
        ]);
        $turno->ubicaciones()->sync([$ubicacion->id]);

        $this->actingAs($admin)
            ->get(route('admin.turnos.index', ['fecha' => '2026-04-27']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/turnos/index')
                ->where('filters.fecha', '2026-04-27')
                ->has('turnos', 1)
                ->where('turnos.0.nombre', 'Turno Dia')
                ->where('turnos.0.ubicaciones.0.codigo', 'PACK-CENTRAL')
                ->has('ubicaciones', 1)
            );
    }

    public function test_admin_can_create_turno_for_multiple_ubicaciones(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $ubicacionUno = Ubicacion::factory()->create([
            'nombre' => 'Packing 1',
            'codigo' => 'PACK-1',
        ]);
        $ubicacionDos = Ubicacion::factory()->create([
            'nombre' => 'Packing 2',
            'codigo' => 'PACK-2',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.turnos.index'))
            ->post(route('admin.turnos.store'), [
                'fecha' => '2026-04-27',
                'nombre' => 'Turno Dia',
                'hora_inicio' => '08:00',
                'hora_fin' => '18:00',
                'descripcion' => 'Operacion diurna',
                'activo' => true,
                'ubicacion_ids' => [$ubicacionUno->id, $ubicacionDos->id],
            ]);

        $response->assertRedirect(route('admin.turnos.index', ['fecha' => '2026-04-27']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('turnos', [
            'fecha' => '2026-04-27',
            'nombre' => 'Turno Dia',
            'descripcion' => 'Operacion diurna',
            'activo' => true,
        ]);

        $turno = Turno::query()
            ->whereDate('fecha', '2026-04-27')
            ->where('nombre', 'Turno Dia')
            ->firstOrFail();

        $this->assertSame('08:00:00', $turno->hora_inicio->format('H:i:s'));
        $this->assertSame('18:00:00', $turno->hora_fin->format('H:i:s'));

        $this->assertDatabaseHas('turno_ubicacion', [
            'ubicacion_id' => $ubicacionUno->id,
        ]);
        $this->assertDatabaseHas('turno_ubicacion', [
            'ubicacion_id' => $ubicacionDos->id,
        ]);
    }

    public function test_admin_can_clone_turnos_from_specific_date(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $ubicacionUno = Ubicacion::factory()->create([
            'nombre' => 'Packing Norte',
            'codigo' => 'PACK-NORTE',
        ]);
        $ubicacionDos = Ubicacion::factory()->create([
            'nombre' => 'Packing Sur',
            'codigo' => 'PACK-SUR',
        ]);

        $sourceTurno = Turno::create([
            'fecha' => '2026-04-26',
            'nombre' => 'Turno Noche',
            'hora_inicio' => '18:30',
            'hora_fin' => '08:00',
            'descripcion' => 'Operacion nocturna',
            'activo' => true,
        ]);
        $sourceTurno->ubicaciones()->sync([$ubicacionUno->id, $ubicacionDos->id]);

        $response = $this->actingAs($admin)
            ->from(route('admin.turnos.index', ['fecha' => '2026-04-27']))
            ->post(route('admin.turnos.clone'), [
                'source_date' => '2026-04-26',
                'target_date' => '2026-04-27',
            ]);

        $response->assertRedirect(route('admin.turnos.index', ['fecha' => '2026-04-27']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('turnos', [
            'fecha' => '2026-04-27',
            'nombre' => 'Turno Noche',
            'descripcion' => 'Operacion nocturna',
            'activo' => true,
        ]);

        $clonedTurno = Turno::query()
            ->whereDate('fecha', '2026-04-27')
            ->where('nombre', 'Turno Noche')
            ->firstOrFail();

        $this->assertSame('18:30:00', $clonedTurno->hora_inicio->format('H:i:s'));
        $this->assertSame('08:00:00', $clonedTurno->hora_fin->format('H:i:s'));
        $this->assertEqualsCanonicalizing(
            [$ubicacionUno->id, $ubicacionDos->id],
            $clonedTurno->ubicaciones()->pluck('ubicaciones.id')->all(),
        );
    }

    public function test_clone_replaces_existing_target_turno_without_creating_duplicates(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $sourceLocation = Ubicacion::factory()->create(['codigo' => 'SRC-LOC']);
        $oldTargetLocation = Ubicacion::factory()->create(['codigo' => 'OLD-LOC']);

        $sourceTurno = Turno::create([
            'fecha' => '2026-04-26',
            'nombre' => 'Turno Dia',
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'activo' => true,
        ]);
        $sourceTurno->ubicaciones()->sync([$sourceLocation->id]);

        $targetTurno = Turno::create([
            'fecha' => '2026-04-27',
            'nombre' => 'Turno Dia',
            'hora_inicio' => '09:00',
            'hora_fin' => '17:00',
            'activo' => false,
        ]);
        $targetTurno->ubicaciones()->sync([$oldTargetLocation->id]);

        $this->actingAs($admin)
            ->post(route('admin.turnos.clone'), [
                'source_date' => '2026-04-26',
                'target_date' => '2026-04-27',
            ])
            ->assertRedirect(route('admin.turnos.index', ['fecha' => '2026-04-27']));

        $this->assertDatabaseCount('turnos', 2);

        $targetTurno->refresh();

        $this->assertSame('08:00:00', $targetTurno->hora_inicio->format('H:i:s'));
        $this->assertTrue($targetTurno->activo);
        $this->assertEqualsCanonicalizing(
            [$sourceLocation->id],
            $targetTurno->ubicaciones()->pluck('ubicaciones.id')->all(),
        );
    }

    public function test_non_admin_cannot_manage_turnos(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
        ]);

        $this->actingAs($user)
            ->get(route('admin.turnos.index'))
            ->assertForbidden();
    }
}
