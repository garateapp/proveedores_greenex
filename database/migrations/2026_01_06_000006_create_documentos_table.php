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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            $table->foreignId('tipo_documento_id')->constrained('tipo_documentos')->onDelete('cascade');
            $table->integer('periodo_ano')->comment('Año del período');
            $table->integer('periodo_mes')->nullable()->comment('Mes del período (1-12)');
            $table->string('archivo_nombre_original');
            $table->string('archivo_ruta');
            $table->integer('archivo_tamano_kb');
            $table->enum('estado', ['pendiente_validacion', 'aprobado', 'rechazado'])->default('pendiente_validacion');
            $table->text('observaciones')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->foreignId('cargado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('validado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contratista_id', 'tipo_documento_id']);
            $table->index(['periodo_ano', 'periodo_mes']);
            $table->index('estado');
            $table->index('fecha_vencimiento');
            $table->unique(['contratista_id', 'tipo_documento_id', 'periodo_ano', 'periodo_mes'], 'unique_documento_periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
