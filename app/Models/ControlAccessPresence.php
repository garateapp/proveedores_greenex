<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlAccessPresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'personal_id',
        'nombre',
        'departamento',
        'last_entry_at',
        'last_exit_at',
        'last_event_id_pair',
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'last_entry_at' => 'datetime',
            'last_exit_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'personal_id', 'id');
    }
}
