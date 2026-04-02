<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use HasFactory;

    // INMUTABILIDAD: No permitir actualizaciones ni eliminaciones
    // Conforme a Resolución Exenta N° 38 de la DT
    public static $allowedOperations = ['create', 'read'];

    protected $fillable = [
        'trabajador_id',
        'faena_id',
        'contratista_id',
        'tipo',
        'fecha_hora',
        'latitud',
        'longitud',
        'ubicacion_texto',
        'registrado_por',
        'observaciones',
        'sincronizado',
        'sincronizado_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
            'sincronizado' => 'boolean',
            'sincronizado_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Override update to prevent modifications (compliance).
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException('Los registros de asistencia no pueden ser modificados (cumplimiento DT)');
    }

    /**
     * Override delete to prevent deletions (compliance).
     */
    public function delete(): ?bool
    {
        throw new \RuntimeException('Los registros de asistencia no pueden ser eliminados (cumplimiento DT)');
    }

    /**
     * Get the trabajador associated with this asistencia.
     */
    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    /**
     * Get the faena associated with this asistencia.
     */
    public function faena(): BelongsTo
    {
        return $this->belongsTo(Faena::class);
    }

    /**
     * Get the contratista associated with this asistencia.
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    /**
     * Get the user who registered this asistencia.
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Scope to filter by contratista.
     */
    public function scopeForContratista($query, int $contratistaId)
    {
        return $query->where('contratista_id', $contratistaId);
    }

    /**
     * Scope to filter by trabajador.
     */
    public function scopeForTrabajador($query, string $trabajadorId)
    {
        return $query->where('trabajador_id', $trabajadorId);
    }

    /**
     * Scope to filter by faena.
     */
    public function scopeForFaena($query, int $faenaId)
    {
        return $query->where('faena_id', $faenaId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('fecha_hora', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by tipo.
     */
    public function scopeByTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope to filter pending sync.
     */
    public function scopePendingSync($query)
    {
        return $query->where('sincronizado', false);
    }

    /**
     * Calculate worked hours for a trabajador on a specific date.
     */
    public static function calculateWorkedHours(string $trabajadorId, string $date): float
    {
        $entradas = self::where('trabajador_id', $trabajadorId)
            ->whereDate('fecha_hora', $date)
            ->where('tipo', 'entrada')
            ->orderBy('fecha_hora')
            ->get();

        $salidas = self::where('trabajador_id', $trabajadorId)
            ->whereDate('fecha_hora', $date)
            ->where('tipo', 'salida')
            ->orderBy('fecha_hora')
            ->get();

        $totalHours = 0;

        foreach ($entradas as $index => $entrada) {
            if (isset($salidas[$index])) {
                $diff = $entrada->fecha_hora->diffInMinutes($salidas[$index]->fecha_hora);
                $totalHours += $diff / 60;
            }
        }

        return round($totalHours, 2);
    }
}
