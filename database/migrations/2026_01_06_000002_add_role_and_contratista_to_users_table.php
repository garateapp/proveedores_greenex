<?php

use App\Enums\UserRole;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::Contratista->value)->after('email');
            $table->foreignId('contratista_id')->nullable()->after('role')->constrained('contratistas')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->after('contratista_id');

            $table->index('role');
            $table->index('contratista_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['contratista_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['contratista_id']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'contratista_id', 'is_active']);
        });
    }
};
