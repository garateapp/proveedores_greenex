<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asistencia;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AsistenciaStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_sincronizado_records_set_timestamp_when_marked_as_synced(): void
    {
        $now = Carbon::parse('2024-01-15 08:30:00');
        Carbon::setTestNow($now);

        $contratista = Contratista::factory()->create();

        $user = User::factory()->create([
            'contratista_id' => $contratista->id,
            'role' => UserRole::Contratista,
        ]);

        $trabajador = Trabajador::create([
            'id' => '12345678',
            'documento' => '12345678-5',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($user)->post(route('asistencias.store'), [
            'trabajador_id' => $trabajador->id,
            'tipo' => 'entrada',
            'fecha_hora' => $now->toDateTimeString(),
            'sincronizado' => true,
        ]);

        $response->assertRedirect();

        $asistencia = Asistencia::first();

        $this->assertTrue($asistencia->sincronizado);
        $this->assertNotNull($asistencia->sincronizado_at);
        $this->assertSame($now->toDateTimeString(), $asistencia->sincronizado_at->toDateTimeString());

        Carbon::setTestNow();
    }
}
