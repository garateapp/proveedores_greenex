<?php

namespace App\Models;

use App\Enums\PerfilTarjetaQr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TarjetaQr extends Model
{
    /** @use HasFactory<\Database\Factories\TarjetaQrFactory> */
    use HasFactory;

    protected $table = 'tarjetas_qr';

    protected $fillable = [
        'numero_serie',
        'codigo_qr',
        'estado',
        'perfil',
        'admin_user_id',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'perfil' => PerfilTarjetaQr::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function esDeAdminCasino(): bool
    {
        return $this->perfil === PerfilTarjetaQr::AdminCasino;
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(TarjetaQrAsignacion::class);
    }

    public function asignacionActiva(): HasOne
    {
        return $this->hasOne(TarjetaQrAsignacion::class)
            ->whereNull('desasignada_en')
            ->latestOfMany('asignada_en');
    }

    public function marcaciones(): HasMany
    {
        return $this->hasMany(MarcacionPacking::class);
    }

    public function valesAlmuerzo(): HasMany
    {
        return $this->hasMany(ValeAlmuerzo::class);
    }
}
