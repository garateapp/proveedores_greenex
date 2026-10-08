<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarjetas_qr', function (Blueprint $table): void {
            $table->boolean('multiticket')->default(false)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('tarjetas_qr', function (Blueprint $table): void {
            $table->dropColumn('multiticket');
        });
    }
};
