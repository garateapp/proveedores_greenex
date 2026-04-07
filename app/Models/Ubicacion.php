<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ubicacion extends Model
{
    /** @use HasFactory<\Database\Factories\UbicacionFactory> */
    use HasFactory;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'padre_id',
        'nombre',
        'codigo',
        'descripcion',
        'tipo',
        'orden',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activa' => 'boolean',
        ];
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(Ubicacion::class, 'padre_id');
    }

    public function hijosActivos(): HasMany
    {
        return $this->hasMany(Ubicacion::class, 'padre_id')->where('activa', true)->orderBy('orden');
    }

    public function marcaciones(): HasMany
    {
        return $this->hasMany(MarcacionPacking::class, 'ubicacion_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        if ($this->padre) {
            return "{$this->padre->nombre} - {$this->nombre}";
        }

        return $this->nombre;
    }

    public function scopePrincipales($query)
    {
        return $query->whereNull('padre_id')->where('activa', true)->orderBy('orden');
    }

    public function scopeSecundarias($query)
    {
        return $query->whereNotNull('padre_id')->where('activa', true)->orderBy('orden');
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
