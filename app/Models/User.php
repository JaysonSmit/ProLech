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
        'roles_extra',
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
            'roles_extra'          => 'array',
        ];
    }

    // ── Varios roles: el principal (columna rol) + accesos adicionales (roles_extra) ──

    /** @return list<string> todos los roles del usuario, el principal primero */
    public function roles(): array
    {
        return array_values(array_unique(array_filter([$this->rol, ...($this->roles_extra ?? [])])));
    }

    public function tieneRol(string ...$roles): bool
    {
        return (bool) array_intersect($this->roles(), $roles);
    }

    /** Usuarios con ese rol, sea el principal o un acceso adicional. */
    public function scopeConRol($query, string ...$roles)
    {
        return $query->where(function ($q) use ($roles) {
            $q->whereIn('rol', $roles);
            foreach ($roles as $rol) {
                $q->orWhereJsonContains('roles_extra', $rol);
            }
        });
    }

    // ── Helpers 2FA ───────────────────────────────────────────────────────────

    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->otp_activo && ! empty($this->telefono);
    }

    public function requiereTwoFactor(): bool
    {
        return $this->tieneRol('admin', 'chofer', 'jefe_produccion');
    }

    // ── Scopes de rol ──────────────────────────────────────────────────────────
    public function scopeAdmin($query)     { return $query->conRol('admin'); }
    public function scopeChoferes($query)  { return $query->conRol('chofer'); }
    public function scopeGanaderos($query) { return $query->where('rol', 'ganadero'); }

    // ── Helpers ────────────────────────────────────────────────────────────────
    public function isAdmin(): bool    { return $this->tieneRol('admin'); }
    public function isChofer(): bool   { return $this->tieneRol('chofer'); }
    public function isGanadero(): bool { return $this->rol === 'ganadero'; }

    public function seccionWebPermitida(string $seccion): bool
    {
        foreach ($this->roles() as $rol) {
            if (\App\Support\RolWeb::permite($rol, $seccion)) {
                return true;
            }
        }
        return false;
    }

    /** Pantalla de inicio: la del primer rol (principal primero) que tenga panel web. */
    public function rutaInicioWeb(): string
    {
        foreach ($this->roles() as $rol) {
            if (\App\Support\RolWeb::secciones($rol) || $rol === 'proveedor') {
                return \App\Support\RolWeb::rutaInicio($rol);
            }
        }
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
