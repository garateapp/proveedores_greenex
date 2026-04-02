<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoDocumento extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'periodicidad',
        'permite_multiples_en_mes',
        'es_obligatorio',
        'es_documento_trabajador',
        'dias_vencimiento',
        'formatos_permitidos',
        'tamano_maximo_kb',
        'requiere_validacion',
        'instrucciones',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_obligatorio' => 'boolean',
            'permite_multiples_en_mes' => 'boolean',
            'es_documento_trabajador' => 'boolean',
            'requiere_validacion' => 'boolean',
            'activo' => 'boolean',
            'formatos_permitidos' => 'array',
            'dias_vencimiento' => 'integer',
            'tamano_maximo_kb' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the documentos for this tipo.
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    /**
     * Get worker signature templates linked to this tipo documento.
     */
    public function plantillasDocumentosTrabajador(): HasMany
    {
        return $this->hasMany(PlantillaDocumentoTrabajador::class);
    }

    /**
     * Get tipos de faena associated with this tipo de documento.
     */
    public function tiposFaena(): BelongsToMany
    {
        return $this->belongsToMany(TipoFaena::class, 'tipo_documento_tipo_faena')
            ->withTimestamps();
    }

    /**
     * Scope to filter active tipos.
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope to filter obligatory tipos.
     */
    public function scopeObligatory($query)
    {
        return $query->where('es_obligatorio', true);
    }
}
