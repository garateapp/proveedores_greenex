<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratistaTrabajadorHistorial extends Model
{
    protected $table = 'contratista_trabajador_historial';

    protected $fillable = [
        'trabajador_id',
        'contratista_origen_id',
        'contratista_destino_id',
        'usuario_id',
        'motivo',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function contratistaOrigen(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_origen_id');
    }

    public function contratistaDestino(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
