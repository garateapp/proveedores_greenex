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
        Schema::table('tipo_documentos', function (Blueprint $table) {
            if (! Schema::hasColumn('tipo_documentos', 'permite_multiples_en_mes')) {
                $table->boolean('permite_multiples_en_mes')
                    ->default(false)
                    ->after('periodicidad');
            }
        });

        try {
            Schema::table('documentos', function (Blueprint $table) {
                $table->dropUnique('unique_documento_periodo');
            });
        } catch (\Throwable $exception) {
        }

        try {
            Schema::table('documentos', function (Blueprint $table) {
                $table->index(
                    ['contratista_id', 'tipo_documento_id', 'periodo_ano', 'periodo_mes'],
                    'documentos_periodo_lookup_idx',
                );
            });
        } catch (\Throwable $exception) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('documentos', function (Blueprint $table) {
                $table->dropIndex('documentos_periodo_lookup_idx');
            });
        } catch (\Throwable $exception) {
        }

        try {
            Schema::table('documentos', function (Blueprint $table) {
                $table->unique(
                    ['contratista_id', 'tipo_documento_id', 'periodo_ano', 'periodo_mes'],
                    'unique_documento_periodo',
                );
            });
        } catch (\Throwable $exception) {
        }

        Schema::table('tipo_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('tipo_documentos', 'permite_multiples_en_mes')) {
                $table->dropColumn('permite_multiples_en_mes');
            }
        });
    }
};
