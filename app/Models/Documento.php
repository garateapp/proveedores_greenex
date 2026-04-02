<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Documento extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contratista_id',
        'tipo_documento_id',
        'periodo_ano',
        'periodo_mes',
        'archivo_nombre_original',
        'archivo_ruta',
        'archivo_tamano_kb',
        'estado',
        'observaciones',
        'motivo_rechazo',
        'fecha_vencimiento',
        'cargado_por',
        'validado_por',
        'validado_at',
    ];

    protected function casts(): array
    {
        return [
            'periodo_ano' => 'integer',
            'periodo_mes' => 'integer',
            'archivo_tamano_kb' => 'integer',
            'fecha_vencimiento' => 'date',
            'validado_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the contratista that owns this documento.
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    /**
     * Get the tipo documento.
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    /**
     * Get the user who uploaded this documento.
     */
    public function cargadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cargado_por');
    }

    /**
     * Get the user who validated this documento.
     */
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    /**
     * Check if documento is approved.
     */
    public function isApproved(): bool
    {
        return $this->estado === 'aprobado';
    }

    /**
     * Check if documento is rejected.
     */
    public function isRejected(): bool
    {
        return $this->estado === 'rechazado';
    }

    /**
     * Check if documento is pending validation.
     */
    public function isPending(): bool
    {
        return $this->estado === 'pendiente_validacion';
    }

    /**
     * Check if documento is expired.
     */
    public function isExpired(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast();
    }

    /**
     * Get the file URL.
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->archivo_ruta);
    }

    /**
     * Get the periodo display.
     */
    public function getPeriodoDisplayAttribute(): string
    {
        if ($this->periodo_mes) {
            return date('M Y', mktime(0, 0, 0, $this->periodo_mes, 1, $this->periodo_ano));
        }

        return (string) $this->periodo_ano;
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
     * Scope to filter expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', now());
    }

    /**
     * Scope to filter documents expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 15)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '>=', now())
            ->whereDate('fecha_vencimiento', '<=', now()->addDays($days));
    }
}
