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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->string('trabajador_id', 8);
            $table->foreign('trabajador_id')->references('id')->on('trabajadores')->onDelete('cascade');
            $table->foreignId('faena_id')->nullable()->constrained('faenas')->onDelete('set null');
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            $table->enum('tipo', ['entrada', 'salida']);
            $table->dateTime('fecha_hora')->comment('Timestamp preciso de la marcación');
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->string('ubicacion_texto')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->boolean('sincronizado')->default(false)->comment('Si fue registrado offline');
            $table->timestamp('sincronizado_at')->nullable();
            $table->timestamps();

            // NO soft deletes - inmutabilidad (Línea 245 CLAUDE.md)
            // NO update/delete - solo insert

            $table->index(['trabajador_id', 'fecha_hora']);
            $table->index(['contratista_id', 'fecha_hora']);
            $table->index(['faena_id', 'fecha_hora']);
            $table->index('sincronizado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
