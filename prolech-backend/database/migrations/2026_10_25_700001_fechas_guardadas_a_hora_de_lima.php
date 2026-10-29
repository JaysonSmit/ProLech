<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La aplicación pasó de UTC a America/Lima (config/app.php). Laravel guarda las fechas-hora
 * en la zona de la aplicación sin indicarla, así que las ya guardadas (en UTC) se mostrarían
 * 5 horas adelantadas. Aquí se pasan a hora de Lima. Perú no tiene horario de verano: el
 * desfase es siempre de 5 horas. Las columnas de solo fecha (fecha_venta, fecha…) no cambian.
 */
return new class extends Migration
{
    /** Columnas que llena MySQL (DEFAULT CURRENT_TIMESTAMP) y ya están en la hora del servidor. */
    private const EXCLUIR = ['failed_jobs.failed_at'];

    public function up(): void
    {
        $this->desplazar('-');
    }

    public function down(): void
    {
        $this->desplazar('+');
    }

    private function desplazar(string $signo): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;   // pruebas en SQLite: base vacía, no hay nada que mover
        }

        $columnas = DB::select(
            "SELECT table_name AS tabla, column_name AS columna FROM information_schema.columns
             WHERE table_schema = DATABASE() AND data_type IN ('datetime', 'timestamp')"
        );

        $porTabla = [];
        foreach ($columnas as $c) {
            if (! in_array("{$c->tabla}.{$c->columna}", self::EXCLUIR, true)) {
                $porTabla[$c->tabla][] = $c->columna;
            }
        }

        foreach ($porTabla as $tabla => $cols) {
            // Todas las columnas de la tabla en un solo UPDATE: así las que tienen
            // ON UPDATE CURRENT_TIMESTAMP (tracking_gps) no se pisan con la hora actual.
            $sets = implode(', ', array_map(fn ($col) => "`{$col}` = `{$col}` {$signo} INTERVAL 5 HOUR", $cols));
            DB::statement("UPDATE `{$tabla}` SET {$sets}");
        }
    }
};
