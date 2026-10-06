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
        Schema::create('vales_almuerzo', function (Blueprint $table) {
            $table->id();
            $table->char('token', 32)->unique();
            $table->string('trabajador_id');
            $table->foreign('trabajador_id')->references('id')->on('trabajadores')->cascadeOnDelete();
            $table->foreignId('tarjeta_qr_id')->nullable()->constrained('tarjetas_qr')->nullOnDelete();
            $table->foreignId('asignacion_id')->nullable()->constrained('tarjeta_qr_asignaciones')->nullOnDelete();
            $table->string('contratista')->nullable();
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costo')->nullOnDelete();
            $table->dateTime('emitido_en');
            $table->timestamps();

            $table->index(['trabajador_id', 'emitido_en']);
            $table->index(['tarjeta_qr_id', 'emitido_en']);
            $table->index(['centro_costo_id', 'emitido_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vales_almuerzo');
    }
};
