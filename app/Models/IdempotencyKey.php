<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'clave',
        'endpoint',
        'request_hash',
        'response_status',
        'response_body',
    ];

    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function respuestaDecodificada(): ?array
    {
        $decodificada = json_decode($this->response_body, true);

        return is_array($decodificada) ? $decodificada : null;
    }
}
