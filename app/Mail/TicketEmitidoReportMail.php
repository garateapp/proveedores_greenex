<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketEmitidoReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $report
     */
    public function __construct(
        public array $report,
        public string $subjectLine,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ticket-emitido-report',
            with: [
                'desde' => $this->report['desde'],
                'hasta' => $this->report['hasta'],
                'total' => $this->report['total'],
                'totalsByContratista' => $this->report['totals_by_contratista'],
                'rutDetalle' => $this->report['rut_detalle'],
                'totalsByCentroCosto' => $this->report['totals_by_centro_costo'],
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
