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
        Schema::create('marcaciones_packing', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('trabajador_id');
            $table->foreign('trabajador_id')->references('id')->on('trabajadores')->cascadeOnDelete();
            $table->foreignId('tarjeta_qr_id')->constrained('tarjetas_qr')->cascadeOnDelete();
            $table->foreignId('tarjeta_qr_asignacion_id')->constrained('tarjeta_qr_asignaciones')->cascadeOnDelete();
            $table->string('numero_serie_snapshot');
            $table->string('codigo_qr_snapshot');
            $table->dateTime('marcado_en');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_id')->nullable();
            $table->string('sync_batch_id')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('ubicacion_texto')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('sincronizado_at')->nullable();
            $table->timestamps();

            $table->index(['trabajador_id', 'marcado_en']);
            $table->index(['tarjeta_qr_id', 'marcado_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marcaciones_packing');
    }
};
