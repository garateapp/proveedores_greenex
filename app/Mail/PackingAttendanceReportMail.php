<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackingAttendanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array<string, mixed>  $report
     */
    public function __construct(
        public array $report,
        public string $subjectLine,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.packing-attendance-report',
            with: [
                'report' => $this->report,
                'rows' => $this->report['rows'],
                'summary' => $this->report['summary'],
                'totalsByTurno' => $this->report['totals_by_turno'],
                'totalsByGroup' => $this->report['totals_by_group'],
                'turnos' => $this->report['turnos'],
                'date' => $this->report['date'],
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
