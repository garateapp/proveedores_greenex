<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpDocument extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'archivo_nombre_original',
        'archivo_ruta',
        'archivo_tamano_kb',
        'tipo_extension',
        'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'archivo_tamano_kb' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function subidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function downloadUrl(): string
    {
        return route('ayuda.download', $this);
    }

    public function sizeForHumans(): string
    {
        $kb = $this->archivo_tamano_kb;

        if ($kb >= 1024) {
            return number_format($kb / 1024, 1).' MB';
        }

        return number_format($kb, 0).' KB';
    }
}
