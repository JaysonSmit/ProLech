<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->after('activo')
                  ->comment('Número WhatsApp para recibir OTP (formato: 51901344157)');
            $table->string('callmebot_apikey', 60)->nullable()->after('telefono')
                  ->comment('API key personal de CallMeBot — se obtiene activando el servicio');
            $table->boolean('otp_activo')->default(false)->after('callmebot_apikey')
                  ->comment('true = el usuario activó el 2FA por WhatsApp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'callmebot_apikey', 'otp_activo']);
        });
    }
};
