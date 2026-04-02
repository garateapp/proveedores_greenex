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
        Schema::create('documentos_trabajadores', function (Blueprint $table) {
            $table->id();
            $table->string('trabajador_id', 8);
            $table->foreignId('tipo_documento_id')->constrained('tipo_documentos')->onDelete('cascade');
            $table->string('archivo_nombre_original');
            $table->string('archivo_ruta');
            $table->integer('archivo_tamano_kb');
            $table->foreignId('cargado_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('trabajador_id')->references('id')->on('trabajadores')->onDelete('cascade');
            $table->unique(['trabajador_id', 'tipo_documento_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_trabajadores');
    }
};
