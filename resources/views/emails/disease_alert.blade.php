<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Alerta de Enfermedad</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }

        .muted {
            color: #6b7280;
            font-size: 12px;
        }

        .h1 {
            font-size: 18px;
            margin: 0 0 8px;
        }

        .row {
            margin-bottom: 6px;
        }

        .label {
            display: inline-block;
            width: 180px;
            color: #6b7280;
        }

        .value {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="h1">Alerta de Enfermedad</div>
        <div class="muted">Se detectó una acumulación de horas que rebasa el umbral configurado.</div>
        <hr />
        <div class="row"><span class="label">Enfermedad:</span> <span
                class="value">{{ $data['enfermedad'] ?? '' }}</span></div>
        <div class="row"><span class="label">Horas acumuladas:</span> <span
                class="value">{{ number_format((float) ($data['horas'] ?? 0), 1) }}</span></div>
        <div class="row"><span class="label">Umbral de alerta (medio):</span> <span
                class="value">{{ number_format((float) ($data['umbral_medio'] ?? 0), 1) }}</span></div>
        <div class="row"><span class="label">Umbral máximo:</span> <span
                class="value">{{ number_format((float) ($data['umbral_maximo'] ?? 0), 1) }}</span></div>
        <div class="row"><span class="label">Tipo Cultivo ID:</span> <span
                class="value">{{ $data['tipo_cultivo_id'] ?? '' }}</span></div>
        <div class="row"><span class="label">Zona Manejo ID:</span> <span
                class="value">{{ $data['zona_manejo_id'] ?? '' }}</span></div>
        <div class="row"><span class="label">Enfermedad ID:</span> <span
                class="value">{{ $data['enfermedad_id'] ?? '' }}</span></div>
        <hr />
        <div class="muted">Este correo se envía automáticamente cuando la acumulación supera el umbral definido.</div>
    </div>
</body>

</html>
