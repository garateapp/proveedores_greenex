<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\Contratista;
use App\Models\Documento;
use App\Models\TipoDocumento;
use Illuminate\Console\Command;

class GenerateDocumentosAlertas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alertas:generate-documentos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate alerts for pending, expiring, and expired documents';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating document alerts...');

        $this->generateExpiringAlerts();
        $this->generateExpiredAlerts();
        $this->generatePendingAlerts();
        $this->generateRejectedAlerts();

        $this->info('Document alerts generated successfully.');

        return Command::SUCCESS;
    }

    /**
     * Generate alerts for documents expiring soon.
     */
    private function generateExpiringAlerts(): void
    {
        $documents = Documento::with(['tipoDocumento', 'contratista'])
            ->expiringSoon(15)
            ->where('estado', 'aprobado')
            ->get();

        foreach ($documents as $documento) {
            // Check if alert already exists
            $exists = Alerta::where('contratista_id', $documento->contratista_id)
                ->where('tipo', 'documento_por_vencer')
                ->where('documento_id', $documento->id)
                ->where('leida', false)
                ->exists();

            if (! $exists) {
                Alerta::create([
                    'contratista_id' => $documento->contratista_id,
                    'tipo' => 'documento_por_vencer',
                    'titulo' => 'Documento próximo a vencer',
                    'mensaje' => "El documento {$documento->tipoDocumento->nombre} del período {$documento->periodo_display} vencerá el {$documento->fecha_vencimiento->format('d/m/Y')}",
                    'prioridad' => 'media',
                    'documento_id' => $documento->id,
                ]);
            }
        }

        $this->info('Generated '.$documents->count().' expiring document alerts');
    }

    /**
     * Generate alerts for expired documents.
     */
    private function generateExpiredAlerts(): void
    {
        $documents = Documento::with(['tipoDocumento', 'contratista'])
            ->expired()
            ->whereIn('estado', ['aprobado', 'pendiente_validacion'])
            ->get();

        foreach ($documents as $documento) {
            // Check if alert already exists
            $exists = Alerta::where('contratista_id', $documento->contratista_id)
                ->where('tipo', 'documento_vencido')
                ->where('documento_id', $documento->id)
                ->where('leida', false)
                ->exists();

            if (! $exists) {
                Alerta::create([
                    'contratista_id' => $documento->contratista_id,
                    'tipo' => 'documento_vencido',
                    'titulo' => 'Documento vencido',
                    'mensaje' => "El documento {$documento->tipoDocumento->nombre} del período {$documento->periodo_display} está vencido desde el {$documento->fecha_vencimiento->format('d/m/Y')}",
                    'prioridad' => 'alta',
                    'documento_id' => $documento->id,
                ]);
            }
        }

        $this->info('Generated '.$documents->count().' expired document alerts');
    }

    /**
     * Generate alerts for pending documents (not uploaded).
     */
    private function generatePendingAlerts(): void
    {
        $tiposObligatorios = TipoDocumento::obligatory()->active()->get();
        $contratistas = Contratista::where('estado', 'activo')->get();

        $currentYear = now()->year;
        $currentMonth = now()->month;

        $count = 0;

        foreach ($contratistas as $contratista) {
            foreach ($tiposObligatorios as $tipo) {
                // Check if documento for current period exists
                $exists = Documento::where('contratista_id', $contratista->id)
                    ->where('tipo_documento_id', $tipo->id)
                    ->where('periodo_ano', $currentYear)
                    ->where('periodo_mes', $currentMonth - 1) // Previous month
                    ->exists();

                if (! $exists) {
                    // Check if alert already exists
                    $alertExists = Alerta::where('contratista_id', $contratista->id)
                        ->where('tipo', 'documento_pendiente')
                        ->where('mensaje', 'like', "%{$tipo->nombre}%")
                        ->where('leida', false)
                        ->whereMonth('created_at', $currentMonth)
                        ->exists();

                    if (! $alertExists) {
                        Alerta::create([
                            'contratista_id' => $contratista->id,
                            'tipo' => 'documento_pendiente',
                            'titulo' => 'Documento pendiente de carga',
                            'mensaje' => "No se ha cargado el documento {$tipo->nombre} del período ".date('M Y', mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear)),
                            'prioridad' => 'alta',
                        ]);
                        $count++;
                    }
                }
            }
        }

        $this->info("Generated {$count} pending document alerts");
    }

    /**
     * Generate alerts for rejected documents.
     */
    private function generateRejectedAlerts(): void
    {
        $documents = Documento::with(['tipoDocumento', 'contratista'])
            ->byEstado('rechazado')
            ->whereDate('validado_at', '>=', now()->subDays(7))
            ->get();

        foreach ($documents as $documento) {
            // Check if alert already exists
            $exists = Alerta::where('contratista_id', $documento->contratista_id)
                ->where('tipo', 'documento_rechazado')
                ->where('documento_id', $documento->id)
                ->where('leida', false)
                ->exists();

            if (! $exists) {
                Alerta::create([
                    'contratista_id' => $documento->contratista_id,
                    'tipo' => 'documento_rechazado',
                    'titulo' => 'Documento rechazado',
                    'mensaje' => "El documento {$documento->tipoDocumento->nombre} del período {$documento->periodo_display} fue rechazado. Motivo: {$documento->motivo_rechazo}",
                    'prioridad' => 'alta',
                    'documento_id' => $documento->id,
                ]);
            }
        }

        $this->info('Generated '.$documents->count().' rejected document alerts');
    }
}
