<?php

return [
    'packing_attendance' => [
        'to' => env('PACKING_ATTENDANCE_REPORT_TO'),
        'cc' => env('PACKING_ATTENDANCE_REPORT_CC'),
        'bcc' => env('PACKING_ATTENDANCE_REPORT_BCC'),
        'subject' => env('PACKING_ATTENDANCE_REPORT_SUBJECT', 'Reporte Asistencia Packing'),
    ],
];
