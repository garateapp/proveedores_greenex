<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'personal_id',
        'nombre',
        'departamento',
        'primera_entrada',
        'ultima_salida',
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'primera_entrada' => 'datetime',
            'ultima_salida' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'personal_id', 'id');
    }
}
