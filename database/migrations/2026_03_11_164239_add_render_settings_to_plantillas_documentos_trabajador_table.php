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
        Schema::table('plantillas_documentos_trabajador', function (Blueprint $table) {
            if (! Schema::hasColumn('plantillas_documentos_trabajador', 'fuente_nombre')) {
                $table->string('fuente_nombre', 50)
                    ->default('dejavu_sans')
                    ->after('contenido_html');
            }

            if (! Schema::hasColumn('plantillas_documentos_trabajador', 'fuente_tamano')) {
                $table->unsignedTinyInteger('fuente_tamano')
                    ->default(12)
                    ->after('fuente_nombre');
            }

            if (! Schema::hasColumn('plantillas_documentos_trabajador', 'color_texto')) {
                $table->string('color_texto', 7)
                    ->default('#111827')
                    ->after('fuente_tamano');
            }

            if (! Schema::hasColumn('plantillas_documentos_trabajador', 'formato_papel')) {
                $table->enum('formato_papel', ['a4', 'letter'])
                    ->default('letter')
                    ->after('color_texto');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plantillas_documentos_trabajador', function (Blueprint $table) {
            if (Schema::hasColumn('plantillas_documentos_trabajador', 'formato_papel')) {
                $table->dropColumn('formato_papel');
            }

            if (Schema::hasColumn('plantillas_documentos_trabajador', 'color_texto')) {
                $table->dropColumn('color_texto');
            }

            if (Schema::hasColumn('plantillas_documentos_trabajador', 'fuente_tamano')) {
                $table->dropColumn('fuente_tamano');
            }

            if (Schema::hasColumn('plantillas_documentos_trabajador', 'fuente_nombre')) {
                $table->dropColumn('fuente_nombre');
            }
        });
    }
};
