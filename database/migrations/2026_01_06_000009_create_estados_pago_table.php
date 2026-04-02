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
        Schema::create('estados_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            $table->string('numero_documento')->comment('Número de factura o estado de pago');
            $table->date('fecha_documento');
            $table->decimal('monto', 12, 2);
            $table->enum('estado', [
                'recibido',
                'en_revision',
                'aprobado_pago',
                'retenido',
                'pagado',
                'rechazado',
            ])->default('recibido');
            $table->text('observaciones')->nullable();
            $table->text('motivo_retencion')->nullable();
            $table->date('fecha_pago_estimada')->nullable();
            $table->date('fecha_pago_real')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contratista_id', 'estado']);
            $table->index('numero_documento');
            $table->index('fecha_documento');
        });

        // Tabla de historial de cambios de estado (auditoría)
        Schema::create('historial_estado_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_pago_id')->constrained('estados_pago')->onDelete('cascade');
            $table->string('estado_anterior');
            $table->string('estado_nuevo');
            $table->text('comentario')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('estado_pago_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estado_pago');
        Schema::dropIfExists('estados_pago');
    }
};
