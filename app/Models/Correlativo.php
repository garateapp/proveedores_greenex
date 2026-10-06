<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correlativo extends Model
{
    use HasFactory;

    protected $fillable = [
        'clave',
        'ultimo_numero',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_numero' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
