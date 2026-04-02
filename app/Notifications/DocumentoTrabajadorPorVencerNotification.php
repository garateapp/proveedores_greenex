<?php

namespace App\Notifications;

use App\Models\DocumentoTrabajador;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentoTrabajadorPorVencerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public DocumentoTrabajador $documentoTrabajador) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $trabajador = $this->documentoTrabajador->trabajador;
        $tipoDocumento = $this->documentoTrabajador->tipoDocumento;
        $trabajadorNombre = $trabajador?->nombre_completo ?? 'Trabajador no disponible';
        $trabajadorDocumento = $trabajador?->documento ?? '-';
        $tipoDocumentoNombre = $tipoDocumento?->nombre ?? 'Documento';
        $fechaVencimiento = $this->documentoTrabajador->fecha_vencimiento?->format('d/m/Y') ?? 'sin fecha';

        return (new MailMessage)
            ->subject('Alerta: documento de trabajador próximo a vencer')
            ->greeting('Hola,')
            ->line('Se detectó un documento próximo a vencer en la dotación.')
            ->line("Trabajador: {$trabajadorNombre} ({$trabajadorDocumento})")
            ->line("Documento: {$tipoDocumentoNombre}")
            ->line("Fecha de vencimiento: {$fechaVencimiento}")
            ->line('Ingresa al portal para revisar y actualizar el expediente.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $trabajador = $this->documentoTrabajador->trabajador;
        $tipoDocumento = $this->documentoTrabajador->tipoDocumento;

        return [
            'documento_trabajador_id' => $this->documentoTrabajador->id,
            'trabajador_id' => $trabajador?->id,
            'trabajador_nombre' => $trabajador?->nombre_completo,
            'tipo_documento' => $tipoDocumento?->nombre,
            'fecha_vencimiento' => $this->documentoTrabajador->fecha_vencimiento?->toDateString(),
        ];
    }
}
