<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantillaDocumentoTrabajador extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plantillas_documentos_trabajador';

    public const FORMATO_PAPEL_A4 = 'a4';

    public const FORMATO_PAPEL_LETTER = 'letter';

    /**
     * @var array<string, string>
     */
    public const FUENTES_DISPONIBLES = [
        'dejavu_sans' => 'DejaVu Sans',
        'dejavu_serif' => 'DejaVu Serif',
        'dejavu_sans_mono' => 'DejaVu Sans Mono',
        'helvetica' => 'Helvetica',
        'times' => 'Times New Roman',
        'courier' => 'Courier New',
    ];

    /**
     * @var array<int, string>
     */
    public const FORMATOS_PAPEL_DISPONIBLES = [
        self::FORMATO_PAPEL_A4,
        self::FORMATO_PAPEL_LETTER,
    ];

    protected $fillable = [
        'tipo_documento_id',
        'nombre',
        'contenido_html',
        'fuente_nombre',
        'fuente_tamano',
        'color_texto',
        'formato_papel',
        'activo',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fuente_tamano' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the tipo de documento linked to this plantilla.
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    /**
     * Get the user who created this plantilla.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Get the user who last updated this plantilla.
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Get digital-signature documentos generated from this plantilla.
     */
    public function documentosTrabajador(): HasMany
    {
        return $this->hasMany(DocumentoTrabajador::class, 'plantilla_documento_trabajador_id');
    }

    /**
     * Scope to filter active plantillas.
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    public function cssFontFamily(): string
    {
        return self::FUENTES_DISPONIBLES[$this->fuente_nombre] ?? self::FUENTES_DISPONIBLES['dejavu_sans'];
    }

    public function dompdfPaperSize(): string
    {
        return in_array($this->formato_papel, self::FORMATOS_PAPEL_DISPONIBLES, true)
            ? $this->formato_papel
            : self::FORMATO_PAPEL_LETTER;
    }
}
