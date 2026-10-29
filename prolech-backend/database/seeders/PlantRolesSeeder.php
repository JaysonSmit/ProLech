<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea usuarios de planta sin borrar datos existentes.
 * Uso: php artisan db:seed --class=PlantRolesSeeder
 */
class PlantRolesSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Lucía Vargas',   'email' => 'calidad@prolech.com',     'rol' => 'calidad'],
            ['name' => 'Ana Quispe',      'email' => 'asistente@prolech.com', 'rol' => 'asistente'],
            ['name' => 'Roberto Flores', 'email' => 'produccion@prolech.com', 'rol' => 'jefe_produccion'],
            ['name' => 'Sandra Ríos',    'email' => 'despacho@prolech.com',   'rol' => 'despacho'],
        ];

        foreach ($usuarios as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('prolech2024'),
                    'rol'      => $data['rol'],
                    'activo'   => true,
                ]
            );
        }

        $this->command->info('✅ Usuarios de planta listos (contraseña: prolech2024)');
    }
}
