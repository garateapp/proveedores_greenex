<?php

namespace Tests\Feature;

use App\Mail\PackingAttendanceReportMail;
use App\Models\Turno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendPackingAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_packing_attendance_report_to_configured_recipients(): void
    {
        Mail::fake();

        config([
            'reports.packing_attendance.to' => 'operaciones@example.com,rrhh@example.com',
            'reports.packing_attendance.cc' => 'jefatura@example.com',
            'reports.packing_attendance.bcc' => null,
            'reports.packing_attendance.subject' => 'Reporte Test Packing',
        ]);

        Turno::query()->create([
            'fecha' => '2026-05-26',
            'nombre' => 'Turno Dia',
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'activo' => true,
        ]);

        $this->artisan('report:packing-attendance', ['--date' => '2026-05-26'])
            ->assertExitCode(0);

        Mail::assertSent(PackingAttendanceReportMail::class, function (PackingAttendanceReportMail $mail): bool {
            return $mail->subjectLine === 'Reporte Test Packing 2026-05-26'
                && $mail->hasTo('operaciones@example.com')
                && $mail->hasTo('rrhh@example.com')
                && $mail->hasCc('jefatura@example.com')
                && $mail->report['date']->toDateString() === '2026-05-26';
        });
    }

    public function test_command_fails_when_recipients_are_not_configured(): void
    {
        Mail::fake();

        config([
            'reports.packing_attendance.to' => null,
            'reports.packing_attendance.cc' => null,
            'reports.packing_attendance.bcc' => null,
        ]);

        $this->artisan('report:packing-attendance', ['--date' => '2026-05-26'])
            ->assertExitCode(1);

        Mail::assertNothingSent();
    }
}
