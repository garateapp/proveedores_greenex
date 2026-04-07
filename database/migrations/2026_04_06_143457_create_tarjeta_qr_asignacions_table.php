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
        Schema::create('tarjeta_qr_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarjeta_qr_id')->constrained('tarjetas_qr')->cascadeOnDelete();
            $table->string('trabajador_id');
            $table->foreign('trabajador_id')->references('id')->on('trabajadores')->cascadeOnDelete();
            $table->foreignId('asignada_por')->constrained('users');
            $table->dateTime('asignada_en');
            $table->foreignId('desasignada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('desasignada_en')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['tarjeta_qr_id', 'desasignada_en']);
            $table->index(['trabajador_id', 'desasignada_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarjeta_qr_asignaciones');
    }
};
