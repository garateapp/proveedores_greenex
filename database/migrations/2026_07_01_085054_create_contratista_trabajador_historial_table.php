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
        Schema::create('contratista_trabajador_historial', function (Blueprint $table) {
            $table->id();
            $table->string('trabajador_id', 8);
            $table->foreignId('contratista_origen_id')->constrained('contratistas');
            $table->foreignId('contratista_destino_id')->constrained('contratistas');
            $table->foreignId('usuario_id')->constrained('users');
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->foreign('trabajador_id')
                ->references('id')
                ->on('trabajadores')
                ->cascadeOnDelete();

            $table->index('trabajador_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_trabajador_historial');
    }
};
