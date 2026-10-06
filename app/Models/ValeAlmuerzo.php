<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValeAlmuerzo extends Model
{
    use HasFactory;

    protected $table = 'vales_almuerzo';

    protected $fillable = [
        'token',
        'trabajador_id',
        'tarjeta_qr_id',
        'asignacion_id',
        'contratista',
        'centro_costo_id',
        'emitido_en',
    ];

    protected function casts(): array
    {
        return [
            'emitido_en' => 'datetime',
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

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(TarjetaQrAsignacion::class, 'asignacion_id');
    }

    /**
     * `withTrashed` a propósito: el vale es un comprobante impreso y su centro
     * de costo no cambia aunque el catálogo se dé de baja. Sin esto, retirar un
     * centro de costo haría que los vales ya emitidos dejaran de mostrar el
     * código que realmente se imprimió en el papel.
     */
    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class)->withTrashed();
    }
}
