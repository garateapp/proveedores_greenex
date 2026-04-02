<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoFaena extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the faenas for this tipo.
     */
    public function faenas(): HasMany
    {
        return $this->hasMany(Faena::class);
    }

    /**
     * Get the tipos de documento associated with this tipo de faena.
     */
    public function tiposDocumento(): BelongsToMany
    {
        return $this->belongsToMany(TipoDocumento::class, 'tipo_documento_tipo_faena')
            ->withTimestamps();
    }

    /**
     * Scope to filter active tipos.
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}
