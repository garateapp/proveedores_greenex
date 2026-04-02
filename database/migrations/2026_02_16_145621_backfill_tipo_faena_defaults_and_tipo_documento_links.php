<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tipo_faenas') || ! Schema::hasTable('faenas')) {
            return;
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

        if (Schema::hasColumn('faenas', 'tipo_faena_id')) {
            DB::table('faenas')
                ->whereNull('tipo_faena_id')
                ->update(['tipo_faena_id' => $defaultTipoFaenaId]);
        }

        if (! Schema::hasTable('tipo_documento_tipo_faena') || ! Schema::hasTable('tipo_documentos')) {
            return;
        }

        $tipoDocumentoIds = DB::table('tipo_documentos')->pluck('id');
        $tipoFaenaIds = DB::table('tipo_faenas')->pluck('id');

        if ($tipoDocumentoIds->isEmpty() || $tipoFaenaIds->isEmpty()) {
            return;
        }

        $rows = [];
        $existing = DB::table('tipo_documento_tipo_faena')
            ->select('tipo_documento_id', 'tipo_faena_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->tipo_documento_id.'-'.$row->tipo_faena_id => true,
            ]);

        foreach ($tipoDocumentoIds as $tipoDocumentoId) {
            foreach ($tipoFaenaIds as $tipoFaenaId) {
                $key = $tipoDocumentoId.'-'.$tipoFaenaId;
                if (isset($existing[$key])) {
                    continue;
                }

                $rows[] = [
                    'tipo_documento_id' => $tipoDocumentoId,
                    'tipo_faena_id' => $tipoFaenaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            DB::table('tipo_documento_tipo_faena')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
