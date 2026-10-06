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
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->foreignId('centro_costo_id')->nullable()->after('contratista_id')
                ->constrained('centros_costo')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropForeign(['centro_costo_id']);
            $table->dropColumn('centro_costo_id');
        });
    }
};
