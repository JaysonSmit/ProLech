<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Clave secreta TOTP (null = 2FA no configurado)
            $table->string('google2fa_secret')->nullable()->after('password');
            // true = el usuario ya escaneó el QR y confirmó el primer código
            $table->boolean('two_factor_confirmed')->default(false)->after('google2fa_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google2fa_secret', 'two_factor_confirmed']);
        });
    }
};
