<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Tickets Emitidos</title>
    <style>
        body { color: #111827; font-family: Arial, sans-serif; }
        table { border-collapse: collapse; margin-top: 14px; width: 100%; }
        th, td { border: 1px solid #e5e7eb; font-size: 12px; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .muted { color: #6b7280; }
        .total { font-size: 20px; font-weight: bold; }
    </style>
</head>
<body>
<h2>Reporte Tickets Emitidos</h2>
<p class="muted">
    Desde {{ $desde->format('Y-m-d H:i') }} hasta {{ $hasta->format('Y-m-d H:i') }}
</p>
<p>Total de tickets emitidos: <span class="total">{{ $total }}</span></p>

<h3>Tickets por contratista</h3>
<table>
    <thead>
    <tr>
        <th>Contratista</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($totalsByContratista as $item)
        <tr>
            <td>{{ $item['contratista'] }}</td>
            <td>{{ $item['total'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="2" class="muted">Sin tickets emitidos en el periodo.</td>
        </tr>
    @endforelse
    </tbody>
</table>

@if ($rutDetalle !== null)
    <h3>Detalle por centro de costo — {{ $rutDetalle['contratista'] }} ({{ $rutDetalle['rut'] }})</h3>
    <table>
        <thead>
        <tr>
            <th>Centro de costo</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($totalsByCentroCosto as $item)
            <tr>
                <td>
                    {{ $item['codigo'] ?? '(sin centro de costo)' }}
                    @if ($item['nombre'])
                        <br><span class="muted">{{ $item['nombre'] }}</span>
                    @endif
                </td>
                <td>{{ $item['total'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="muted">Sin tickets emitidos para este contratista.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endif
</body>
</html>
