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
        if (! Schema::hasColumn('faenas', 'tipo_faena_id')) {
            Schema::table('faenas', function (Blueprint $table) {
                $table->foreignId('tipo_faena_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tipo_faenas')
                    ->nullOnDelete();
            });
        }

        $defaultTipoFaenaId = DB::table('tipo_faenas')
            ->where('codigo', 'GENERAL')
            ->value('id');

        if (! $defaultTipoFaenaId) {
            $defaultTipoFaenaId = DB::table('tipo_faenas')->insertGetId([
                'nombre' => 'General',
                'codigo' => 'GENERAL',
                'descripcion' => 'Tipo de faena por defecto para continuidad operacional.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('faenas')
            ->whereNull('tipo_faena_id')
            ->update(['tipo_faena_id' => $defaultTipoFaenaId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('faenas', 'tipo_faena_id')) {
            Schema::table('faenas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tipo_faena_id');
            });
        }
    }
};
