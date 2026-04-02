<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoPago extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'estados_pago';

    protected $fillable = [
        'contratista_id',
        'numero_documento',
        'fecha_documento',
        'monto',
        'estado',
        'observaciones',
        'motivo_retencion',
        'fecha_pago_estimada',
        'fecha_pago_real',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_documento' => 'date',
            'monto' => 'decimal:2',
            'fecha_pago_estimada' => 'date',
            'fecha_pago_real' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot method to log estado changes.
     */
    protected static function booted(): void
    {
        static::updating(function (EstadoPago $estadoPago) {
            if ($estadoPago->isDirty('estado')) {
                HistorialEstadoPago::create([
                    'estado_pago_id' => $estadoPago->id,
                    'estado_anterior' => $estadoPago->getOriginal('estado'),
                    'estado_nuevo' => $estadoPago->estado,
                    'comentario' => $estadoPago->observaciones,
                    'usuario_id' => auth()->id() ?? 1,
                ]);
            }
        });
    }

    /**
     * Get the contratista associated with this estado de pago.
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    /**
     * Get the user who last updated this estado de pago.
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Get the historial for this estado de pago.
     */
    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstadoPago::class);
    }

    /**
     * Check if estado de pago is retenido.
     */
    public function isRetenido(): bool
    {
        return $this->estado === 'retenido';
    }

    /**
     * Check if estado de pago is pagado.
     */
    public function isPagado(): bool
    {
        return $this->estado === 'pagado';
    }

    /**
     * Scope to filter by contratista.
     */
    public function scopeForContratista($query, int $contratistaId)
    {
        return $query->where('contratista_id', $contratistaId);
    }

    /**
     * Scope to filter by estado.
     */
    public function scopeByEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope to filter pending payment.
     */
    public function scopePendingPayment($query)
    {
        return $query->whereIn('estado', ['recibido', 'en_revision', 'aprobado_pago']);
    }
}
