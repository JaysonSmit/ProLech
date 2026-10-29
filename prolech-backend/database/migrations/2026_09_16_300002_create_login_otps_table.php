<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 6)->comment('Código OTP de 6 dígitos');
            $table->timestamp('expires_at')->comment('Expira a los 10 minutos');
            $table->timestamp('used_at')->nullable()->comment('null = no usado aún');
            $table->string('ip', 45)->nullable()->comment('IP desde donde se solicitó');
            $table->timestamps();

            $table->index(['user_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otps');
    }
};
