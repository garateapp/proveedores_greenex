<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarcacionPacking extends Model
{
    /** @use HasFactory<\Database\Factories\MarcacionPackingFactory> */
    use HasFactory;

    protected $table = 'marcaciones_packing';

    protected $fillable = [
        'uuid',
        'trabajador_id',
        'tarjeta_qr_id',
        'tarjeta_qr_asignacion_id',
        'numero_serie_snapshot',
        'codigo_qr_snapshot',
        'marcado_en',
        'registrado_por',
        'device_id',
        'sync_batch_id',
        'latitud',
        'longitud',
        'ubicacion_id',
        'ubicacion_texto',
        'metadata',
        'sincronizado_at',
    ];

    protected function casts(): array
    {
        return [
            'marcado_en' => 'datetime',
            'sincronizado_at' => 'datetime',
            'latitud' => 'float',
            'longitud' => 'float',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function tarjetaQr(): BelongsTo
    {
        return $this->belongsTo(TarjetaQr::class);
    }

    public function tarjetaQrAsignacion(): BelongsTo
    {
        return $this->belongsTo(TarjetaQrAsignacion::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }
}
