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
        Schema::create('faena_contratista', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faena_id')->constrained('faenas')->onDelete('cascade');
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['faena_id', 'contratista_id']);
            $table->index(['contratista_id', 'faena_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faena_contratista');
    }
};
