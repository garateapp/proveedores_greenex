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
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('padre_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->string('nombre');
            $table->string('codigo')->unique();
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['principal', 'secundaria'])->default('secundaria');
            $table->integer('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['padre_id', 'activa']);
            $table->index(['tipo', 'activa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
