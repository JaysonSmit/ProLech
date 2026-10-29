<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    protected $fillable = [
        'user_id',
        'entidad_tipo',
        'entidad_id',
        'accion',
        'campo_modificado',
        'valor_anterior',
        'valor_nuevo',
        'motivo',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeRecientes($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopePorEntidad($query, string $tipo, int $id)
    {
        return $query->where('entidad_tipo', $tipo)->where('entidad_id', $id);
    }
}
