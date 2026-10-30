<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            // Contratista que realizó la carga del documento. Es "informativo":
            // se conserva aunque el trabajador cambie de contratista, para poder
            // auditar quién subió cada archivo.
            $table->foreignId('contratista_id')
                ->nullable()
                ->after('trabajador_id')
                ->constrained('contratistas')
                ->nullOnDelete();

            $table->index(['contratista_id', 'trabajador_id']);
        });

        // Backfill: los documentos existentes pertenecen al contratista actual
        // del trabajador en el momento de la ejecución de la migración.
        DB::statement(<<<'SQL'
            UPDATE documentos_trabajadores dt
            INNER JOIN trabajadores t ON t.id = dt.trabajador_id
            SET dt.contratista_id = t.contratista_id
            WHERE dt.contratista_id IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            $table->dropIndex(['contratista_id', 'trabajador_id']);
            $table->dropConstrainedForeignId('contratista_id');
        });
    }
};
