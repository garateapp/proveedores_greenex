<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarjetaQrAsignacion extends Model
{
    /** @use HasFactory<\Database\Factories\TarjetaQrAsignacionFactory> */
    use HasFactory;

    protected $table = 'tarjeta_qr_asignaciones';

    protected $fillable = [
        'tarjeta_qr_id',
        'trabajador_id',
        'asignada_por',
        'asignada_en',
        'desasignada_por',
        'desasignada_en',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'asignada_en' => 'datetime',
            'desasignada_en' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function tarjetaQr(): BelongsTo
    {
        return $this->belongsTo(TarjetaQr::class);
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function asignadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignada_por');
    }

    public function desasignadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desasignada_por');
    }

    public function marcaciones(): HasMany
    {
        return $this->hasMany(MarcacionPacking::class);
    }
}
