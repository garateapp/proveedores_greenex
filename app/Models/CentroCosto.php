<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentroCosto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'centros_costo';

    protected $fillable = [
        'codigo',
        'nombre',
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

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class);
    }

    public function valesAlmuerzo(): HasMany
    {
        return $this->hasMany(ValeAlmuerzo::class);
    }

    public function valesLote(): HasMany
    {
        return $this->hasMany(ValeLote::class);
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}
