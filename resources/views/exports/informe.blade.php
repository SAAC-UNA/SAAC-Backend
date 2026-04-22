<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Informe de acreditación con enlaces</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            background: #f8fafc;
        }

        .header {
            background: linear-gradient(135deg, #0f3d5e 0%, #1f4e78 100%);
            border-radius: 10px;
            color: #ffffff;
            padding: 16px 18px;
            margin-bottom: 14px;
        }

        .title {
            font-size: 19px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .subtitle {
            color: #dbeafe;
            margin: 0;
            font-size: 10px;
        }

        .summary {
            margin: 8px 0 16px 0;
        }

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }

        .summary-cell {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .summary-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 3px;
        }

        .summary-value {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        .dimension {
            margin-bottom: 14px;
            page-break-inside: avoid;
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .dimension h2 {
            font-size: 14px;
            margin: 0 0 10px 0;
            color: #0f3d5e;
            border-left: 5px solid #1f4e78;
            background: #eff6ff;
            padding: 6px 8px;
            border-radius: 4px;
        }

        .component {
            margin: 8px 0 10px 10px;
            page-break-inside: avoid;
        }

        .component h3 {
            font-size: 12px;
            margin: 0 0 6px 0;
            color: #34546d;
            background: #f8fafc;
            border-left: 3px solid #6b9ac4;
            padding: 5px 7px;
        }

        .criterion {
            margin: 8px 0 12px 10px;
            page-break-inside: avoid;
        }

        .criterion h4 {
            font-size: 11px;
            margin: 0 0 6px 0;
            color: #111827;
            background: #f8fafc;
            border-left: 3px solid #94a3b8;
            padding: 5px 7px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #d9e2ec;
            padding: 6px 7px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background: #1f4e78;
            color: #ffffff;
            font-weight: 700;
        }

        tr:nth-child(even) td {
            background: #f8fafc;
        }

        .link-list {
            margin: 0;
            padding-left: 14px;
        }

        .link-list li {
            margin-bottom: 2px;
            word-break: break-word;
        }

        a {
            color: #0b5cab;
            text-decoration: none;
        }

        .empty {
            padding: 12px;
            border: 1px dashed #cbd5e1;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Informe de acreditación con enlaces</p>
        <p class="subtitle">Sistema SAAC-UNA • Agrupado por dimensión, componente y criterio según la estructura SINAES.</p>
    </div>

    <div class="summary">
        <table class="summary-table">
            <tr>
                <td class="summary-cell" style="width: 50%;">
                    <div class="summary-label">Generado</div>
                    <div class="summary-value">{{ $report['generated_at_human'] ?? now()->format('d/m/Y H:i') }}</div>
                </td>
                <td class="summary-cell" style="width: 50%;">
                    <div class="summary-label">Total de evidencias</div>
                    <div class="summary-value">{{ $report['total_evidences'] ?? 0 }}</div>
                </td>
            </tr>
        </table>
    </div>

    @forelse(($report['dimensions'] ?? []) as $dimension)
        <section class="dimension">
            <h2>{{ $dimension['label'] ?? 'Sin dimensión' }}</h2>

            @foreach(($dimension['components'] ?? []) as $component)
                <div class="component">
                    <h3>{{ $component['label'] ?? 'Sin componente' }}</h3>

                    @foreach(($component['criteria'] ?? []) as $criterion)
                        <div class="criterion">
                            <h4>{{ $criterion['label'] ?? 'Sin criterio' }}</h4>

                            @if(!empty($criterion['evidences']))
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width: 14%;">Evidencia</th>
                                            <th style="width: 28%;">Descripción</th>
                                            <th style="width: 10%;">Estado</th>
                                            <th style="width: 20%;">Responsables</th>
                                            <th style="width: 28%;">Enlaces</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($criterion['evidences'] as $evidence)
                                            <tr>
                                                <td>{{ $evidence['nomenclatura'] ?? 'N/A' }}</td>
                                                <td>{{ $evidence['descripcion'] ?? 'N/A' }}</td>
                                                <td>{{ $evidence['estado'] ?? 'N/A' }}</td>
                                                <td>{{ $evidence['responsables'] ?? 'Sin asignar' }}</td>
                                                <td>
                                                    @if(!empty($evidence['links']))
                                                        <ul class="link-list">
                                                            @foreach($evidence['links'] as $link)
                                                                <li>
                                                                    @if(!empty($link['url']))
                                                                        <a href="{{ $link['url'] }}">{{ $link['label'] ?? $link['url'] }}</a>
                                                                    @else
                                                                        {{ $link['label'] ?? 'Sin enlace disponible' }}
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        Sin enlace disponible
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="empty">No hay evidencias registradas para este criterio.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </section>
    @empty
        <div class="empty">No hay evidencias disponibles para el rango seleccionado.</div>
    @endforelse
</body>
</html>