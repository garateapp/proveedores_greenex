<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UbicacionImportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_ubicaciones_csv_template(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ubicaciones.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString(
            'codigo;nombre;descripcion;tipo;padre_codigo;orden',
            $response->streamedContent(),
        );
    }

    public function test_admin_can_import_new_ubicaciones_from_csv(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'ubicaciones.csv',
            implode("\n", [
                'codigo;nombre;descripcion;tipo;padre_codigo;orden',
                'UNI1;UNITEC 1;Área principal;principal;;1',
                'UNI1-FILTRO;Filtro;Sub área;secundaria;UNI1;2',
            ]),
        );

        $response = $this->actingAs($admin)
            ->from(route('admin.ubicaciones.index'))
            ->post(route('admin.ubicaciones.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ubicaciones', [
            'codigo' => 'UNI1',
            'nombre' => 'UNITEC 1',
            'tipo' => 'principal',
            'padre_id' => null,
            'orden' => 1,
            'activa' => true,
        ]);

        $padre = Ubicacion::query()->where('codigo', 'UNI1')->firstOrFail();

        $this->assertDatabaseHas('ubicaciones', [
            'codigo' => 'UNI1-FILTRO',
            'nombre' => 'Filtro',
            'tipo' => 'secundaria',
            'padre_id' => $padre->id,
            'orden' => 2,
            'activa' => true,
        ]);
    }

    public function test_admin_can_import_new_ubicaciones_from_csv_with_utf8_bom(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'ubicaciones.csv',
            "\xEF\xBB\xBF".implode("\n", [
                'codigo;nombre;descripcion;tipo;padre_codigo;orden',
                'UNI1;UNITEC 1;Área principal;principal;;1',
                'UNI1-FILTRO;Filtro;Sub área;secundaria;UNI1;2',
            ]),
        );

        $response = $this->actingAs($admin)
            ->from(route('admin.ubicaciones.index'))
            ->post(route('admin.ubicaciones.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ubicaciones', [
            'codigo' => 'UNI1',
            'nombre' => 'UNITEC 1',
            'tipo' => 'principal',
        ]);
    }

    public function test_import_fails_when_csv_contains_existing_codigo(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        Ubicacion::factory()->create([
            'codigo' => 'UNI1',
            'nombre' => 'Existente',
            'tipo' => 'principal',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'ubicaciones.csv',
            implode("\n", [
                'codigo;nombre;descripcion;tipo;padre_codigo;orden',
                'UNI1;UNITEC 1;Área principal;principal;;1',
                'UNI2;UNITEC 2;Área principal;principal;;2',
            ]),
        );

        $response = $this->actingAs($admin)
            ->from(route('admin.ubicaciones.index'))
            ->post(route('admin.ubicaciones.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $response->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('ubicaciones', [
            'codigo' => 'UNI2',
        ]);
    }

    public function test_import_fails_when_secondary_parent_codigo_does_not_exist(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'ubicaciones.csv',
            implode("\n", [
                'codigo;nombre;descripcion;tipo;padre_codigo;orden',
                'UNI1-FILTRO;Filtro;Sub área;secundaria;NO-EXISTE;2',
            ]),
        );

        $response = $this->actingAs($admin)
            ->from(route('admin.ubicaciones.index'))
            ->post(route('admin.ubicaciones.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $response->assertSessionHasErrors('file');

        $this->assertDatabaseCount('ubicaciones', 0);
    }
}
