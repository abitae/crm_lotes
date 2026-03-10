<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas - {{ config('app.name') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #334155; }
        h1 { font-size: 18px; margin-bottom: 4px; color: #0f172a; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
        th { background: #f1f5f9; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        tr:nth-child(even) { background: #f8fafc; }
        .summary-box { margin-bottom: 20px; padding: 12px; background: #f1f5f9; border-radius: 4px; }
        .summary-box h2 { font-size: 14px; margin: 0 0 8px 0; }
        .summary-row { display: flex; gap: 24px; margin-top: 8px; }
        .summary-item { flex: 1; }
        .pct { font-weight: bold; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>Reporte de Ventas por Nivel</h1>
    <p class="meta">{{ config('app.name') }} · Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <div class="summary-box">
        <h2>Resumen global</h2>
        <p>Ventas totales: S/ {{ number_format($globalSold, 0, ',', '.') }} · Meta: S/ {{ number_format($globalGoal, 0, ',', '.') }} · <span class="pct">{{ $globalPct }}%</span></p>
    </div>

    <div class="summary-box">
        <h2>Nivel: {{ $levelName }}</h2>
        <p>Ventas del nivel: S/ {{ number_format($levelSold, 0, ',', '.') }} · Meta: S/ {{ number_format($levelGoal, 0, ',', '.') }} · <span class="pct">{{ $levelPct }}%</span> ({{ $levelAdvisorsCount }} vendedores)</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asesor</th>
                <th>Venta (S/)</th>
                <th>Meta (S/)</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sellersPerformance as $idx => $seller)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $seller['full'] }}</td>
                <td>{{ number_format($seller['Logrado'], 0, ',', '.') }}</td>
                <td>{{ number_format($seller['Meta'], 0, ',', '.') }}</td>
                <td class="pct">{{ $seller['pct'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">Documento generado por {{ config('app.name') }}. Uso interno.</p>
</body>
</html>
