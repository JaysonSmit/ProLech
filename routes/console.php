<?php

use App\Models\LoginOtp;
use App\Models\Notificacion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas (hora de Lima)
|--------------------------------------------------------------------------
| Necesitan que el scheduler corra cada minuto:
|   - Docker: lo arranca deploy/arranque.sh (schedule:work).
|   - VPS sin Docker: cron de instalar-vps.sh (schedule:run).
|   - Windows/Laragon: programar-tareas.ps1 o "php artisan schedule:work".
| Ver la lista con: php artisan schedule:list
*/

// Respaldo diario de la base de datos (storage/app/respaldos, se guardan los últimos 14)
Schedule::command('prolech:respaldo')
    ->dailyAt('02:00')
    ->withoutOverlapping(120)
    ->appendOutputTo(storage_path('logs/respaldos.log'));

// Limpieza: códigos 2FA vencidos y notificaciones leídas de hace más de 90 días
Schedule::command('model:prune', ['--model' => [LoginOtp::class, Notificacion::class]])
    ->dailyAt('03:00');

// Tokens de la API que ya vencieron y trabajos fallidos de la cola con más de 30 días
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('03:10');
Schedule::command('queue:prune-failed --hours=720')->dailyAt('03:20');

// Aviso de insumos en stock mínimo antes del turno de producción
Schedule::command('prolech:alertas-stock')->dailyAt('06:30');

// Productos de lotes vencidos: se retiran de la tienda como merma antes de abrir
Schedule::command('prolech:retirar-vencidos')->dailyAt('06:35');
