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
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            $table->enum('tipo', ['documento_pendiente', 'documento_vencido', 'documento_por_vencer', 'documento_rechazado', 'general']);
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->onDelete('cascade');
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();

            $table->index(['contratista_id', 'leida']);
            $table->index(['tipo', 'prioridad']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
