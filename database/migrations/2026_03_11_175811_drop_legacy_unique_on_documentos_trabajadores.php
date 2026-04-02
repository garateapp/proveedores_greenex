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
        if (! Schema::hasTable('documentos_trabajadores')) {
            return;
        }

        try {
            DB::statement(
                'ALTER TABLE documentos_trabajadores DROP INDEX documentos_trabajadores_trabajador_id_tipo_documento_id_unique',
            );
        } catch (\Throwable $exception) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('documentos_trabajadores')) {
            return;
        }

        try {
            Schema::table('documentos_trabajadores', function (Blueprint $table) {
                $table->unique(
                    ['trabajador_id', 'tipo_documento_id'],
                    'documentos_trabajadores_trabajador_id_tipo_documento_id_unique',
                );
            });
        } catch (\Throwable $exception) {
        }
    }
};
