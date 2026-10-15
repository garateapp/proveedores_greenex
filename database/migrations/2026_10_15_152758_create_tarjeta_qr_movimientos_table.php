<?php

use App\Enums\TipoMovimientoTarjetaQr;
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
        Schema::create('tarjeta_qr_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarjeta_qr_id')->constrained('tarjetas_qr')->cascadeOnDelete();
            $table->string('codigo_qr');
            $table->enum('tipo', array_column(TipoMovimientoTarjetaQr::cases(), 'value'));
            $table->string('filtro');
            $table->string('filtro_nombre');
            $table->dateTime('fecha');
            $table->timestamps();

            $table->index(['tarjeta_qr_id', 'fecha']);
            $table->index(['tipo', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarjeta_qr_movimientos');
    }
};
