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
        Schema::create('faenas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->string('ubicacion')->nullable();
            $table->enum('estado', ['activa', 'inactiva', 'finalizada'])->default('activa');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('estado');
        });

        // Tabla pivot para asignar trabajadores a faenas
        Schema::create('faena_trabajador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faena_id')->constrained('faenas')->onDelete('cascade');
            $table->string('trabajador_id', 8);
            $table->foreign('trabajador_id')->references('id')->on('trabajadores')->onDelete('cascade');
            $table->date('fecha_asignacion')->default(now());
            $table->date('fecha_desasignacion')->nullable();
            $table->timestamps();

            $table->unique(['faena_id', 'trabajador_id']);
            $table->index(['faena_id', 'trabajador_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faena_trabajador');
        Schema::dropIfExists('faenas');
    }
};
