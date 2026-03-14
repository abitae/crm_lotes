<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de {{ $viewLabel }} - {{ config('app.name') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #334155; }
        h1 { font-size: 18px; margin-bottom: 4px; color: #0f172a; }
        h2 { font-size: 14px; margin: 0 0 10px 0; color: #0f172a; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 20px; }
        .summary-grid { width: 100%; margin-bottom: 16px; }
        .summary-grid td { width: 25%; padding: 10px; border: 1px solid #e2e8f0; background: #f8fafc; vertical-align: top; }
        .summary-label { display: block; font-size: 9px; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .summary-value { font-size: 14px; font-weight: bold; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
        th { background: #f1f5f9; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        tr:nth-child(even) { background: #f8fafc; }
        .filters { margin-bottom: 16px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>Reporte por {{ $viewLabel }}</h1>
    <p class="meta">{{ config('app.name') }} · Generado el {{ $generatedAt }}</p>

    <div class="filters">
        <h2>Filtros aplicados</h2>
        <p>
            Vista: {{ $viewLabel }}
            @if ($filterLabels['project'])
                · Proyecto: {{ $filterLabels['project'] }}
            @endif
            @if ($filterLabels['team'])
                · Team: {{ $filterLabels['team'] }}
            @endif
            @if ($filterLabels['advisor'])
                · Vendedor: {{ $filterLabels['advisor'] }}
            @endif
            @if ($filterLabels['start_date'])
                · Desde: {{ $filterLabels['start_date'] }}
            @endif
            @if ($filterLabels['end_date'])
                · Hasta: {{ $filterLabels['end_date'] }}
            @endif
        </p>
    </div>

    <table class="summary-grid">
        <tr>
            <td>
                <span class="summary-label">Ventas</span>
                <span class="summary-value">S/ {{ number_format($summary['sold_amount'], 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="summary-label">Meta</span>
                <span class="summary-value">S/ {{ number_format($summary['goal_amount'], 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="summary-label">Cobrado</span>
                <span class="summary-value">S/ {{ number_format($summary['collected_amount'], 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="summary-label">Pendiente</span>
                <span class="summary-value">S/ {{ number_format($summary['pending_amount'], 2, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    @if (count($rows) === 0)
        <div class="filters">
            <p>No hay datos para los filtros seleccionados.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ rtrim($viewLabel, 's') }}</th>
                    <th>Ventas (S/)</th>
                    <th>Meta (S/)</th>
                    <th>%</th>
                    <th>Cobrado (S/)</th>
                    <th>Pendiente (S/)</th>
                    <th>Lotes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ number_format($row['sold_amount'], 2, ',', '.') }}</td>
                        <td>{{ number_format($row['goal_amount'], 2, ',', '.') }}</td>
                        <td>{{ $row['pct'] }}%</td>
                        <td>{{ number_format($row['collected_amount'], 2, ',', '.') }}</td>
                        <td>{{ number_format($row['pending_amount'], 2, ',', '.') }}</td>
                        <td>{{ $row['lots_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footer">Documento generado por {{ config('app.name') }}. Uso interno.</p>
</body>
</html>
