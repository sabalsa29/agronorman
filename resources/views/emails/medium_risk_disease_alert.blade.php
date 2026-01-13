<!DOCTYPE html>
<html>

<head>
    <title>⚠️ ALERTA - RIESGO MEDIO</title>
</head>

<body style="font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4;">
    <div
        style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <div
            style="background-color: #ff8c00; color: white; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px;">
            <h1 style="margin: 0; font-size: 24px;">⚠️ ALERTA</h1>
            <p style="margin: 10px 0 0 0; font-size: 18px;">ENFERMEDAD EN RIESGO MEDIO</p>
        </div>

        <div style="margin-bottom: 20px;">
            <h2 style="color: #ff8c00; margin-bottom: 15px;">Detalles de la Alerta:</h2>
            <p><strong>Enfermedad:</strong> {{ $data['enfermedad'] }}</p>
            <p><strong>Tipo de Cultivo:</strong> {{ $data['tipo_cultivo'] }}</p>
            <p><strong>Estación:</strong> {{ $data['estacion'] }}</p>
            <p><strong>Zona de Manejo:</strong> {{ $data['zona_manejo'] }}</p>
            <p><strong>Fecha de Detección:</strong> {{ $data['fecha_deteccion'] }}</p>
        </div>

        <div
            style="background-color: #fff3cd; border: 2px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #856404;">⚠️ MONITOREO REQUERIDO</h3>
            <p><strong>Horas Acumuladas:</strong> {{ $data['horas_acumuladas'] }} horas</p>
            <p><strong>Umbral de Riesgo:</strong> {{ $data['umbral_riesgo'] }} horas</p>
            <p><strong>Estado:</strong> <span style="color: #ff8c00; font-weight: bold;">{{ $data['estado'] }}</span></p>
        </div>

        <div style="margin-bottom: 20px;">
            <h3 style="color: #ff8c00; margin-bottom: 10px;">Recomendaciones Preventivas:</h3>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($data['recomendaciones'] as $recomendacion)
                    <li style="margin-bottom: 5px;">{{ $recomendacion }}</li>
                @endforeach
            </ul>
        </div>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
            <p style="margin: 0; color: #666; font-size: 14px;">
                Este es un mensaje automático del sistema PIA Alertas.<br>
                Monitoree las condiciones y tome medidas preventivas si es necesario.
            </p>
        </div>

    </div>
</body>

</html>
