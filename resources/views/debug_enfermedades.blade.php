<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Enfermedades</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .debug-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .debug-title {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .json-data {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            white-space: pre-wrap;
            font-size: 12px;
            overflow-x: auto;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1 class="debug-title">🔍 Debug Enfermedades - Datos Completos</h1>
        
        <div class="info">
            <strong>Parámetros de la consulta:</strong><br>
            Tipo de Cultivo ID: {{ $tipoCultivoId ?? 'No especificado' }}<br>
            Estación ID: {{ $estacionId ?? 'No especificado' }}<br>
            Período: {{ $periodo ?? 'No especificado' }}<br>
            Start Date: {{ $startDate ?? 'No especificado' }}<br>
            End Date: {{ $endDate ?? 'No especificado' }}
        </div>

        @if(isset($enfermedades) && $enfermedades->count() > 0)
            <div class="success">
                ✅ Se encontraron {{ $enfermedades->count() }} enfermedades
            </div>

            @foreach($enfermedades as $index => $enfermedad)
                <div class="debug-container">
                    <h2 class="debug-title">🏥 Enfermedad #{{ $index + 1 }}: {{ $enfermedad->nombre }} (ID: {{ $enfermedad->id }})</h2>
                    
                    <h3>📊 Datos Básicos de la Enfermedad:</h3>
                    <div class="json-data">
{
    "id": {{ $enfermedad->id }},
    "nombre": "{{ $enfermedad->nombre }}",
    "slug": "{{ $enfermedad->slug ?? 'N/A' }}",
    "status": {{ $enfermedad->status ?? 'N/A' }},
    "riesgo_humedad": {{ $enfermedad->riesgo_humedad ?? 'null' }},
    "riesgo_temperatura": {{ $enfermedad->riesgo_temperatura ?? 'null' }},
    "riesgo_humedad_max": {{ $enfermedad->riesgo_humedad_max ?? 'null' }},
    "riesgo_temperatura_max": {{ $enfermedad->riesgo_temperatura_max ?? 'null' }},
    "riesgo_medio": {{ $enfermedad->riesgo_medio ?? 'null' }},
    "riesgo_mediciones": {{ $enfermedad->riesgo_mediciones ?? 'null' }}
}
                    </div>

                    <h3>🎯 Semáforo de Riesgo:</h3>
                    <div class="json-data">
@if(isset($enfermedad->semaforo_historicos))
{{ json_encode($enfermedad->semaforo_historicos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
@else
{
    "error": "No se pudo calcular el semáforo de riesgo"
}
@endif
                    </div>

                    <h3>📅 Períodos de Condiciones:</h3>
                    <div class="json-data">
@if(isset($enfermedad->periodos_condiciones))
{
    "historicos": {
        "count": {{ $enfermedad->periodos_condiciones['historicos']->count() ?? 0 }},
        "data": {{ json_encode($enfermedad->periodos_condiciones['historicos'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
    },
    "pronostico": {
        "count": {{ isset($enfermedad->periodos_condiciones['pronostico']) ? $enfermedad->periodos_condiciones['pronostico']->count() : 0 }},
        "data": {{ json_encode($enfermedad->periodos_condiciones['pronostico'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
    }
}
@else
{
    "error": "No se encontraron períodos de condiciones"
}
@endif
                    </div>

                    @if(isset($enfermedad->timeline_acumulaciones))
                    <h3>📈 Timeline de Acumulaciones:</h3>
                    <div class="json-data">
{{ json_encode($enfermedad->timeline_acumulaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                    </div>
                    @endif

                    <h3>🔍 Análisis de Datos:</h3>
                    <div class="info">
                        @if(isset($enfermedad->semaforo_historicos))
                            <strong>Lógica del Semáforo:</strong><br>
                            • Horas acumuladas: {{ $enfermedad->semaforo_historicos['horas_acumuladas'] ?? 'N/A' }}<br>
                            • Umbral mínimo (riesgo_medio): {{ $enfermedad->semaforo_historicos['umbral'] ?? 'N/A' }}<br>
                            • Umbral máximo (riesgo_mediciones): {{ $enfermedad->semaforo_historicos['umbral_maximo'] ?? 'N/A' }}<br>
                            • Color resultante: {{ $enfermedad->semaforo_historicos['color'] ?? 'N/A' }}<br>
                            • Etapa: {{ $enfermedad->semaforo_historicos['etapa'] ?? 'N/A' }}<br>
                            • Porcentaje: {{ $enfermedad->semaforo_historicos['porcentaje'] ?? 'N/A' }}%
                        @else
                            <span class="error">❌ No se pudo calcular el semáforo</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="error">
                ❌ No se encontraron enfermedades para los parámetros especificados
            </div>
        @endif

        @if(isset($error))
        <div class="debug-container">
            <h2 class="debug-title">❌ Error Detectado</h2>
            <div class="error">
                <strong>Mensaje de error:</strong> {{ $error }}<br>
                <strong>Archivo:</strong> {{ $errorFile ?? 'N/A' }}<br>
                <strong>Línea:</strong> {{ $errorLine ?? 'N/A' }}
            </div>
        </div>
        @endif

        <div class="debug-container">
            <h2 class="debug-title">📋 Información del Sistema</h2>
            <div class="json-data">
{
    "php_version": "{{ phpversion() }}",
    "laravel_version": "{{ app()->version() }}",
    "timestamp": "{{ now() }}",
    "memory_usage": "{{ memory_get_usage(true) }} bytes",
    "peak_memory": "{{ memory_get_peak_usage(true) }} bytes"
}
            </div>
        </div>
    </div>
</body>
</html>
