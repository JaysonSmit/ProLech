<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            // Ruta en el disco "public" de la foto de evidencia (leche rechazada/observada)
            $table->string('foto_evidencia')->nullable()->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->dropColumn('foto_evidencia');
        });
    }
};
