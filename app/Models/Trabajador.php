<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trabajador extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trabajadores';

    // La clave primaria es el RUT sin DV
    protected $primaryKey = 'id';

    // La PK es string, no autoincremental
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'documento',
        'nombre',
        'apellido',
        'contratista_id',
        'estado',
        'email',
        'telefono',
        'fecha_ingreso',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the contratista that owns this trabajador.
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    /**
     * Get the faenas assigned to this trabajador.
     */
    public function faenas(): BelongsToMany
    {
        return $this->belongsToMany(Faena::class, 'faena_trabajador', 'trabajador_id', 'faena_id')
            ->withPivot('fecha_asignacion', 'fecha_desasignacion')
            ->withTimestamps();
    }

    /**
     * Get the documentos associated with this trabajador.
     */
    public function documentosTrabajador(): HasMany
    {
        return $this->hasMany(DocumentoTrabajador::class);
    }

    /**
     * Get the nombre completo attribute.
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Check if trabajador is active.
     */
    public function isActive(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Scope to filter by contratista.
     */
    public function scopeForContratista($query, int $contratistaId)
    {
        return $query->where('contratista_id', $contratistaId);
    }

    /**
     * Scope to filter active trabajadores.
     */
    public function scopeActive($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Validate Chilean RUT using Modulo 11 algorithm.
     */
    public static function validateRut(string $rut): bool
    {
        $rut = str_replace(['.', '-'], '', $rut);
        $dv = strtoupper(substr($rut, -1));
        $numero = substr($rut, 0, -1);

        if (! is_numeric($numero)) {
            return false;
        }

        $suma = 0;
        $multiplo = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += intval($numero[$i]) * $multiplo;
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }

        $resto = $suma % 11;
        $dvCalculado = 11 - $resto;

        if ($dvCalculado == 11) {
            $dvCalculado = '0';
        } elseif ($dvCalculado == 10) {
            $dvCalculado = 'K';
        } else {
            $dvCalculado = (string) $dvCalculado;
        }

        return $dv === $dvCalculado;
    }

    /**
     * Extract ID from documento (RUT without DV).
     */
    public static function extractIdFromDocumento(string $documento): string
    {
        $rut = str_replace(['.', '-'], '', $documento);

        return substr($rut, 0, -1);
    }
}
