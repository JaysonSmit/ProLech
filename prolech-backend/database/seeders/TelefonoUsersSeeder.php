<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Registra el número de WhatsApp de prueba en todos los usuarios.
 * El número 901344157 se registra con código de país Perú: 51901344157.
 *
 * NOTA: otp_activo queda en false hasta que cada usuario complete el setup
 * (activar CallMeBot y confirmar con su API key personal).
 *
 * Ejecutar: php artisan db:seed --class=TelefonoUsersSeeder
 */
class TelefonoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $numero = '51901344157'; // +51 (Perú) + 901344157

        $actualizados = User::query()->update([
            'telefono'   => $numero,
            'otp_activo' => false,   // Se activa cuando completen el setup de CallMeBot
        ]);

        $this->command->info("  ✅ {$actualizados} usuarios actualizados con teléfono {$numero}");
        $this->command->line('');
        $this->command->line('  ⚠  otp_activo = false — cada usuario debe:');
        $this->command->line('     1. Guardar +34 644 59 21 97 en WhatsApp');
        $this->command->line('     2. Enviar: "I allow callmebot to send me messages"');
        $this->command->line('     3. Ingresar su API key en el sistema');
        $this->command->line('');

        // Mostrar tabla de usuarios
        $usuarios = User::select('id', 'name', 'rol', 'telefono', 'otp_activo')->get();
        foreach ($usuarios as $u) {
            $this->command->line(sprintf(
                '     #%-3d %-30s %-18s 📱 %s',
                $u->id, $u->name, $u->rol, $u->telefono ?? '—'
            ));
        }
    }
}
