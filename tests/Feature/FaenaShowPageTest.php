<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\Faena;
use App\Models\TipoFaena;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaenaShowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_renders_with_faena_data_and_workers(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $contratista = Contratista::factory()->create();

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);

        $faena = Faena::create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Temporada 2026',
            'codigo' => 'TEMP_2026',
            'descripcion' => 'Faena principal',
            'ubicacion' => 'Bodega Norte',
            'estado' => 'activa',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => null,
        ]);

        $trabajador = Trabajador::create([
            'id' => '12345678',
            'documento' => '12345678-5',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $trabajadorDisponible = Trabajador::create([
            'id' => '87654321',
            'documento' => '87654321-9',
            'nombre' => 'Maria',
            'apellido' => 'Lopez',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $trabajadorInactivo = Trabajador::create([
            'id' => '11223344',
            'documento' => '11223344-6',
            'nombre' => 'Pedro',
            'apellido' => 'Gonzalez',
            'contratista_id' => $contratista->id,
            'estado' => 'inactivo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $faena->trabajadores()->attach($trabajador->id, [
            'fecha_asignacion' => now()->toDateString(),
            'fecha_desasignacion' => null,
        ]);

        $response = $this->actingAs($user)->get(route('faenas.show', $faena));

        $response->assertOk();
        $response->assertViewHas('page', function (array $page) use ($faena, $trabajador, $trabajadorDisponible, $trabajadorInactivo) {
            $trabajadoresDisponibles = collect($page['props']['trabajadoresDisponibles'] ?? [])
                ->pluck('id')
                ->all();

            return ($page['component'] ?? null) === 'faenas/show'
                && ($page['props']['faena']['id'] ?? null) === $faena->id
                && ($page['props']['faena']['nombre'] ?? null) === 'Temporada 2026'
                && count($page['props']['faena']['trabajadores'] ?? []) === 1
                && in_array($trabajadorDisponible->id, $trabajadoresDisponibles, true)
                && ! in_array($trabajador->id, $trabajadoresDisponibles, true)
                && ! in_array($trabajadorInactivo->id, $trabajadoresDisponibles, true);
        });
    }

    public function test_admin_can_assign_and_unassign_a_worker_in_faena(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $contratista = Contratista::factory()->create();
        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);
        $faena = Faena::create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Faena Centro',
            'codigo' => 'FAENA_CENTRO',
            'descripcion' => null,
            'ubicacion' => null,
            'estado' => 'activa',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => null,
        ]);
        $trabajador = Trabajador::create([
            'id' => '22334455',
            'documento' => '22334455-1',
            'nombre' => 'Jose',
            'apellido' => 'Diaz',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->from(route('faenas.show', $faena))
            ->post(route('faenas.assign', $faena), [
                'trabajador_id' => $trabajador->id,
            ])
            ->assertRedirect(route('faenas.show', $faena));

        $this->assertDatabaseHas('faena_trabajador', [
            'faena_id' => $faena->id,
            'trabajador_id' => $trabajador->id,
            'fecha_desasignacion' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('faenas.show', $faena))
            ->delete(route('faenas.unassign', ['faena' => $faena, 'trabajador' => $trabajador->id]))
            ->assertRedirect(route('faenas.show', $faena));

        $this->assertDatabaseMissing('faena_trabajador', [
            'faena_id' => $faena->id,
            'trabajador_id' => $trabajador->id,
            'fecha_desasignacion' => null,
        ]);
    }

    public function test_reassigning_an_unassigned_worker_reuses_the_existing_pivot_record(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $contratista = Contratista::factory()->create();
        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);
        $faena = Faena::create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Faena Sur',
            'codigo' => 'FAENA_SUR',
            'descripcion' => null,
            'ubicacion' => null,
            'estado' => 'activa',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => null,
        ]);
        $trabajador = Trabajador::create([
            'id' => '33445566',
            'documento' => '33445566-4',
            'nombre' => 'Luis',
            'apellido' => 'Rojas',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $faena->trabajadores()->attach($trabajador->id, [
            'fecha_asignacion' => now()->subDay()->toDateString(),
            'fecha_desasignacion' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->from(route('faenas.show', $faena))
            ->post(route('faenas.assign', $faena), [
                'trabajador_id' => $trabajador->id,
            ])
            ->assertRedirect(route('faenas.show', $faena));

        $this->assertDatabaseCount('faena_trabajador', 1);
        $this->assertDatabaseHas('faena_trabajador', [
            'faena_id' => $faena->id,
            'trabajador_id' => $trabajador->id,
            'fecha_desasignacion' => null,
        ]);

        $pivotFechaAsignacion = DB::table('faena_trabajador')
            ->where('faena_id', $faena->id)
            ->where('trabajador_id', $trabajador->id)
            ->value('fecha_asignacion');

        $this->assertIsString($pivotFechaAsignacion);
        $this->assertStringStartsWith(now()->toDateString(), $pivotFechaAsignacion);
    }

    public function test_non_admin_user_cannot_assign_worker_from_another_contratista(): void
    {
        $contratistaA = Contratista::factory()->create();
        $contratistaB = Contratista::factory()->create();
        $usuarioContratista = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratistaA->id,
        ]);
        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);
        $faena = Faena::create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Faena Norte',
            'codigo' => 'FAENA_NORTE',
            'descripcion' => null,
            'ubicacion' => null,
            'estado' => 'activa',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => null,
        ]);
        $trabajadorOtroContratista = Trabajador::create([
            'id' => '44556677',
            'documento' => '44556677-0',
            'nombre' => 'Carlos',
            'apellido' => 'Moya',
            'contratista_id' => $contratistaB->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $this->actingAs($usuarioContratista)
            ->post(route('faenas.assign', $faena), [
                'trabajador_id' => $trabajadorOtroContratista->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('faena_trabajador', [
            'faena_id' => $faena->id,
            'trabajador_id' => $trabajadorOtroContratista->id,
        ]);
    }

    public function test_contratista_user_cannot_edit_or_update_faena(): void
    {
        $contratista = Contratista::factory()->create();
        $usuarioContratista = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => 'Procesos de packing',
            'activo' => true,
        ]);

        $faena = Faena::create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Faena Inicial',
            'codigo' => 'FAENA_INICIAL',
            'descripcion' => 'Descripcion inicial',
            'ubicacion' => 'Bodega A',
            'estado' => 'activa',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => null,
        ]);

        $this->actingAs($usuarioContratista)
            ->get(route('faenas.edit', $faena))
            ->assertForbidden();

        $this->actingAs($usuarioContratista)
            ->patch(route('faenas.update', $faena), [
                'tipo_faena_id' => $tipoFaena->id,
                'nombre' => 'Faena Editada',
                'codigo' => 'FAENA_INICIAL',
                'descripcion' => 'Descripcion editada',
                'ubicacion' => 'Bodega B',
                'estado' => 'activa',
                'fecha_inicio' => now()->toDateString(),
                'fecha_termino' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('faenas', [
            'id' => $faena->id,
            'nombre' => 'Faena Inicial',
            'descripcion' => 'Descripcion inicial',
            'ubicacion' => 'Bodega A',
        ]);
    }
}
