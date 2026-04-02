<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\DocumentoTrabajador;
use App\Notifications\DocumentoTrabajadorPorVencerNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyExpiringWorkerDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alertas:notificar-documentos-trabajadores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía alertas de documentos de trabajadores que vencen en 7 días';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetDate = now()->addDays(7)->toDateString();

        $documentos = DocumentoTrabajador::query()
            ->whereDate('fecha_vencimiento', $targetDate)
            ->with([
                'tipoDocumento:id,nombre',
                'trabajador:id,documento,nombre,apellido,contratista_id',
                'trabajador.contratista:id,razon_social,nombre_fantasia',
                'trabajador.contratista.users:id,name,email,contratista_id,is_active',
            ])
            ->get();

        $notifiedUsers = 0;
        $createdAlerts = 0;

        foreach ($documentos as $documento) {
            $trabajador = $documento->trabajador;
            if (! $trabajador || ! $trabajador->contratista) {
                continue;
            }

            $contratista = $trabajador->contratista;
            $tipoDocumentoNombre = $documento->tipoDocumento?->nombre ?? 'Documento';
            $fechaVencimiento = $documento->fecha_vencimiento?->format('d/m/Y') ?? 'sin fecha';

            $message = "El documento {$tipoDocumentoNombre} de {$trabajador->nombre_completo} ({$trabajador->documento}) vence el {$fechaVencimiento}.";

            $hasAlertForToday = Alerta::query()
                ->where('contratista_id', $contratista->id)
                ->where('tipo', 'documento_por_vencer')
                ->where('titulo', 'Documento de trabajador próximo a vencer')
                ->where('mensaje', $message)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if (! $hasAlertForToday) {
                Alerta::query()->create([
                    'contratista_id' => $contratista->id,
                    'tipo' => 'documento_por_vencer',
                    'titulo' => 'Documento de trabajador próximo a vencer',
                    'mensaje' => $message,
                    'prioridad' => 'alta',
                    'documento_id' => null,
                ]);
                $createdAlerts++;
            }

            $users = $contratista->users
                ->filter(fn ($user) => (bool) $user->is_active && ! empty($user->email))
                ->values();

            if ($users->isEmpty()) {
                continue;
            }

            Notification::send($users, new DocumentoTrabajadorPorVencerNotification($documento));
            $notifiedUsers += $users->count();
        }

        $this->info("Documentos evaluados: {$documentos->count()}");
        $this->info("Usuarios notificados: {$notifiedUsers}");
        $this->info("Alertas creadas: {$createdAlerts}");

        return self::SUCCESS;
    }
}
