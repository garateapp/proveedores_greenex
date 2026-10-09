<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('alertas:generate-documentos')->dailyAt('06:00');
Schedule::command('alertas:notificar-documentos-trabajadores')->dailyAt('06:10');
Schedule::command('garatepass:purge-idempotency-keys')->hourly();
Schedule::command('report:tickets-emitidos')->dailyAt('16:00');
Schedule::command('report:tickets-emitidos')->dailyAt('23:00');
