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
        // La tabla ya existe, solo verificamos que tenga la estructura correcta
        if (! Schema::hasColumn('marcaciones_packing', 'ubicacion_id')) {
            Schema::table('marcaciones_packing', function (Blueprint $table) {
                $table->foreignId('ubicacion_id')->nullable()->after('longitud')->constrained('ubicaciones')->nullOnDelete();
                $table->index(['ubicacion_id', 'marcado_en']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('marcaciones_packing', 'ubicacion_id')) {
            Schema::table('marcaciones_packing', function (Blueprint $table) {
                $table->dropForeign(['ubicacion_id']);
                $table->dropColumn('ubicacion_id');
            });
        }
    }
};
