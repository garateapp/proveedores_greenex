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
            if (! Schema::hasColumn('documentos_trabajadores', 'estado')) {
                $table->string('estado', 20)
                    ->default('pendiente_validacion')
                    ->after('origen');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'motivo_rechazo')) {
                $table->text('motivo_rechazo')
                    ->nullable()
                    ->after('estado');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'validado_por')) {
                $table->foreignId('validado_por')
                    ->nullable()
                    ->after('motivo_rechazo')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'validado_at')) {
                $table->timestamp('validado_at')
                    ->nullable()
                    ->after('validado_por');
            }
        });

        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            $table->index(['estado', 'tipo_documento_id'], 'documentos_trabajadores_estado_tipo_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            if (Schema::hasIndex('documentos_trabajadores', 'documentos_trabajadores_estado_tipo_idx')) {
                $table->dropIndex('documentos_trabajadores_estado_tipo_idx');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'validado_at')) {
                $table->dropColumn('validado_at');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'validado_por')) {
                $table->dropConstrainedForeignId('validado_por');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'motivo_rechazo')) {
                $table->dropColumn('motivo_rechazo');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};
