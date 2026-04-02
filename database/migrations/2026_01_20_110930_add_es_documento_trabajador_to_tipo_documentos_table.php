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
            $table->boolean('es_documento_trabajador')->default(false)->after('es_obligatorio');
            $table->index('es_documento_trabajador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_documentos', function (Blueprint $table) {
            $table->dropIndex(['es_documento_trabajador']);
            $table->dropColumn('es_documento_trabajador');
        });
    }
};
