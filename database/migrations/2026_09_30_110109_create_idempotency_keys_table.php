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
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 128);
            $table->string('endpoint', 64);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status');
            $table->mediumText('response_body');
            $table->timestamp('created_at');

            $table->unique(['clave', 'endpoint']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
