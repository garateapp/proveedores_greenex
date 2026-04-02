<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\DocumentoTrabajador;
use App\Models\Faena;
use App\Models\TipoDocumento;
use App\Models\TipoFaena;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_integral_compliance_metrics(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'contratista_id' => null,
        ]);

        $contratista = Contratista::factory()->create([
            'estado' => 'activo',
        ]);

        $workerOnTrack = Trabajador::create([
            'id' => '11111111',
            'documento' => '11111111-1',
            'nombre' => 'Ana',
            'apellido' => 'Mora',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->subMonths(3),
        ]);

        $workerWithCriticalMissing = Trabajador::create([
            'id' => '22222222',
            'documento' => '22222222-2',
            'nombre' => 'Beto',
            'apellido' => 'Rios',
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->subMonths(2),
        ]);

        $tipoFaena = TipoFaena::create([
            'nombre' => 'Packing',
            'codigo' => 'PACKING',
            'descripcion' => null,
            'activo' => true,
        ]);

        $faena = Faena::create([
            'tipo_faena_id' => $tipoFaena->id,
            'nombre' => 'Packing Norte',
            'codigo' => 'PACKING_NORTE',
            'descripcion' => null,
            'ubicacion' => null,
            'estado' => 'activa',
            'fecha_inicio' => now()->subMonths(1)->toDateString(),
            'fecha_termino' => null,
        ]);

        $faena->trabajadores()->attach($workerOnTrack->id, [
            'fecha_asignacion' => now()->subDays(15)->toDateString(),
            'fecha_desasignacion' => null,
        ]);
        $faena->trabajadores()->attach($workerWithCriticalMissing->id, [
            'fecha_asignacion' => now()->subDays(15)->toDateString(),
            'fecha_desasignacion' => null,
        ]);

        $tipoCedula = TipoDocumento::create([
            'nombre' => 'Cédula de Identidad',
            'codigo' => 'CEDULA_IDENTIDAD',
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

        $tipoContrato = TipoDocumento::create([
            'nombre' => 'Contrato de Trabajo',
            'codigo' => 'CONTRATO_TRABAJO',
            'descripcion' => null,
            'periodicidad' => 'unico',
            'es_obligatorio' => true,
            'es_documento_trabajador' => true,
            'dias_vencimiento' => null,
            'formatos_permitidos' => ['pdf'],
            'tamano_maximo_kb' => 3072,
            'requiere_validacion' => true,
            'instrucciones' => null,
            'activo' => true,
        ]);

        $tipoCedula->tiposFaena()->attach($tipoFaena->id);
        $tipoContrato->tiposFaena()->attach($tipoFaena->id);

        DocumentoTrabajador::create([
            'trabajador_id' => $workerOnTrack->id,
            'tipo_documento_id' => $tipoCedula->id,
            'archivo_nombre_original' => 'cedula-ana.pdf',
            'archivo_ruta' => 'docs/ana/cedula.pdf',
            'archivo_tamano_kb' => 210,
            'fecha_vencimiento' => now()->addDays(60)->toDateString(),
            'cargado_por' => $admin->id,
        ]);
        DocumentoTrabajador::create([
            'trabajador_id' => $workerOnTrack->id,
            'tipo_documento_id' => $tipoContrato->id,
            'archivo_nombre_original' => 'contrato-ana.pdf',
            'archivo_ruta' => 'docs/ana/contrato.pdf',
            'archivo_tamano_kb' => 180,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
        ]);
        DocumentoTrabajador::create([
            'trabajador_id' => $workerWithCriticalMissing->id,
            'tipo_documento_id' => $tipoContrato->id,
            'archivo_nombre_original' => 'contrato-beto.pdf',
            'archivo_ruta' => 'docs/beto/contrato.pdf',
            'archivo_tamano_kb' => 190,
            'fecha_vencimiento' => null,
            'cargado_por' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('kpis.required_documents_total', 4)
                ->where('kpis.loaded_documents_total', 3)
                ->where('kpis.workers_total', 2)
                ->where('kpis.compliance_index_percent', 75)
                ->where('kpis.critical_alerts_total', 1)
                ->where('kpis.expired_documents_total', 0)
                ->where('kpis.workers_missing_critical_total', 1)
                ->where('kpis.recent_uploads_24h_total', 3)
                ->has('workers', 2)
                ->where('workers.0.status', 'vencido')
                ->where('workers.1.status', 'al_dia')
                ->has('compliance_by_area', 1)
                ->where('compliance_by_area.0.nombre', 'Packing Norte')
                ->where('compliance_by_area.0.compliance_percent', 75)
            );
    }
}
