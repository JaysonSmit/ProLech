<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'activo',
        'google2fa_secret',
        'two_factor_confirmed',
        'telefono',
        'callmebot_apikey',
        'otp_activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'callmebot_apikey',   // nunca exponer en JSON/API
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'activo'               => 'boolean',
            'two_factor_confirmed' => 'boolean',
            'otp_activo'           => 'boolean',
        ];
    }

    // ── Helpers 2FA ───────────────────────────────────────────────────────────

    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->otp_activo && ! empty($this->telefono);
    }

    public function requiereTwoFactor(): bool
    {
        return in_array($this->rol, ['admin', 'chofer', 'jefe_produccion'], true);
    }

    // ── Scopes de rol ──────────────────────────────────────────────────────────
    public function scopeAdmin($query)     { return $query->where('rol', 'admin'); }
    public function scopeChoferes($query)  { return $query->where('rol', 'chofer'); }
    public function scopeGanaderos($query) { return $query->where('rol', 'ganadero'); }

    // ── Helpers ────────────────────────────────────────────────────────────────
    public function isAdmin(): bool    { return $this->rol === 'admin'; }
    public function isChofer(): bool   { return $this->rol === 'chofer'; }
    public function isGanadero(): bool { return $this->rol === 'ganadero'; }

    public function seccionWebPermitida(string $seccion): bool
    {
        return \App\Support\RolWeb::permite($this->rol, $seccion);
    }

    public function rutaInicioWeb(): string
    {
        return \App\Support\RolWeb::rutaInicio($this->rol);
    }

    // ── Relaciones ─────────────────────────────────────────────────────────────
    public function ganadero()
    {
        return $this->hasOne(Ganadero::class);
    }

    public function acopiosRecolectados()
    {
        return $this->hasMany(Acopio::class, 'chofer_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'registrado_por');
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class, 'registrado_por');
    }

    /** Última ubicación GPS del chofer. */
    public function trackingGps()
    {
        return $this->hasOne(TrackingGps::class, 'chofer_id');
    }

    /** Solicitudes de cambio de ruta enviadas desde la app. */
    public function solicitudesRuta()
    {
        return $this->hasMany(SolicitudRuta::class, 'chofer_id');
    }

    /** Reuniones a las que este usuario está invitado (pivot). */
    public function reuniones()
    {
        return $this->belongsToMany(Reunion::class, 'reunion_user')
                    ->withPivot(['notificado', 'confirmado'])
                    ->withTimestamps();
    }

    /** Reuniones que este usuario (admin) creó. */
    public function reunionesCreadas()
    {
        return $this->hasMany(Reunion::class, 'creado_por');
    }

    /** Notificaciones del sistema para este usuario. */
    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'user_id');
    }

    /** Notificaciones no leídas. */
    public function notificacionesPendientes()
    {
        return $this->hasMany(Notificacion::class, 'user_id')->where('leida', false);
    }
}
