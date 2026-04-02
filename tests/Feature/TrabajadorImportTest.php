<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TrabajadorImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_import_requires_contratista_id(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'contratista_id' => null,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'trabajadores.csv',
            "id;nombre;apellido;documento\n12345678;Juan;Perez;12345678-5\n",
        );

        $this->actingAs($admin)
            ->from(route('trabajadores.index'))
            ->post(route('trabajadores.import'), [
                'file' => $file,
            ])
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHasErrors('contratista_id');

        $this->assertDatabaseCount('trabajadores', 0);
    }

    public function test_admin_import_sets_selected_contratista_id_on_created_workers(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'contratista_id' => null,
        ]);
        $contratista = Contratista::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'trabajadores.csv',
            "id;nombre;apellido;documento\n12345678;Juan;Perez;12345678-5\n",
        );

        $this->actingAs($admin)
            ->from(route('trabajadores.index'))
            ->post(route('trabajadores.import'), [
                'file' => $file,
                'contratista_id' => $contratista->id,
            ])
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(Trabajador::class, [
            'id' => '12345678',
            'contratista_id' => $contratista->id,
            'documento' => '12345678-5',
        ]);
    }

    public function test_contratista_import_uses_authenticated_user_contratista_id(): void
    {
        $contratista = Contratista::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Contratista,
            'contratista_id' => $contratista->id,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'trabajadores.csv',
            "id;nombre;apellido;documento\n87654321;Maria;Gonzalez;87654321-4\n",
        );

        $this->actingAs($user)
            ->from(route('trabajadores.index'))
            ->post(route('trabajadores.import'), [
                'file' => $file,
            ])
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(Trabajador::class, [
            'id' => '87654321',
            'contratista_id' => $contratista->id,
            'documento' => '87654321-4',
        ]);
    }
}
