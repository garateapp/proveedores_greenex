<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentoTrabajador extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documentos_trabajadores';

    protected $fillable = [
        'trabajador_id',
        'tipo_documento_id',
        'origen',
        'plantilla_documento_trabajador_id',
        'archivo_nombre_original',
        'archivo_ruta',
        'contenido_html_snapshot',
        'variables_snapshot',
        'firma_imagen_ruta',
        'archivo_tamano_kb',
        'fecha_vencimiento',
        'cargado_por',
        'firmado_por',
        'firmado_at',
        'firma_ip',
        'firma_user_agent',
        'contenido_hash',
    ];

    protected function casts(): array
    {
        return [
            'origen' => 'string',
            'contenido_html_snapshot' => 'string',
            'variables_snapshot' => 'array',
            'archivo_tamano_kb' => 'integer',
            'fecha_vencimiento' => 'date',
            'firmado_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the trabajador that owns the documento.
     */
    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    /**
     * Get the tipo documento.
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    /**
     * Get the plantilla used for this digital signature document.
     */
    public function plantillaDocumentoTrabajador(): BelongsTo
    {
        return $this->belongsTo(PlantillaDocumentoTrabajador::class, 'plantilla_documento_trabajador_id');
    }

    /**
     * Get the user who uploaded this documento.
     */
    public function cargadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cargado_por');
    }

    /**
     * Get the user who signed this documento.
     */
    public function firmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'firmado_por');
    }
}
