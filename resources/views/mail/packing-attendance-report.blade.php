<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Asistencia Packing</title>
    <style>
        body { color: #111827; font-family: Arial, sans-serif; }
        table { border-collapse: collapse; margin-top: 14px; width: 100%; }
        th, td { border: 1px solid #e5e7eb; font-size: 12px; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .muted { color: #6b7280; }
        .ok { color: #047857; font-weight: bold; }
        .warning { color: #b45309; font-weight: bold; }
        .danger { color: #b91c1c; font-weight: bold; }
        .mark { margin-bottom: 4px; }
    </style>
</head>
<body>
@php
    $multipleRows = $rows->filter(fn (array $row): bool => $row['has_multiple_marks']);
    $issueRows = $rows->filter(fn (array $row): bool => in_array($row['status'], ['app_sin_control', 'control_sin_app'], true));
@endphp

<h2>Reporte Asistencia Packing</h2>
<p class="muted">Fecha: {{ $date->format('Y-m-d') }}</p>

<h3>Resumen ejecutivo</h3>
<table>
    <thead>
    <tr>
        <th>Total</th>
        <th>App + Control</th>
        <th>App sin control</th>
        <th>Control sin app</th>
        <th>Marcaciones multiples</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ $summary['total'] ?? 0 }}</td>
        <td>{{ $summary['app_control'] ?? 0 }}</td>
        <td>{{ $summary['app_sin_control'] ?? 0 }}</td>
        <td>{{ $summary['control_sin_app'] ?? 0 }}</td>
        <td>{{ $summary['marcaciones_multiples'] ?? 0 }}</td>
    </tr>
    </tbody>
</table>

<h3>Turnos evaluados</h3>
<table>
    <thead>
    <tr>
        <th>Turno</th>
        <th>Horario</th>
        <th>Ubicaciones</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($turnos as $turno)
        <tr>
            <td>{{ $turno['nombre'] }}</td>
            <td>{{ $turno['inicio']->format('Y-m-d H:i') }} - {{ $turno['fin']->format('Y-m-d H:i') }}</td>
            <td>{{ $turno['ubicaciones']->isNotEmpty() ? $turno['ubicaciones']->implode(', ') : 'Todas' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3" class="muted">Sin turnos activos configurados.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>Resumen por turno</h3>
<table>
    <thead>
    <tr>
        <th>Turno</th>
        <th>Total</th>
        <th>App + Control</th>
        <th>App sin control</th>
        <th>Control sin app</th>
        <th>Multiples</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($totalsByTurno as $item)
        <tr>
            <td>{{ $item['turno_nombre'] }}</td>
            <td>{{ $item['total'] }}</td>
            <td>{{ $item['app_control'] }}</td>
            <td>{{ $item['app_sin_control'] }}</td>
            <td>{{ $item['control_sin_app'] }}</td>
            <td>{{ $item['marcaciones_multiples'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="muted">Sin registros por turno.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>Resumen por contratista/departamento</h3>
<table>
    <thead>
    <tr>
        <th>Contratista / Departamento</th>
        <th>Total</th>
        <th>App + Control</th>
        <th>App sin control</th>
        <th>Control sin app</th>
        <th>Multiples</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($totalsByGroup as $item)
        <tr>
            <td>{{ $item['group_label'] }}</td>
            <td>{{ $item['total'] }}</td>
            <td>{{ $item['app_control'] }}</td>
            <td>{{ $item['app_sin_control'] }}</td>
            <td>{{ $item['control_sin_app'] }}</td>
            <td>{{ $item['marcaciones_multiples'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="muted">Sin registros por contratista/departamento.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>Marcaciones multiples en el turno</h3>
<table>
    <thead>
    <tr>
        <th>Turno</th>
        <th>Trabajador</th>
        <th>Estado</th>
        <th>Marcaciones</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($multipleRows as $row)
        <tr>
            <td>{{ $row['turno_nombre'] }}</td>
            <td>{{ $row['nombre'] }}<br><span class="muted">{{ $row['documento'] }}</span></td>
            <td><span class="warning">{{ $row['status_label'] }}</span></td>
            <td>
                @foreach ($row['marcaciones'] as $marcacion)
                    <div class="mark">
                        {{ $marcacion['marcado_en']?->format('H:i:s') }}
                        @if ($marcacion['ubicacion'])
                            - {{ $marcacion['ubicacion'] }}
                        @endif
                        @if ($marcacion['numero_serie'])
                            - {{ $marcacion['numero_serie'] }}
                        @endif
                        @if ($marcacion['device_id'])
                            - {{ $marcacion['device_id'] }}
                        @endif
                    </div>
                @endforeach
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="muted">Sin marcaciones multiples.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>Inconsistencias</h3>
<table>
    <thead>
    <tr>
        <th>Turno</th>
        <th>Trabajador</th>
        <th>Contratista / Departamento</th>
        <th>Estado</th>
        <th>Control acceso</th>
        <th>Marcaciones app</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($issueRows as $row)
        <tr>
            <td>{{ $row['turno_nombre'] }}</td>
            <td>{{ $row['nombre'] }}<br><span class="muted">{{ $row['documento'] }}</span></td>
            <td>{{ $row['group_label'] }}</td>
            <td><span class="danger">{{ $row['status_label'] }}</span></td>
            <td>
                Entrada: {{ $row['primera_entrada']?->format('H:i:s') ?? '-' }}<br>
                Salida: {{ $row['ultima_salida']?->format('H:i:s') ?? '-' }}
            </td>
            <td>
                @forelse ($row['marcaciones'] as $marcacion)
                    <div class="mark">{{ $marcacion['marcado_en']?->format('H:i:s') }} - {{ $marcacion['ubicacion'] ?? 'Sin ubicacion' }}</div>
                @empty
                    <span class="muted">Sin marcacion app</span>
                @endforelse
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="muted">Sin inconsistencias.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>Detalle completo</h3>
<table>
    <thead>
    <tr>
        <th>Turno</th>
        <th>Trabajador</th>
        <th>Contratista / Departamento</th>
        <th>Estado</th>
        <th>Control acceso</th>
        <th>Marcaciones app</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($rows as $row)
        <tr>
            <td>{{ $row['turno_nombre'] }}</td>
            <td>{{ $row['nombre'] }}<br><span class="muted">{{ $row['documento'] }}</span></td>
            <td>{{ $row['group_label'] }}</td>
            <td>
                <span class="{{ $row['status'] === 'app_control' ? 'ok' : 'danger' }}">
                    {{ $row['status_label'] }}
                </span>
                @if ($row['has_multiple_marks'])
                    <br><span class="warning">Marcaciones multiples: {{ $row['marcaciones_count'] }}</span>
                @endif
            </td>
            <td>
                Entrada: {{ $row['primera_entrada']?->format('H:i:s') ?? '-' }}<br>
                Salida: {{ $row['ultima_salida']?->format('H:i:s') ?? '-' }}
            </td>
            <td>
                @forelse ($row['marcaciones'] as $marcacion)
                    <div class="mark">
                        {{ $marcacion['marcado_en']?->format('H:i:s') }}
                        - {{ $marcacion['ubicacion'] ?? 'Sin ubicacion' }}
                        @if ($marcacion['numero_serie'])
                            - {{ $marcacion['numero_serie'] }}
                        @endif
                    </div>
                @empty
                    <span class="muted">Sin marcacion app</span>
                @endforelse
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="muted">Sin registros para los turnos del dia.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
