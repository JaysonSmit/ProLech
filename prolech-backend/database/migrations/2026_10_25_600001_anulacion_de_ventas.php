<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Anular una venta: se conserva el registro (auditoría) y se devuelve el stock. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->timestamp('anulada_at')->nullable()->after('registrado_por');
            $table->foreignId('anulada_por')->nullable()->after('anulada_at')->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion', 250)->nullable()->after('anulada_por');
            $table->index('anulada_at');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['anulada_at']);
            $table->dropConstrainedForeignId('anulada_por');
            $table->dropColumn(['anulada_at', 'motivo_anulacion']);
        });
    }
};
