<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\TipoFaena;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaenaTipoFaenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_faena_requires_tipo_faena_and_persists_it(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Planta',
            'codigo' => 'PLANTA',
            'descripcion' => null,
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('faenas.store'), [
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Faena Norte',
            'codigo' => 'FN-001',
            'descripcion' => 'Turno norte',
            'ubicacion' => 'Copiapo',
            'fecha_inicio' => '2026-02-16',
            'fecha_termino' => null,
        ]);

        $response->assertRedirect(route('faenas.index'));

        $this->assertDatabaseHas('faenas', [
            'nombre' => 'Faena Norte',
            'codigo' => 'FN-001',
            'tipo_faena_id' => $tipoFaena->id,
        ]);
    }

    public function test_contratista_cannot_create_faena(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Planta',
            'codigo' => 'PLANTA',
            'descripcion' => null,
            'activo' => true,
        ]);

        $this->actingAs($user)
            ->get(route('faenas.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('faenas.store'), [
                'tipo_faena_id' => $tipoFaena->id,
                'nombre' => 'Faena Contratista',
                'codigo' => 'FC-001',
                'descripcion' => 'No debe crearse',
                'ubicacion' => 'Planta Sur',
                'fecha_inicio' => '2026-02-16',
                'fecha_termino' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('faenas', [
            'codigo' => 'FC-001',
        ]);
    }
}
