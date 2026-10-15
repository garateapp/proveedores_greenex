<?php

namespace App\Models;

use App\Enums\TipoMovimientoTarjetaQr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarjetaQrMovimiento extends Model
{
    /** @use HasFactory<\Database\Factories\TarjetaQrMovimientoFactory> */
    use HasFactory;

    protected $table = 'tarjeta_qr_movimientos';

    protected $fillable = [
        'tarjeta_qr_id',
        'codigo_qr',
        'tipo',
        'filtro',
        'filtro_nombre',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimientoTarjetaQr::class,
            'fecha' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function tarjetaQr(): BelongsTo
    {
        return $this->belongsTo(TarjetaQr::class);
    }
}
