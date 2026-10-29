<?php

use App\Models\AcopioCampo;
use App\Services\AcopioCampoEnlace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los acopios de la app de campo quedaban solo en acopios_campo y no aparecían en el
 * dashboard ni en liquidaciones. Cada uno tendrá un acopio espejo en la tabla acopios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->foreignId('acopio_campo_id')->nullable()->unique()->after('client_uuid')
                  ->constrained('acopios_campo')->nullOnDelete();
        });

        // Los que ya estaban sincronizados
        $enlace = app(AcopioCampoEnlace::class);
        AcopioCampo::orderBy('id')->each(fn ($campo) => $enlace->sincronizar($campo));
    }

    public function down(): void
    {
        DB::table('acopios')->whereNotNull('acopio_campo_id')->delete();

        Schema::table('acopios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acopio_campo_id');
        });
    }
};
