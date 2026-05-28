<?php

namespace App\Console\Commands;

use App\Mail\PackingAttendanceReportMail;
use App\Services\PackingAttendanceReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendPackingAttendanceReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:packing-attendance {--date= : Fecha a reportar en formato YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía el reporte de asistencia app packing versus control de acceso.';

    /**
     * Execute the console command.
     */
    public function handle(PackingAttendanceReportService $service): int
    {
        $reportDate = $this->resolveReportDate();

        if (! ($reportDate instanceof Carbon)) {
            return self::FAILURE;
        }

        $recipients = $this->resolveRecipients();

        if (empty($recipients['to'])) {
            $this->error('No hay destinatarios configurados para PACKING_ATTENDANCE_REPORT_TO.');

            return self::FAILURE;
        }

        $report = $service->buildForDate($reportDate);
        $subjectLine = $this->buildSubject($reportDate);

        $mail = Mail::to($recipients['to']);

        if (! empty($recipients['cc'])) {
            $mail->cc($recipients['cc']);
        }

        if (! empty($recipients['bcc'])) {
            $mail->bcc($recipients['bcc']);
        }

        $mail->send(new PackingAttendanceReportMail($report, $subjectLine));

        $this->info("Reporte de asistencia packing enviado para {$reportDate->toDateString()}.");

        return self::SUCCESS;
    }

    private function resolveReportDate(): ?Carbon
    {
        $timezone = config('app.timezone', 'America/Santiago');
        $date = $this->option('date');

        try {
            return $date
                ? Carbon::parse((string) $date, $timezone)->startOfDay()
                : Carbon::now($timezone)->subDay()->startOfDay();
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
            'to' => $this->parseRecipientList(config('reports.packing_attendance.to')),
            'cc' => $this->parseRecipientList(config('reports.packing_attendance.cc')),
            'bcc' => $this->parseRecipientList(config('reports.packing_attendance.bcc')),
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
        $base = config('reports.packing_attendance.subject', 'Reporte Asistencia Packing');

        return (string) Str::of($base)
            ->append(' ')
            ->append($reportDate->toDateString());
    }
}
