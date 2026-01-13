# Configuración del Sistema de Pronósticos Meteorológicos

## 📋 Requisitos Previos

-   Laravel 10+
-   Base de datos configurada
-   API key de OpenWeatherMap

## 🔧 Configuración

### 1. Configurar Variables de Entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
# OpenWeatherMap API Configuration
OPENWEATHERMAP_KEY=tu_api_key_aqui
OPENWEATHERMAP_BASE_URL=https://api.openweathermap.org/data/3.0
OPENWEATHERMAP_TIMEZONE=+06:00
```

### 2. Obtener API Key de OpenWeatherMap

1. Ve a [OpenWeatherMap](https://openweathermap.org/)
2. Crea una cuenta gratuita
3. Ve a "API keys" en tu perfil
4. Copia tu API key
5. Reemplaza `tu_api_key_aqui` en el archivo `.env`

### 3. Verificar Configuración

Ejecuta el siguiente comando para verificar que todo esté configurado correctamente:

```bash
php artisan forecasts:sync --force
```

## 🚀 Uso

### Comandos Disponibles

#### Sincronizar Pronósticos

```bash
# Sincronización interactiva
php artisan forecasts:sync

# Sincronización forzada (sin confirmación)
php artisan forecasts:sync --force
```

#### Limpiar Pronósticos Antiguos

```bash
# Limpiar pronósticos de más de 30 días (por defecto)
php artisan forecasts:clean

# Limpiar pronósticos de más de X días
php artisan forecasts:clean --days=60
```

### API Endpoints

#### Obtener Pronósticos de una Parcela

```
GET /api/forecast/{parcela_id}
```

#### Ejecutar Sincronización vía API

```
POST /api/forecast/sync
```

## 📊 Estructura de Datos

### Tabla `forecasts`

-   `id`: ID único del pronóstico
-   `parcela_id`: ID de la parcela
-   `fecha_solicita`: Fecha cuando se solicitó el pronóstico
-   `hora_solicita`: Hora cuando se solicitó el pronóstico
-   `lat`: Latitud de la parcela
-   `lon`: Longitud de la parcela
-   `fecha_prediccion`: Fecha del pronóstico
-   `sunriseTime`: Hora de salida del sol
-   `sunsetTime`: Hora de puesta del sol
-   `temperatureHigh`: Temperatura máxima
-   `temperatureLow`: Temperatura mínima
-   `precipProbability`: Probabilidad de precipitación
-   `hourly`: Datos horarios en JSON
-   `summary`: Resumen del clima
-   `icon`: Icono del clima

### Tabla `forecast_hourly`

-   `id`: ID único del dato horario
-   `forecast_id`: ID del pronóstico padre
-   `parcela_id`: ID de la parcela
-   `fecha`: Fecha y hora del dato
-   `humedad`: Humedad relativa
-   `temperatura`: Temperatura

## 🔄 Programación Automática

### Usando Cron (Recomendado)

Agrega las siguientes líneas a tu crontab:

```bash
# Sincronizar pronósticos cada 6 horas
0 */6 * * * cd /path/to/your/project && php artisan forecasts:sync --force

# Limpiar pronósticos antiguos diariamente a las 2 AM
0 2 * * * cd /path/to/your/project && php artisan forecasts:clean --days=30
```

### Usando Laravel Scheduler

En `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sincronizar pronósticos cada 6 horas
    $schedule->command('forecasts:sync --force')
             ->everyFourHours()
             ->withoutOverlapping();

    // Limpiar pronósticos antiguos diariamente
    $schedule->command('forecasts:clean --days=30')
             ->daily()
             ->at('02:00');
}
```

## 🛠️ Mantenimiento

### Verificar Estado del Sistema

```bash
# Verificar configuración
php artisan config:show services.openweathermap

# Verificar conectividad con la API
php artisan forecasts:sync --force
```

### Logs

Los logs se guardan en `storage/logs/laravel.log` con los siguientes niveles:

-   `info`: Información general del proceso
-   `warning`: Advertencias (parcelas sin coordenadas)
-   `error`: Errores de API o base de datos
-   `debug`: Información detallada para debugging

### Monitoreo

Revisa regularmente:

1. Los logs para errores
2. El tamaño de las tablas de pronósticos
3. La frecuencia de actualización de datos
4. La calidad de los datos recibidos

## 🚨 Solución de Problemas

### Error: "API key de OpenWeatherMap no configurada"

-   Verifica que `OPENWEATHERMAP_KEY` esté en tu archivo `.env`
-   Ejecuta `php artisan config:cache` después de modificar `.env`

### Error: "No se encontraron parcelas"

-   Verifica que existan parcelas en la base de datos
-   Asegúrate de que las parcelas tengan coordenadas (lat/lon)

### Error: "Error de conexión"

-   Verifica tu conexión a internet
-   Comprueba que la API key sea válida
-   Revisa los límites de tu plan de OpenWeatherMap

### Datos incompletos

-   Verifica que las parcelas tengan coordenadas válidas
-   Revisa los logs para errores específicos de la API

## 📈 Optimización

### Para Grandes Volúmenes de Datos

1. **Usar Colas**: Implementa jobs para procesar parcelas en segundo plano
2. **Chunking**: Procesa parcelas en lotes más pequeños
3. **Caching**: Cachea respuestas de la API para evitar llamadas repetidas
4. **Índices**: Agrega índices a las columnas más consultadas

### Ejemplo de Job

```php
// Crear un job para procesar una parcela individual
php artisan make:job ProcessForecastForParcela
```

## 🔒 Seguridad

-   Nunca commits la API key en el código
-   Usa variables de entorno para todas las credenciales
-   Implementa rate limiting para las APIs
-   Monitorea el uso de la API key

## 📞 Soporte

Para problemas específicos:

1. Revisa los logs en `storage/logs/laravel.log`
2. Verifica la configuración con `php artisan config:show`
3. Ejecuta los comandos con `--verbose` para más detalles
