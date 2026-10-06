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
        Schema::create('vales_lote', function (Blueprint $table) {
            $table->id();
            $table->char('codigo', 32)->unique();
            $table->uuid('lote_id');
            $table->unsignedSmallInteger('posicion');
            $table->foreignId('administrador_id')->constrained('users');
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costo')->nullOnDelete();
            $table->dateTime('emitido_en');
            $table->dateTime('canjeado_en')->nullable();
            $table->foreignId('canjeado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['lote_id', 'posicion']);
            $table->index('canjeado_en');
            $table->index(['lote_id', 'canjeado_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vales_lote');
    }
};
