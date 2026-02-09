<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bitácora del Sistema</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>

<h2>Bitácora del Sistema</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Módulo</th>
            <th>Detalle</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
        <tr>
            <td>{{ $log->bitacora_id }}</td>
            <td>{{ $log->user->nombre ?? 'Sin usuario' }}</td>
            <td>{{ $log->actionType->descripcion }}</td>
            <td>{{ $log->modulo }}</td>
            <td>{{ $log->detalle }}</td>
            <td>{{ $log->fecha_hora }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
