<?php

namespace Tests\Feature;

use App\Models\Contratista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlAccessLogIngestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ingests_control_access_logs_and_creates_worker_for_known_contractor(): void
    {
        $contratista = Contratista::factory()->create([
            'razon_social' => 'Valsán Ltda',
            'nombre_fantasia' => 'Valsan',
            'estado' => 'activo',
        ]);

        $response = $this->postJson('/api/control-access-logs', [
            'records' => [
                [
                    'personal_id' => '12345678',
                    'nombre' => 'Juan Perez Soto',
                    'departamento' => 'valsan noche',
                    'primera_entrada' => '2026-04-26 07:55:00',
                    'ultima_salida' => '2026-04-26 18:10:00',
                    'pin' => 'PIN-123',
                    'max_event_id_pair' => 'entry-1/exit-1',
                    'pair_max_time' => '2026-04-26 18:10:00',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('stored', 1);

        $this->assertDatabaseHas('control_access_logs', [
            'fecha' => '2026-04-26 00:00:00',
            'personal_id' => '12345678',
            'nombre' => 'Juan Perez Soto',
            'departamento' => 'Valsán Ltda',
            'primera_entrada' => '2026-04-26 07:55:00',
            'ultima_salida' => '2026-04-26 18:10:00',
            'pin' => 'PIN-123',
        ]);

        $this->assertDatabaseHas('control_access_presences', [
            'personal_id' => '12345678',
            'nombre' => 'Juan Perez Soto',
            'departamento' => 'Valsán Ltda',
            'last_entry_at' => '2026-04-26 07:55:00',
            'last_exit_at' => '2026-04-26 18:10:00',
            'last_event_id_pair' => 'entry-1/exit-1',
            'pin' => 'PIN-123',
        ]);

        $this->assertDatabaseHas('trabajadores', [
            'id' => '12345678',
            'documento' => '12345678-5',
            'nombre' => 'Juan',
            'apellido' => 'Perez Soto',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
        ]);
    }

    public function test_it_merges_same_person_and_operational_date_using_first_entry_and_last_exit(): void
    {
        $response = $this->postJson('/api/control-access-logs', [
            'records' => [
                [
                    'personal_id' => '11111111',
                    'nombre' => 'Ana Merge',
                    'departamento' => 'Packing',
                    'fecha_operativa' => '2026-04-26',
                    'primera_entrada' => '2026-04-26 08:00:00',
                    'ultima_salida' => '2026-04-26 12:00:00',
                ],
                [
                    'personal_id' => '11111111',
                    'nombre' => 'Ana Merge',
                    'departamento' => 'Packing',
                    'fecha_operativa' => '2026-04-26',
                    'primera_entrada' => '2026-04-26 07:30:00',
                    'ultima_salida' => '2026-04-26 18:00:00',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('stored', 2);

        $this->assertDatabaseCount('control_access_logs', 1);
        $this->assertDatabaseHas('control_access_logs', [
            'fecha' => '2026-04-26 00:00:00',
            'personal_id' => '11111111',
            'primera_entrada' => '2026-04-26 07:30:00',
            'ultima_salida' => '2026-04-26 18:00:00',
        ]);
    }

    public function test_it_assigns_early_entry_to_previous_night_operational_date(): void
    {
        $response = $this->postJson('/api/control-access-logs', [
            'records' => [
                [
                    'personal_id' => '22222222',
                    'nombre' => 'Nocturno',
                    'departamento' => 'Turno Noche',
                    'primera_entrada' => '2026-04-27 06:45:00',
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('control_access_logs', [
            'fecha' => '2026-04-26 00:00:00',
            'personal_id' => '22222222',
            'primera_entrada' => '2026-04-27 06:45:00',
        ]);
    }

    public function test_it_rejects_empty_payloads(): void
    {
        $response = $this->postJson('/api/control-access-logs', [
            'records' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['records']);
    }
}
