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
        Schema::table('documentos', function (Blueprint $table) {
            // Add version field for tracking multiple uploads
            $table->unsignedTinyInteger('version')->default(1)->after('periodo_mes');

            // Add flag to quickly identify the latest version
            $table->boolean('es_ultima_version')->default(true)->after('version');
        });

        // Add composite index for common queries
        Schema::table('documentos', function (Blueprint $table) {
            $table->index(
                ['contratista_id', 'tipo_documento_id', 'periodo_ano', 'periodo_mes', 'estado'],
                'idx_documentos_lookup'
            );
            $table->index(
                ['contratista_id', 'tipo_documento_id', 'periodo_ano', 'periodo_mes', 'es_ultima_version'],
                'idx_documentos_latest_version'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropIndex('idx_documentos_lookup');
            $table->dropIndex('idx_documentos_latest_version');
            $table->dropColumn(['version', 'es_ultima_version']);
        });
    }
};
