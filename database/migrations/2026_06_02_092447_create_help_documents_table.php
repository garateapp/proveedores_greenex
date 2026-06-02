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
        Schema::create('help_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('archivo_nombre_original');
            $table->string('archivo_ruta');
            $table->unsignedInteger('archivo_tamano_kb');
            $table->string('tipo_extension');
            $table->unsignedBigInteger('subido_por');
            $table->timestamps();

            $table->foreign('subido_por')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_documents');
    }
};
