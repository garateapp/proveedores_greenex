<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajadores', function (Blueprint $table): void {
            $table->boolean('autorizado_multiples_tickets_dia')->default(false)->after('dieta_hipocalorica');
        });
    }

    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table): void {
            $table->dropColumn('autorizado_multiples_tickets_dia');
        });
    }
};
