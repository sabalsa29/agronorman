# 🌤️ Comandos de Pronósticos del Clima

## 📋 Comandos Disponibles

### 1. `weather:update`

Actualiza los pronósticos del clima para todas las parcelas.

**Uso:**

```bash
php artisan weather:update
```

**Opciones:**

-   `--force`: Forzar actualización incluso si ya existen datos

**Ejemplo:**

```bash
php artisan weather:update --force
```

### 2. `weather:clean`

Limpia datos antiguos de pronósticos del clima.

**Uso:**

```bash
php artisan weather:clean [--days=7] [--force]
```

**Opciones:**

-   `--days=7`: Número de días a mantener (por defecto: 7)
-   `--force`: Confirmar sin preguntar

**Ejemplos:**

```bash
# Limpiar datos de más de 7 días (con confirmación)
php artisan weather:clean

# Limpiar datos de más de 3 días sin confirmación
php artisan weather:clean --days=3 --force

# Limpiar datos de más de 1 día
php artisan weather:clean --days=1
```

## ⏰ Programación Automática

Los comandos están programados para ejecutarse automáticamente:

### Actualización de Pronósticos

-   **Frecuencia:** Cada 4 horas
-   **Horarios:** 00:00, 04:00, 08:00, 12:00, 16:00, 20:00
-   **Comando:** `weather:update`
-   **Log:** `storage/logs/weather-update.log`

### Limpieza de Datos Antiguos

-   **Frecuencia:** Diariamente
-   **Horario:** 02:00 AM
-   **Comando:** `weather:clean --days=7 --force`
-   **Log:** `storage/logs/weather-clean.log`

## 📊 Estadísticas de Uso

### Límites de API

-   **Plan gratuito:** 1,000 llamadas/día
-   **Parcelas:** 47
-   **Llamadas por ejecución:** 47
-   **Ejecuciones máximas/día:** ~21 (cada 1.1 horas)
-   **Configuración actual:** 6 ejecuciones/día (cada 4 horas)

### Almacenamiento

-   **Forecasts por ejecución:** ~680 registros
-   **Forecast Hourlies por ejecución:** ~6,048 registros
-   **Total diario:** ~4,080 forecasts + 36,288 hourlies
-   **Limpieza automática:** Datos de más de 7 días

## 🔧 Configuración del Cron

Para que la programación automática funcione, asegúrate de tener configurado el cron job:

```bash
# Editar crontab
crontab -e

# Agregar esta línea
* * * * * cd /path/to/your/app && php artisan schedule:run >> /dev/null 2>&1
```

## 📝 Logs

Los logs se guardan en:

-   `storage/logs/weather-update.log` - Actualizaciones de pronósticos
-   `storage/logs/weather-clean.log` - Limpieza de datos
-   `storage/logs/laravel.log` - Logs generales de Laravel

## 🚨 Notificaciones

En caso de error, se enviará un email a: `rodolfoulises.ramirez@gmail.com`

## 📈 Monitoreo

Para verificar el estado de los comandos:

```bash
# Ver logs de actualización
tail -f storage/logs/weather-update.log

# Ver logs de limpieza
tail -f storage/logs/weather-clean.log

# Verificar registros en base de datos
php artisan tinker
>>> App\Models\Forecast::count()
>>> App\Models\ForecastHourly::count()
```

## 🔄 Comandos Legacy

Por compatibilidad, también está disponible:

-   `forecast:update` - Comando legacy (misma funcionalidad que `weather:update`)
