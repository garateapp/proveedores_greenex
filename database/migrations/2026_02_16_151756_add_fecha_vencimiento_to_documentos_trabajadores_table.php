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
        if (! Schema::hasColumn('documentos_trabajadores', 'fecha_vencimiento')) {
            Schema::table('documentos_trabajadores', function (Blueprint $table) {
                $table->date('fecha_vencimiento')->nullable()->after('archivo_tamano_kb');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('documentos_trabajadores', 'fecha_vencimiento')) {
            Schema::table('documentos_trabajadores', function (Blueprint $table) {
                $table->dropColumn('fecha_vencimiento');
            });
        }
    }
};
