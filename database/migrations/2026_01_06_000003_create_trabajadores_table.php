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
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->string('id', 8)->primary()->comment('RUT sin puntos ni DV (ej: 12345678)');
            $table->string('documento', 20)->unique()->comment('RUT sin puntos con DV (ej: 12345678-5)');
            $table->string('nombre');
            $table->string('apellido');
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->string('email')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('contratista_id');
            $table->index('estado');
            $table->index(['contratista_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
