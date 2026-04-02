<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstadoPago extends Model
{
    use HasFactory;

    protected $table = 'historial_estado_pago';

    protected $fillable = [
        'estado_pago_id',
        'estado_anterior',
        'estado_nuevo',
        'comentario',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the estado de pago associated with this historial.
     */
    public function estadoPago(): BelongsTo
    {
        return $this->belongsTo(EstadoPago::class);
    }

    /**
     * Get the user who made the change.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
