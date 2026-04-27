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
        Schema::create('control_access_presences', function (Blueprint $table) {
            $table->id();
            $table->string('personal_id')->unique();
            $table->string('nombre')->nullable();
            $table->string('departamento')->nullable();
            $table->dateTime('last_entry_at')->nullable();
            $table->dateTime('last_exit_at')->nullable();
            $table->string('last_event_id_pair')->nullable();
            $table->string('pin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_access_presences');
    }
};
