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
        try {
            Schema::table('documentos_trabajadores', function (Blueprint $table) {
                $table->dropUnique('documentos_trabajadores_trabajador_id_tipo_documento_id_unique');
            });
        } catch (\Throwable $exception) {
        }

        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            if (! Schema::hasColumn('documentos_trabajadores', 'origen')) {
                $table->enum('origen', ['carga_manual', 'firma_digital'])
                    ->default('carga_manual')
                    ->after('tipo_documento_id');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'plantilla_documento_trabajador_id')) {
                $table->unsignedBigInteger('plantilla_documento_trabajador_id')
                    ->nullable()
                    ->after('tipo_documento_id');
                $table->foreign('plantilla_documento_trabajador_id', 'doc_trab_plt_fk')
                    ->references('id')
                    ->on('plantillas_documentos_trabajador')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'contenido_html_snapshot')) {
                $table->longText('contenido_html_snapshot')->nullable()->after('archivo_ruta');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'variables_snapshot')) {
                $table->json('variables_snapshot')->nullable()->after('contenido_html_snapshot');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'firma_imagen_ruta')) {
                $table->string('firma_imagen_ruta')->nullable()->after('variables_snapshot');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'firmado_por')) {
                $table->foreignId('firmado_por')->nullable()->after('cargado_por')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'firmado_at')) {
                $table->timestamp('firmado_at')->nullable()->after('firmado_por');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'firma_ip')) {
                $table->string('firma_ip', 45)->nullable()->after('firmado_at');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'firma_user_agent')) {
                $table->text('firma_user_agent')->nullable()->after('firma_ip');
            }

            if (! Schema::hasColumn('documentos_trabajadores', 'contenido_hash')) {
                $table->string('contenido_hash', 64)->nullable()->after('firma_user_agent');
            }
        });

        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            $table->index(['trabajador_id', 'tipo_documento_id'], 'documentos_trabajadores_worker_tipo_idx');
            $table->index(['origen', 'firmado_at'], 'documentos_trabajadores_origen_firmado_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('documentos_trabajadores', function (Blueprint $table) {
                $table->dropIndex('documentos_trabajadores_worker_tipo_idx');
            });
        } catch (\Throwable $exception) {
        }

        try {
            Schema::table('documentos_trabajadores', function (Blueprint $table) {
                $table->dropIndex('documentos_trabajadores_origen_firmado_idx');
            });
        } catch (\Throwable $exception) {
        }

        Schema::table('documentos_trabajadores', function (Blueprint $table) {
            if (Schema::hasColumn('documentos_trabajadores', 'contenido_hash')) {
                $table->dropColumn('contenido_hash');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'firma_user_agent')) {
                $table->dropColumn('firma_user_agent');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'firma_ip')) {
                $table->dropColumn('firma_ip');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'firmado_at')) {
                $table->dropColumn('firmado_at');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'firmado_por')) {
                $table->dropConstrainedForeignId('firmado_por');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'firma_imagen_ruta')) {
                $table->dropColumn('firma_imagen_ruta');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'variables_snapshot')) {
                $table->dropColumn('variables_snapshot');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'contenido_html_snapshot')) {
                $table->dropColumn('contenido_html_snapshot');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'plantilla_documento_trabajador_id')) {
                try {
                    $table->dropForeign('doc_trab_plt_fk');
                } catch (\Throwable $exception) {
                }

                try {
                    $table->dropForeign('documentos_trabajadores_plantilla_documento_trabajador_id_foreign');
                } catch (\Throwable $exception) {
                }

                $table->dropColumn('plantilla_documento_trabajador_id');
            }

            if (Schema::hasColumn('documentos_trabajadores', 'origen')) {
                $table->dropColumn('origen');
            }
        });

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
