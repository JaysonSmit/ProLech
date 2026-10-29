<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LoginOtp extends Model
{
    use MassPrunable;

    protected $table = 'login_otps';

    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'used_at',
        'ip',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** ¿El OTP sigue siendo válido? (no expiró y no fue usado) */
    public function esValido(): bool
    {
        return is_null($this->used_at)
            && $this->expires_at->isFuture();
    }

    /** Limpieza diaria (model:prune): códigos vencidos hace más de un día. */
    public function prunable()
    {
        return static::where('expires_at', '<', now()->subDay());
    }

    /** Marcar como usado ahora */
    public function marcarUsado(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * Crear un nuevo OTP para un usuario.
     * Invalida todos los OTPs anteriores no usados del mismo usuario.
     */
    public static function generarPara(User $user, ?string $ip = null): self
    {
        // Invalidar OTPs anteriores (marcarlos como usados)
        static::where('user_id', $user->id)
              ->whereNull('used_at')
              ->update(['used_at' => now()]);

        return static::create([
            'user_id'    => $user->id,
            'code'       => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(10),
            'used_at'    => null,
            'ip'         => $ip,
        ]);
    }
}
