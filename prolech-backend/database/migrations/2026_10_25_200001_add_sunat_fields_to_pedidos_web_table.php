<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado de la boleta electrónica ante SUNAT (vía Nubefact).
 * sunat_estado: simulada (sin token) | aceptada | rechazada | error
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_web', function (Blueprint $table) {
            $table->string('sunat_estado', 20)->nullable()->after('numero_comprobante');
            $table->string('sunat_pdf_url', 500)->nullable()->after('sunat_estado');
            $table->text('sunat_mensaje')->nullable()->after('sunat_pdf_url');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_web', function (Blueprint $table) {
            $table->dropColumn(['sunat_estado', 'sunat_pdf_url', 'sunat_mensaje']);
        });
    }
};
