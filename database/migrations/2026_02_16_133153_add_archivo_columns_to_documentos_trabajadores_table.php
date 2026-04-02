<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $shouldAddArchivoNombreOriginal = ! Schema::hasColumn('documentos_trabajadores', 'archivo_nombre_original');
        $shouldAddArchivoRuta = ! Schema::hasColumn('documentos_trabajadores', 'archivo_ruta');
        $shouldAddArchivoTamanoKb = ! Schema::hasColumn('documentos_trabajadores', 'archivo_tamano_kb');
        $shouldAddCargadoPor = ! Schema::hasColumn('documentos_trabajadores', 'cargado_por');

        if (
            ! $shouldAddArchivoNombreOriginal &&
            ! $shouldAddArchivoRuta &&
            ! $shouldAddArchivoTamanoKb &&
            ! $shouldAddCargadoPor
        ) {
            return;
        }

        Schema::table('documentos_trabajadores', function (Blueprint $table) use (
            $shouldAddArchivoNombreOriginal,
            $shouldAddArchivoRuta,
            $shouldAddArchivoTamanoKb,
            $shouldAddCargadoPor
        ) {
            if ($shouldAddArchivoNombreOriginal) {
                $table->string('archivo_nombre_original')->nullable();
            }

            if ($shouldAddArchivoRuta) {
                $table->string('archivo_ruta')->nullable();
            }

            if ($shouldAddArchivoTamanoKb) {
                $table->integer('archivo_tamano_kb')->nullable();
            }

            if ($shouldAddCargadoPor) {
                $table->foreignId('cargado_por')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
