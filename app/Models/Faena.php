<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faena extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tipo_faena_id',
        'nombre',
        'codigo',
        'descripcion',
        'ubicacion',
        'estado',
        'fecha_inicio',
        'fecha_termino',
    ];

    protected function casts(): array
    {
        return [
            'tipo_faena_id' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_termino' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the tipo faena for this faena.
     */
    public function tipoFaena(): BelongsTo
    {
        return $this->belongsTo(TipoFaena::class);
    }

    /**
     * Get the trabajadores assigned to this faena.
     */
    public function trabajadores(): BelongsToMany
    {
        return $this->belongsToMany(Trabajador::class, 'faena_trabajador', 'faena_id', 'trabajador_id')
            ->withPivot('fecha_asignacion', 'fecha_desasignacion')
            ->withTimestamps();
    }

    /**
     * Check if faena is active.
     */
    public function isActive(): bool
    {
        return $this->estado === 'activa';
    }

    /**
     * Scope to filter active faenas.
     */
    public function scopeActive($query)
    {
        return $query->where('estado', 'activa');
    }
}
