<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    use HasFactory;

    protected $fillable = [
        'contratista_id',
        'tipo',
        'titulo',
        'mensaje',
        'prioridad',
        'documento_id',
        'leida',
        'leida_at',
    ];

    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
            'leida_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the contratista associated with this alerta.
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    /**
     * Get the documento associated with this alerta.
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    /**
     * Mark alerta as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'leida' => true,
            'leida_at' => now(),
        ]);
    }

    /**
     * Scope to filter unread alertas.
     */
    public function scopeUnread($query)
    {
        return $query->where('leida', false);
    }

    /**
     * Scope to filter by contratista.
     */
    public function scopeForContratista($query, int $contratistaId)
    {
        return $query->where('contratista_id', $contratistaId);
    }

    /**
     * Scope to filter by tipo.
     */
    public function scopeByTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope to filter by prioridad.
     */
    public function scopeByPrioridad($query, string $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }
}
