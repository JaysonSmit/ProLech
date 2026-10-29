<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Control de calidad sorpresa: en el predio, frasco traído por el chofer o tanque del camión. */
class ControlCalidad extends Model
{
    protected $table = 'controles_calidad';

    protected $fillable = [
        'fecha', 'tipo', 'estado', 'motivo', 'ganadero_id', 'zona_id', 'acopio_id', 'frasco_codigo',
        'inspector_id', 'agua', 'densidad', 'grasa', 'ph', 'temperatura', 'resultado', 'sancion',
        'notas', 'foto_path', 'lat', 'lon', 'uuid', 'analizado_at',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'agua'         => 'float',
        'densidad'     => 'float',
        'grasa'        => 'float',
        'ph'           => 'float',
        'temperatura'  => 'float',
        'lat'          => 'float',
        'lon'          => 'float',
        'analizado_at' => 'datetime',
    ];

    public const TIPOS = ['predio' => '🏡 En el predio', 'muestra' => '🧪 Frasco del chofer', 'tanque' => '🚛 Tanque del camión'];
    public const RESULTADOS = ['normal' => '✓ Normal', 'leve' => '⚠ Leve', 'grave' => '✗ Grave'];

    public function ganadero() { return $this->belongsTo(Ganadero::class); }
    public function zona()     { return $this->belongsTo(Zona::class); }
    public function acopio()   { return $this->belongsTo(Acopio::class); }
    public function inspector(){ return $this->belongsTo(User::class, 'inspector_id'); }

    public function scopePendientes($q) { return $q->whereIn('estado', ['programado', 'muestra_tomada']); }
}
