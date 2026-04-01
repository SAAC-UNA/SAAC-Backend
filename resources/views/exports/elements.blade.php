<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Elementos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }
        h1 {
            text-align: center;
            color: #1976D2;
            font-size: 16px;
            margin-bottom: 4px;
        }
        .meta {
            font-size: 10px;
            color: #555;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th {
            background-color: #1976D2;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f5f9ff;
        }
        .badge-activo   { color: #2e7d32; font-weight: bold; }
        .badge-inactivo { color: #c62828; font-weight: bold; }
        .footer {
            margin-top: 16px;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>
<body>
    <h1>Reporte de Elementos Filtrados</h1>
    <p class="meta">
        <strong>Fecha de generación:</strong> {{ date('d/m/Y H:i') }} &nbsp;|&nbsp;
        <strong>Total de elementos:</strong> {{ count($elements) }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Nomenclatura</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Categoría</th>
                <th>Estado</th>
                <th>Activo</th>
                <th>Padre</th>
                <th>Fecha Límite</th>
                <th>Responsables</th>
            </tr>
        </thead>
        <tbody>
            @foreach($elements as $element)
                <tr>
                    <td>{{ $element->nomenclatura ?? '—' }}</td>
                    <td>{{ Str::limit($element->descripcion, 60) }}</td>
                    <td>{{ $element->tipo ?? '—' }}</td>
                    <td>{{ $element->categoria ?? '—' }}</td>
                    <td>{{ $element->estado ?? 'N/A' }}</td>
                    <td>
                        @if($element->activo)
                            <span class="badge-activo">Sí</span>
                        @else
                            <span class="badge-inactivo">No</span>
                        @endif
                    </td>
                    <td>{{ $element->parent?->nomenclatura ?? '—' }}</td>
                    <td>{{ $element->fecha_limite?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        @php
                            $responsables = $element->assignments->map(fn($a) => $a->user->nombre ?? $a->user->name ?? '')->filter()->unique()->join(', ');
                        @endphp
                        {{ $responsables ?: 'Sin asignar' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Sistema SAAC &mdash; Reporte generado automáticamente &mdash; {{ date('Y') }}
    </div>
</body>
</html>
