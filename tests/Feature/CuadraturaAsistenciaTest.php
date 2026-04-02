<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CuadraturaAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_cuadratura_asistencia_page(): void
    {
        $this->fakeGreenexnet();

        $this->actingAs($this->createAuthorizedUser())
            ->get(route('herramientas.cuadratura-asistencia.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('herramientas/cuadratura-asistencia')
                ->where('rows', [])
                ->where('summary', null)
                ->where('comparisonSummary', null)
                ->where('entidades.0.id', 9)
                ->where('entidades.0.nombre', 'Bodega Central')
                ->where('entidades.1.id', 4)
                ->where('entidades.1.nombre', 'Las Orquideas')
            );
    }

    public function test_user_can_extract_rows_from_fonasa_pdf(): void
    {
        $this->fakeGreenexnet(function (ClientRequest $request) {
            $data = $request->data();
            $rut = (string) ($data['rut'] ?? '');
            $mes = (int) ($data['mes'] ?? 0);
            $entidad = (int) ($data['entidad'] ?? 0);

            if ($rut === '19.430.256-8' && $mes === 12 && $entidad === 4) {
                return Http::response(['dias_trabajados' => 23], 200);
            }

            if ($rut === '26.280.644-8' && $mes === 12 && $entidad === 4) {
                return Http::response([
                    ['dias_trabajados' => 20],
                ], 200);
            }

            return Http::response([
                ['dias_trabajados' => 0],
            ], 200);
        });

        $this->actingAs($this->createAuthorizedUser())
            ->post(route('herramientas.cuadratura-asistencia.extract'), [
                'archivo' => $this->sampleFonasaPdf(),
                'entidad_id' => 4,
                'mes' => 12,
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('herramientas/cuadratura-asistencia')
                ->where('summary.periodo_mes', 12)
                ->where('summary.periodo_ano', 2025)
                ->where('summary.total_registros', 300)
                ->where('summary.entidad_id', 4)
                ->where('summary.mes_consultado', 12)
                ->where('comparisonSummary.total_registros', 300)
                ->where('comparisonSummary.total_sin_datos', 0)
                ->where('rows.0.rut', '19.430.256-8')
                ->where('rows.0.apellido_paterno', 'ABARCA')
                ->where('rows.0.apellido_materno', 'ABARCA')
                ->where('rows.0.nombres', 'ANTONIETA DEL CARMEN')
                ->where('rows.0.dias_trabajados', 23)
                ->where('rows.0.dias_asistencia', 23)
                ->where('rows.0.diferencia', 0)
                ->where('rows.0.estado', 'coincide')
                ->where('rows.26.numero', 27)
                ->where('rows.26.rut', '26.280.644-8')
                ->where('rows.26.apellido_paterno', 'AURELUS')
                ->where('rows.26.apellido_materno', '')
                ->where('rows.26.nombres', 'VERONIQUE')
                ->where('rows.26.dias_trabajados', 30)
                ->where('rows.26.dias_asistencia', 20)
                ->where('rows.26.diferencia', 10)
                ->where('rows.26.estado', 'difiere')
            );

        Http::assertSent(function (ClientRequest $request): bool {
            return str_contains($request->url(), '/api/attendances/dias-trabajados')
                && ($request->data()['entidad'] ?? null) === 4
                && ($request->data()['mes'] ?? null) === 12;
        });
    }

    public function test_extract_handles_connection_exception_as_missing_data(): void
    {
        $this->fakeGreenexnet(function (ClientRequest $request) {
            $rut = (string) (($request->data())['rut'] ?? '');

            if ($rut === '19.430.256-8') {
                throw new ConnectionException('timeout');
            }

            return Http::response([
                ['dias_trabajados' => 0],
            ], 200);
        });

        $this->actingAs($this->createAuthorizedUser())
            ->post(route('herramientas.cuadratura-asistencia.extract'), [
                'archivo' => $this->sampleFonasaPdf(),
                'entidad_id' => 4,
                'mes' => 12,
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('herramientas/cuadratura-asistencia')
                ->where('rows.0.rut', '19.430.256-8')
                ->where('rows.0.dias_asistencia', null)
                ->where('rows.0.diferencia', null)
                ->where('rows.0.estado', 'sin_datos')
            );
    }

    private function sampleFonasaPdf(): UploadedFile
    {
        return new UploadedFile(
            base_path('COTIZACIONES_FONASA DICIEMBRE LAS ORQUIDEAS.pdf'),
            'COTIZACIONES_FONASA DICIEMBRE LAS ORQUIDEAS.pdf',
            'application/pdf',
            null,
            true
        );
    }

    private function createAuthorizedUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Contratista,
        ]);
    }

    private function fakeGreenexnet(?callable $diasTrabajadosResponder = null): void
    {
        config([
            'services.greenexnet.base_url' => 'https://greenexnet.test',
            'services.greenexnet.entidades_path' => '/api/entidads',
            'services.greenexnet.dias_trabajados_path' => '/api/attendances/dias-trabajados',
            'services.greenexnet.verify_ssl' => true,
        ]);

        Http::fake([
            'https://greenexnet.test/api/entidads*' => Http::response([
                ['id' => 4, 'nombre' => 'Las Orquideas', 'tipo_id' => 2],
                ['id' => 9, 'nombre' => 'Bodega Central', 'tipo_id' => 2],
                ['id' => 1, 'nombre' => 'No valida', 'tipo_id' => 1],
            ], 200),
            'https://greenexnet.test/api/attendances/dias-trabajados*' => $diasTrabajadosResponder
                ?? Http::response([
                    ['dias_trabajados' => 0],
                ], 200),
        ]);
    }
}
