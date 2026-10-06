<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValeLote extends Model
{
    use HasFactory;

    protected $table = 'vales_lote';

    protected $fillable = [
        'codigo',
        'lote_id',
        'posicion',
        'administrador_id',
        'centro_costo_id',
        'emitido_en',
        'canjeado_en',
        'canjeado_por',
    ];

    protected function casts(): array
    {
        return [
            'posicion' => 'integer',
            'emitido_en' => 'datetime',
            'canjeado_en' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrador_id');
    }

    public function canjeadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canjeado_por');
    }

    /**
     * `withTrashed` por la misma razón que en los vales de almuerzo: el vale
     * conserva el centro de costo con el que se emitió aunque el catálogo se
     * haya dado de baja después.
     */
    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class)->withTrashed();
    }

    public function estaCanjeado(): bool
    {
        return $this->canjeado_en !== null;
    }
}
