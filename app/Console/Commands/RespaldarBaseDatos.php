<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Respaldo comprimido de la base de datos en storage/app/respaldos (config/respaldo.php).
 *   - MySQL/MariaDB: mysqldump en una sola transacción (no bloquea a los choferes que sincronizan).
 *   - SQLite: copia consistente con VACUUM INTO.
 * Conserva los N más recientes. Si falla, avisa a los administradores en el panel.
 */
class RespaldarBaseDatos extends Command
{
    protected $signature = 'prolech:respaldo {--conservar= : Cuántos respaldos guardar (por defecto config respaldo.conservar)}';

    protected $description = 'Respalda la base de datos en un .sql.gz y borra los respaldos antiguos';

    public function handle(): int
    {
        $carpeta = rtrim(config('respaldo.ruta'), '/\\');
        if (! is_dir($carpeta) && ! mkdir($carpeta, 0750, true) && ! is_dir($carpeta)) {
            return $this->fallo("No se pudo crear la carpeta {$carpeta}");
        }

        $conexion = config('database.default');
        $driver   = config("database.connections.{$conexion}.driver");
        $nombre   = 'prolech-' . now()->format('Y-m-d_His') . ($driver === 'sqlite' ? '.sqlite.gz' : '.sql.gz');
        $destino  = $carpeta . DIRECTORY_SEPARATOR . $nombre;
        $parcial  = $destino . '.parcial';

        try {
            match ($driver) {
                'mysql', 'mariadb' => $this->volcarMysql(config("database.connections.{$conexion}"), $parcial),
                'sqlite'           => $this->copiarSqlite($parcial),
                default            => throw new \RuntimeException("Motor {$driver} no soportado para respaldos"),
            };
            rename($parcial, $destino);
        } catch (\Throwable $e) {
            @unlink($parcial);
            return $this->fallo($e->getMessage());
        }

        $tamano = round(filesize($destino) / 1024, 1);
        $this->info("Respaldo creado: {$destino} ({$tamano} KB)");

        $borrados = $this->borrarAntiguos($carpeta, (int) ($this->option('conservar') ?? config('respaldo.conservar')));
        if ($borrados) {
            $this->line("Respaldos antiguos borrados: {$borrados}");
        }

        return self::SUCCESS;
    }

    private function volcarMysql(array $db, string $parcial): void
    {
        // La contraseña va en un archivo temporal, no en la línea de comandos (no aparece en la lista de procesos)
        $credenciales = tempnam(sys_get_temp_dir(), 'prolech-dump');
        file_put_contents($credenciales, implode("\n", [
            '[client]',
            'user="' . addcslashes((string) $db['username'], '"\\') . '"',
            'password="' . addcslashes((string) $db['password'], '"\\') . '"',
            'host="' . ($db['host'] ?? '127.0.0.1') . '"',
            'port=' . ($db['port'] ?? 3306),
        ]) . "\n");

        $gz = gzopen($parcial, 'wb6');
        try {
            $proceso = new Process([
                config('respaldo.mysqldump'),
                "--defaults-extra-file={$credenciales}",   // debe ser la primera opción
                '--single-transaction', '--quick', '--routines', '--triggers',
                '--no-tablespaces', '--default-character-set=utf8mb4',
                $db['database'],
            ]);
            $proceso->setTimeout(3600);

            $errores = '';
            $proceso->run(function (string $tipo, string $datos) use ($gz, &$errores) {
                $tipo === Process::OUT ? gzwrite($gz, $datos) : $errores .= $datos;
            });

            if (! $proceso->isSuccessful()) {
                throw new \RuntimeException('mysqldump falló: ' . trim($errores ?: $proceso->getErrorOutput()));
            }
        } finally {
            gzclose($gz);
            @unlink($credenciales);
        }

        // Un volcado completo siempre termina con esta marca; si falta, quedó cortado
        if (! str_contains($this->ultimosBytes($parcial), 'Dump completed')) {
            throw new \RuntimeException('El volcado quedó incompleto');
        }
    }

    private function copiarSqlite(string $parcial): void
    {
        $copia = $parcial . '.db';
        DB::statement('VACUUM INTO ?', [$copia]);

        try {
            $entrada = fopen($copia, 'rb');
            $gz = gzopen($parcial, 'wb6');
            while (! feof($entrada)) {
                gzwrite($gz, fread($entrada, 1 << 20));
            }
            gzclose($gz);
            fclose($entrada);
        } finally {
            @unlink($copia);
        }
    }

    private function ultimosBytes(string $archivoGz): string
    {
        $gz = gzopen($archivoGz, 'rb');
        $cola = '';
        while (! gzeof($gz)) {
            $cola = substr($cola . gzread($gz, 1 << 20), -512);
        }
        gzclose($gz);

        return $cola;
    }

    private function borrarAntiguos(string $carpeta, int $conservar): int
    {
        $archivos = glob($carpeta . DIRECTORY_SEPARATOR . 'prolech-*.gz') ?: [];
        rsort($archivos);   // el nombre lleva la fecha: el más reciente primero

        $borrados = 0;
        foreach (array_slice($archivos, max(1, $conservar)) as $viejo) {
            $borrados += (int) @unlink($viejo);
        }

        return $borrados;
    }

    private function fallo(string $motivo): int
    {
        $this->error("Respaldo fallido: {$motivo}");

        try {
            User::where('rol', 'admin')->where('activo', true)->pluck('id')->each(
                fn (int $id) => Notificacion::enviar(
                    userId:  $id,
                    titulo:  '⚠️ Falló el respaldo de la base de datos',
                    mensaje: "El respaldo automático del " . now()->format('d/m/Y H:i') . " no se pudo crear: "
                           . mb_substr($motivo, 0, 300),
                    tipo:    'respaldo_fallido',
                )
            );
        } catch (\Throwable) {
            // Si la base no responde, al menos queda el error en el log del scheduler
        }

        return self::FAILURE;
    }
}
