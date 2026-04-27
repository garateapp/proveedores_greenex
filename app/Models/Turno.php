<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Turno extends Model
{
    /** @use HasFactory<\Database\Factories\TurnoFactory> */
    use HasFactory;

    protected $fillable = [
        'fecha',
        'nombre',
        'hora_inicio',
        'hora_fin',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'hora_inicio' => 'datetime:H:i:s',
            'hora_fin' => 'datetime:H:i:s',
            'activo' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<Carbon|null, string|null>
     */
    protected function fecha(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?Carbon => $value ? Carbon::parse($value) : null,
            set: fn (?string $value): ?string => $value ? Carbon::parse($value)->toDateString() : null,
        );
    }

    public function ubicaciones(): BelongsToMany
    {
        return $this->belongsToMany(Ubicacion::class, 'turno_ubicacion')
            ->withTimestamps();
    }
}
