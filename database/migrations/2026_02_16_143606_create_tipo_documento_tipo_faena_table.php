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
        Schema::create('tipo_documento_tipo_faena', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_documento_id')->constrained('tipo_documentos')->cascadeOnDelete();
            $table->foreignId('tipo_faena_id')->constrained('tipo_faenas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tipo_documento_id', 'tipo_faena_id'], 'tipo_doc_tipo_faena_unique');
        });

        $tipoDocumentoIds = DB::table('tipo_documentos')->pluck('id');
        $tipoFaenaIds = DB::table('tipo_faenas')->pluck('id');

        if ($tipoDocumentoIds->isNotEmpty() && $tipoFaenaIds->isNotEmpty()) {
            $rows = [];

            foreach ($tipoDocumentoIds as $tipoDocumentoId) {
                foreach ($tipoFaenaIds as $tipoFaenaId) {
                    $rows[] = [
                        'tipo_documento_id' => $tipoDocumentoId,
                        'tipo_faena_id' => $tipoFaenaId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('tipo_documento_tipo_faena')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_documento_tipo_faena');
    }
};
