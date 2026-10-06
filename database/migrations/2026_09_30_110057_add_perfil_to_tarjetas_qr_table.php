<?php

use App\Enums\PerfilTarjetaQr;
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
        Schema::table('tarjetas_qr', function (Blueprint $table) {
            $table->enum('perfil', array_column(PerfilTarjetaQr::cases(), 'value'))
                ->default(PerfilTarjetaQr::Comensal->value)
                ->after('estado');
            $table->foreignId('admin_user_id')->nullable()->after('perfil')
                ->constrained('users')->nullOnDelete();

            $table->index(['perfil', 'admin_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarjetas_qr', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
            $table->dropIndex(['perfil', 'admin_user_id']);
            $table->dropColumn(['perfil', 'admin_user_id']);
        });
    }
};
