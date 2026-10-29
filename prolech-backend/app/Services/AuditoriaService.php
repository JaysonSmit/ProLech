<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;

class AuditoriaService
{
    /**
     * Registra un evento de auditoría para trazabilidad de cambios o correcciones.
     */
    public static function registrar(
        User|int $user,
        string   $entidadTipo,
        int      $entidadId,
        string   $accion,
        string   $motivo,
        ?string  $campoModificado = null,
        ?string  $valorAnterior = null,
        ?string  $valorNuevo = null
    ): Auditoria {
        $userId = $user instanceof User ? $user->id : $user;

        return Auditoria::create([
            'user_id'          => $userId,
            'entidad_tipo'     => $entidadTipo,
            'entidad_id'       => $entidadId,
            'accion'           => $accion,
            'campo_modificado' => $campoModificado,
            'valor_anterior'   => $valorAnterior,
            'valor_nuevo'      => $valorNuevo,
            'motivo'           => $motivo,
        ]);
    }
}
