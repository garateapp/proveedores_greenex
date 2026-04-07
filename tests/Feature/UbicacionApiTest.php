<?php

namespace Tests\Feature;

use App\Models\Ubicacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UbicacionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_active_ubicaciones(): void
    {
        // Create test data
        $unitec1 = Ubicacion::factory()->principal()->create(['nombre' => 'UNITEC 1', 'orden' => 1]);
        $unitec2 = Ubicacion::factory()->principal()->create(['nombre' => 'UNITEC 2', 'orden' => 2]);

        Ubicacion::factory()->create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Filtro',
            'tipo' => 'secundaria',
            'orden' => 1,
        ]);

        Ubicacion::factory()->create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Altillo',
            'tipo' => 'secundaria',
            'orden' => 2,
        ]);

        // Create an inactive ubicacion
        Ubicacion::factory()->inactiva()->create();

        $response = $this->getJson('/api/v1/ubicaciones');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(4, 'data') // All active ones (2 principales + 2 secundarias)
            ->assertJsonFragment(['nombre' => 'UNITEC 1'])
            ->assertJsonFragment(['nombre' => 'UNITEC 2']);
    }

    public function test_can_filter_ubicaciones_by_tipo(): void
    {
        $unitec1 = Ubicacion::factory()->principal()->create(['nombre' => 'UNITEC 1']);
        Ubicacion::factory()->create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Filtro',
            'tipo' => 'secundaria',
        ]);

        $response = $this->getJson('/api/v1/ubicaciones?tipo=principal');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['nombre' => 'UNITEC 1']);
    }

    public function test_can_filter_ubicaciones_by_padre_id(): void
    {
        $unitec1 = Ubicacion::factory()->principal()->create(['nombre' => 'UNITEC 1']);
        Ubicacion::factory()->create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Filtro',
            'tipo' => 'secundaria',
        ]);
        Ubicacion::factory()->create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Altillo',
            'tipo' => 'secundaria',
        ]);

        $response = $this->getJson('/api/v1/ubicaciones?padre_id='.$unitec1->id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_ubicaciones(): void
    {
        Ubicacion::factory()->create(['nombre' => 'UNITEC 1', 'codigo' => 'UNI1', 'tipo' => 'principal']);
        Ubicacion::factory()->create(['nombre' => 'UNITEC 2', 'codigo' => 'UNI2', 'tipo' => 'principal']);
        Ubicacion::factory()->create([
            'nombre' => 'Filtro',
            'codigo' => 'FILTRO',
            'tipo' => 'secundaria',
        ]);

        $response = $this->getJson('/api/v1/ubicaciones?search=UNITEC');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $response = $this->getJson('/api/v1/ubicaciones?search=FILTRO');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['nombre' => 'Filtro']);
    }

    public function test_can_get_single_ubicacion(): void
    {
        $unitec1 = Ubicacion::factory()->principal()->create(['nombre' => 'UNITEC 1']);

        $response = $this->getJson('/api/v1/ubicaciones/'.$unitec1->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $unitec1->id,
                    'nombre' => 'UNITEC 1',
                ],
            ]);
    }

    public function test_cannot_get_inactive_ubicacion(): void
    {
        $inactive = Ubicacion::factory()->inactiva()->create();

        $response = $this->getJson('/api/v1/ubicaciones/'.$inactive->id);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Ubicación no encontrada',
            ]);
    }

    public function test_can_create_ubicacion(): void
    {
        $data = [
            'nombre' => 'Nueva Ubicación',
            'codigo' => 'NEW-001',
            'descripcion' => 'Test descripción',
            'orden' => 5,
        ];

        $response = $this->postJson('/api/v1/ubicaciones', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Ubicación creada exitosamente',
                'data' => [
                    'nombre' => 'Nueva Ubicación',
                    'codigo' => 'NEW-001',
                    'tipo' => 'principal',
                ],
            ]);

        $this->assertDatabaseHas('ubicaciones', [
            'nombre' => 'Nueva Ubicación',
            'codigo' => 'NEW-001',
        ]);
    }

    public function test_can_create_secundaria_ubicacion(): void
    {
        $padre = Ubicacion::factory()->principal()->create();

        $data = [
            'padre_id' => $padre->id,
            'nombre' => 'Sub Ubicación',
            'codigo' => 'SUB-001',
        ];

        $response = $this->postJson('/api/v1/ubicaciones', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nombre' => 'Sub Ubicación',
                    'tipo' => 'secundaria',
                ],
            ]);
    }

    public function test_can_update_ubicacion(): void
    {
        $ubicacion = Ubicacion::factory()->create(['tipo' => 'principal']);

        $response = $this->putJson('/api/v1/ubicaciones/'.$ubicacion->id, [
            'nombre' => 'Nombre Actualizado',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ubicación actualizada exitosamente',
                'data' => [
                    'nombre' => 'Nombre Actualizado',
                ],
            ]);

        $this->assertDatabaseHas('ubicaciones', [
            'id' => $ubicacion->id,
            'nombre' => 'Nombre Actualizado',
        ]);
    }

    public function test_can_delete_ubicacion_without_children(): void
    {
        $ubicacion = Ubicacion::factory()->create(['tipo' => 'principal']);

        $response = $this->deleteJson('/api/v1/ubicaciones/'.$ubicacion->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ubicación eliminada exitosamente',
            ]);

        $this->assertDatabaseMissing('ubicaciones', [
            'id' => $ubicacion->id,
        ]);
    }

    public function test_cannot_delete_ubicacion_with_children(): void
    {
        $padre = Ubicacion::factory()->create(['tipo' => 'principal']);
        Ubicacion::factory()->create(['padre_id' => $padre->id, 'tipo' => 'secundaria']);

        $response = $this->deleteJson('/api/v1/ubicaciones/'.$padre->id);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No se puede eliminar una ubicación que tiene sub-ubicaciones',
            ]);
    }

    public function test_can_get_ubicaciones_principales(): void
    {
        $unitec1 = Ubicacion::factory()->create(['nombre' => 'UNITEC 1', 'orden' => 1, 'tipo' => 'principal']);
        $unitec2 = Ubicacion::factory()->create(['nombre' => 'UNITEC 2', 'orden' => 2, 'tipo' => 'principal']);

        Ubicacion::factory()->create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Filtro',
            'tipo' => 'secundaria',
        ]);

        Ubicacion::factory()->create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Altillo',
            'tipo' => 'secundaria',
        ]);

        $response = $this->getJson('/api/v1/ubicaciones/principales');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'nombre' => 'UNITEC 1',
            ]);
    }
}
