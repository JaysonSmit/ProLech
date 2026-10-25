<?php

namespace App\Support;

/**
 * Permisos de navegación web por rol (panel admin).
 */
final class RolWeb
{
    /** @return list<string> */
    public static function secciones(string $rol): array
    {
        if ($rol === 'admin') {
            return ['*'];
        }

        return match ($rol) {
            'calidad' => ['dashboard', 'calidad_plan', 'calidad_planta', 'calidad_capacitaciones'],
            'asistente' => ['dashboard', 'ganaderos', 'liquidaciones', 'reuniones', 'reclamos', 'comunicados'],
            'jefe_produccion' => ['dashboard', 'produccion', 'ventas', 'reportes'],
            'despacho' => ['dashboard', 'ventas'],
            default => [],
        };
    }

    public static function permite(string $rol, string $seccion): bool
    {
        $secciones = self::secciones($rol);
        if (in_array('*', $secciones, true)) {
            return true;
        }

        return in_array($seccion, $secciones, true);
    }

    public static function rutaInicio(string $rol): string
    {
        return match ($rol) {
            'proveedor' => 'proveedor.dashboard',
            'calidad' => 'admin.calidad.plan.index',
            'jefe_produccion' => 'admin.produccion',
            'despacho' => 'admin.ventas',
            default => 'admin.dashboard',
        };
    }

    /** @return array<string, string> */
    public static function etiquetas(): array
    {
        return [
            'admin'           => 'Administrador',
            'chofer'          => 'Chofer / Acopiador',
            'calidad'         => 'Control de calidad',
            'asistente'       => 'Asistente administrativa',
            'jefe_produccion' => 'Jefe de producción',
            'despacho'        => 'Personal de despacho',
            'proveedor'       => 'Proveedor (portal)',
        ];
    }
}
