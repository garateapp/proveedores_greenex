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
        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            if (! Schema::hasColumn('documentos_trabajadores', 'version')) {
                $table->unsignedTinyInteger('version')->default(1)->after('tipo_documento_id');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'es_ultima_version')) {
                $table->boolean('es_ultima_version')->default(true)->after('version');
            }
        });

        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            $table->index(
                ['trabajador_id', 'tipo_documento_id', 'origen'],
                'idx_documentos_trabajadores_lookup'
            );
            $table->index(
                ['trabajador_id', 'tipo_documento_id', 'es_ultima_version'],
                'idx_documentos_trabajadores_latest_version'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            $table->dropIndex('idx_documentos_trabajadores_lookup');
            $table->dropIndex('idx_documentos_trabajadores_latest_version');
        });
    }
};
