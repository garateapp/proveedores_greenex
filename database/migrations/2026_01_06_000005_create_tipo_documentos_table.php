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
        Schema::create('tipo_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('periodicidad', ['mensual', 'trimestral', 'semestral', 'anual', 'unico'])->default('mensual');
            $table->boolean('es_obligatorio')->default(true);
            $table->integer('dias_vencimiento')->nullable()->comment('Días desde cierre mes para cargar');
            $table->json('formatos_permitidos')->nullable()->comment('Array de extensiones: pdf, csv, xlsx');
            $table->integer('tamano_maximo_kb')->default(5120);
            $table->boolean('requiere_validacion')->default(false);
            $table->text('instrucciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('es_obligatorio');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_documentos');
    }
};
