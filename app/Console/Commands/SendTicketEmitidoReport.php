<?php

namespace App\Console\Commands;

use App\Mail\TicketEmitidoReportMail;
use App\Services\TicketEmitidoReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendTicketEmitidoReport extends Command
{
    /**
     * @var string
     */
    protected $signature = 'report:tickets-emitidos {--date= : Fecha a reportar en formato YYYY-MM-DD}';

    /**
     * @var string
     */
    protected $description = 'Envía el reporte de tickets de almuerzo emitidos, desglosado por contratista y centro de costo.';

    public function handle(TicketEmitidoReportService $service): int
    {
        $reportDate = $this->resolveReportDate();

        if (! ($reportDate instanceof Carbon)) {
            return self::FAILURE;
        }

        $recipients = $this->resolveRecipients();

        if (empty($recipients['to'])) {
            $this->error('No hay destinatarios configurados para TICKET_EMITIDO_REPORT_TO.');

            return self::FAILURE;
        }

        $report = $service->buildForDate($reportDate);

        $mail = Mail::to($recipients['to']);

        if (! empty($recipients['cc'])) {
            $mail->cc($recipients['cc']);
        }

        if (! empty($recipients['bcc'])) {
            $mail->bcc($recipients['bcc']);
        }

        $mail->send(new TicketEmitidoReportMail($report, $this->buildSubject($reportDate)));

        $this->info("Reporte de tickets emitidos enviado para {$reportDate->toDateString()}.");

        return self::SUCCESS;
    }

    private function resolveReportDate(): ?Carbon
    {
        $timezone = config('app.timezone', 'America/Santiago');
        $date = $this->option('date');

        try {
            return $date
                ? Carbon::parse((string) $date, $timezone)->startOfDay()
                : Carbon::now($timezone)->startOfDay();
        } catch (Throwable) {
            $this->error('La opción --date debe tener una fecha válida, por ejemplo: 2026-05-26.');

            return null;
        }
    }

    /**
     * @return array{to: list<string>, cc: list<string>, bcc: list<string>}
     */
    private function resolveRecipients(): array
    {
        return [
            'to' => $this->parseRecipientList(config('reports.ticket_emitido.to')),
            'cc' => $this->parseRecipientList(config('reports.ticket_emitido.cc')),
            'bcc' => $this->parseRecipientList(config('reports.ticket_emitido.bcc')),
        ];
    }

    /**
     * @return list<string>
     */
    private function parseRecipientList(?string $raw): array
    {
        return collect(explode(',', (string) $raw))
            ->map(fn (string $value): string => trim($value))
            ->filter(fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    private function buildSubject(Carbon $reportDate): string
    {
        $base = config('reports.ticket_emitido.subject', 'Reporte Tickets Emitidos');

        return (string) Str::of($base)
            ->append(' ')
            ->append($reportDate->toDateString());
    }
}
