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
        Schema::create('centros_costo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 32)->unique();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centros_costo');
    }
};
