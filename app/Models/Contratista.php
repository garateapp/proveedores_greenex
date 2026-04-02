<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contratista extends Model
{
    /** @use HasFactory<\Database\Factories\ContratistaFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rut',
        'razon_social',
        'nombre_fantasia',
        'direccion',
        'comuna',
        'region',
        'telefono',
        'email',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the users associated with this contratista.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if contratista is active.
     */
    public function isActive(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Check if contratista is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->estado === 'bloqueado';
    }

    /**
     * Get the RUT without formatting (only digits and DV).
     */
    public function getRutSinFormatoAttribute(): string
    {
        return preg_replace('/[^0-9kK-]/', '', $this->rut);
    }

    /**
     * Get the RUT ID (without DV).
     */
    public function getRutIdAttribute(): string
    {
        return str_replace('-', '', explode('-', $this->rut)[0]);
    }
}
