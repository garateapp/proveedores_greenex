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
        Schema::create('control_access_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha')->nullable();
            $table->string('personal_id');
            $table->string('nombre')->nullable();
            $table->string('departamento')->nullable();
            $table->dateTime('primera_entrada')->nullable();
            $table->dateTime('ultima_salida')->nullable();
            $table->string('pin')->nullable();
            $table->timestamps();

            $table->index(['personal_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_access_logs');
    }
};
