<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Evidencias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Reporte de Evidencias Filtradas</h1>
    <p><strong>Fecha de generación:</strong> {{ date('d/m/Y H:i') }}</p>
    <p><strong>Total de evidencias:</strong> {{ count($evidences) }}</p>

    <table>
        <thead>
            <tr>
                <th>Nomenclatura</th>
                <th>Descripción</th>
                <th>Criterio</th>
                <th>Estado</th>
                <th>Responsables</th>
                <th>Fecha Publicación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($evidences as $evidence)
                <tr>
                    <td>{{ $evidence->nomenclatura }}</td>
                    <td>{{ $evidence->descripcion }}</td>
                    <td>{{ $evidence->criterion->nomenclatura }} - {{ Str::limit($evidence->criterion->descripcion, 50) }}</td>
                    <td>{{ $evidence->evidenceState->nombre ?? 'N/A' }}</td>
                    <td>
                        @if($evidence->assignments->count() > 0)
                            {{ $evidence->assignments->pluck('user.nombre')->join(', ') }}
                        @else
                            Sin asignar
                        @endif
                    </td>
                    <td>{{ $evidence->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Sistema de Acreditación y Aseguramiento de la Calidad - UNA</p>
    </div>
</body>
</html>
