<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\Faena;
use App\Models\TipoFaena;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrabajadorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_worker_contratista_and_sync_faenas(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $oldContratista = Contratista::factory()->create();
        $newContratista = Contratista::factory()->create();
        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);
        $faenaA = $this->createFaena($tipoFaena, 'Faena A', 'FAENA_A');
        $faenaB = $this->createFaena($tipoFaena, 'Faena B', 'FAENA_B');
        $trabajador = $this->createTrabajador($oldContratista, '77889900', '77889900-3');

        $response = $this->actingAs($admin)
            ->from(route('trabajadores.edit', $trabajador->id))
            ->patch(route('trabajadores.update', $trabajador->id), [
                'nombre' => 'Jose',
                'apellido' => 'Actualizado',
                'email' => 'jose@example.com',
                'telefono' => '123456789',
                'estado' => 'activo',
                'fecha_ingreso' => now()->toDateString(),
                'observaciones' => 'Observacion',
                'contratista_id' => $newContratista->id,
                'faena_ids' => [$faenaA->id, $faenaB->id],
            ]);

        $response->assertRedirect(route('trabajadores.index'));

        $this->assertDatabaseHas('trabajadores', [
            'id' => $trabajador->id,
            'contratista_id' => $newContratista->id,
            'apellido' => 'Actualizado',
        ]);
        $this->assertDatabaseHas('faena_contratista', [
            'faena_id' => $faenaA->id,
            'contratista_id' => $newContratista->id,
        ]);
        $this->assertDatabaseHas('faena_contratista', [
            'faena_id' => $faenaB->id,
            'contratista_id' => $newContratista->id,
        ]);
        $this->assertDatabaseHas('faena_trabajador', [
            'faena_id' => $faenaA->id,
            'trabajador_id' => $trabajador->id,
            'fecha_desasignacion' => null,
        ]);
        $this->assertDatabaseHas('faena_trabajador', [
            'faena_id' => $faenaB->id,
            'trabajador_id' => $trabajador->id,
            'fecha_desasignacion' => null,
        ]);
    }

    public function test_admin_edit_page_includes_contratistas_and_active_faenas(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $contratista = Contratista::factory()->create([
            'estado' => 'activo',
        ]);
        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);
        $faena = $this->createFaena($tipoFaena, 'Faena Visible', 'FAENA_VISIBLE');
        $trabajador = $this->createTrabajador($contratista, '10101010', '10101010-1');

        $response = $this->actingAs($admin)->get(route('trabajadores.edit', $trabajador->id));

        $response->assertOk();
        $response->assertViewHas('page', function (array $page) use ($contratista, $faena) {
            return ($page['component'] ?? null) === 'trabajadores/edit'
                && collect($page['props']['contratistas'] ?? [])->contains(fn (array $row) => $row['value'] === $contratista->id)
                && collect($page['props']['faenasDisponibles'] ?? [])->contains(fn (array $row) => $row['id'] === $faena->id);
        });
    }

    public function test_contratista_can_sync_own_worker_between_participating_faenas(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);
        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);
        $faenaA = $this->createFaena($tipoFaena, 'Faena C', 'FAENA_C');
        $faenaB = $this->createFaena($tipoFaena, 'Faena D', 'FAENA_D');
        $trabajador = $this->createTrabajador($contratista, '88990011', '88990011-6');

        $faenaA->contratistas()->attach($contratista->id);
        $faenaB->contratistas()->attach($contratista->id);
        $faenaA->trabajadores()->attach($trabajador->id, [
            'fecha_asignacion' => now()->subDay()->toDateString(),
            'fecha_desasignacion' => null,
        ]);

        $response = $this->actingAs($user)
            ->from(route('trabajadores.edit', $trabajador->id))
            ->patch(route('trabajadores.update', $trabajador->id), [
                'nombre' => $trabajador->nombre,
                'apellido' => $trabajador->apellido,
                'email' => null,
                'telefono' => null,
                'estado' => 'activo',
                'fecha_ingreso' => now()->toDateString(),
                'observaciones' => null,
                'faena_ids' => [$faenaB->id],
            ]);

        $response->assertRedirect(route('trabajadores.index'));

        $this->assertDatabaseMissing('faena_trabajador', [
            'faena_id' => $faenaA->id,
            'trabajador_id' => $trabajador->id,
            'fecha_desasignacion' => null,
        ]);
        $this->assertDatabaseHas('faena_trabajador', [
            'faena_id' => $faenaB->id,
            'trabajador_id' => $trabajador->id,
            'fecha_desasignacion' => null,
        ]);
    }

    public function test_contratista_edit_page_only_includes_participating_faenas(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);
        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);
        $faenaPermitida = $this->createFaena($tipoFaena, 'Faena Permitida', 'FAENA_PERM');
        $faenaNoPermitida = $this->createFaena($tipoFaena, 'Faena No Permitida', 'FAENA_NOPERM');
        $trabajador = $this->createTrabajador($contratista, '20202020', '20202020-2');

        $faenaPermitida->contratistas()->attach($contratista->id);

        $response = $this->actingAs($user)->get(route('trabajadores.edit', $trabajador->id));

        $response->assertOk();
        $response->assertViewHas('page', function (array $page) use ($faenaPermitida, $faenaNoPermitida) {
            $faenasDisponibles = collect($page['props']['faenasDisponibles'] ?? []);

            return ($page['component'] ?? null) === 'trabajadores/edit'
                && $faenasDisponibles->contains(fn (array $row) => $row['id'] === $faenaPermitida->id)
                && ! $faenasDisponibles->contains(fn (array $row) => $row['id'] === $faenaNoPermitida->id);
        });
    }

    public function test_contratista_cannot_sync_worker_to_non_participating_faena(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);
        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);
        $faena = $this->createFaena($tipoFaena, 'Faena E', 'FAENA_E');
        $trabajador = $this->createTrabajador($contratista, '99001122', '99001122-8');

        $response = $this->actingAs($user)
            ->from(route('trabajadores.edit', $trabajador->id))
            ->patch(route('trabajadores.update', $trabajador->id), [
                'nombre' => $trabajador->nombre,
                'apellido' => $trabajador->apellido,
                'email' => null,
                'telefono' => null,
                'estado' => 'activo',
                'fecha_ingreso' => now()->toDateString(),
                'observaciones' => null,
                'faena_ids' => [$faena->id],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('faena_trabajador', [
            'faena_id' => $faena->id,
            'trabajador_id' => $trabajador->id,
        ]);
    }

    private function createFaena(TipoFaena $tipoFaena, string $nombre, string $codigo): Faena
    {
        return Faena::create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => $nombre,
            'codigo' => $codigo,
            'descripcion' => null,
            'ubicacion' => null,
            'estado' => 'activa',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => null,
        ]);
    }

    private function createTrabajador(Contratista $contratista, string $id, string $documento): Trabajador
    {
        return Trabajador::create([
            'id' => $id,
            'documento' => $documento,
            'nombre' => 'Jose',
            'apellido' => 'Perez',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);
    }
}
