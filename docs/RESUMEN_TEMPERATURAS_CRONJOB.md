# Comando ResumenTemperaturasCronjob

## Descripción

Este comando genera un resumen diario de temperaturas para todas las zonas de manejo del sistema. Calcula temperaturas máximas, mínimas y amplitudes tanto para períodos diurnos como nocturnos, además de las unidades de calor acumuladas.

## Funcionalidades

-   **Procesamiento por fecha**: Puede procesar una fecha específica o usar la fecha de ayer por defecto
-   **Cálculos automáticos**:
    -   Temperaturas nocturnas (antes del amanecer y después del atardecer)
    -   Temperaturas diurnas (entre amanecer y atardecer)
    -   Temperaturas del día completo
    -   Unidades de calor basadas en temperatura base del cultivo
-   **Manejo de errores**: Captura y reporta errores individuales sin detener el proceso completo
-   **Barra de progreso**: Muestra el avance del procesamiento
-   **Logging**: Registra toda la actividad en archivos de log

## Uso

### Ejecución manual

```bash
# Procesar fecha de ayer (por defecto)
php artisan app:resumen-temperaturas-cronjob

# Procesar fecha específica
php artisan app:resumen-temperaturas-cronjob --fecha=2024-01-15

# Ver ayuda
php artisan app:resumen-temperaturas-cronjob --help
```

### Programación automática

El comando está programado para ejecutarse automáticamente todos los días a las 3:00 AM en el archivo `app/Console/Kernel.php`:

```php
$schedule->command('app:resumen-temperaturas-cronjob')->dailyAt('03:00')
    ->sendOutputTo(storage_path('logs/resumen-temperaturas.log'))
    ->emailOutputOnFailure('rodolfoulises.ramirez@gmail.com');
```

## Estructura de datos

### Entrada

-   **ZonaManejos**: Zonas de manejo con sus estaciones asociadas
-   **EstacionDato**: Datos de temperatura de las estaciones
-   **Forecast**: Información de amanecer/atardecer para cada parcela
-   **TipoCultivos**: Tipos de cultivo con sus temperaturas base

### Salida

-   **ResumenTemperaturas**: Tabla con los siguientes campos:
    -   `fecha`: Fecha del resumen
    -   `zona_manejo_id`: ID de la zona de manejo
    -   `max_nocturna`, `min_nocturna`, `amp_nocturna`: Temperaturas nocturnas
    -   `max_diurna`, `min_diurna`, `amp_diurna`: Temperaturas diurnas
    -   `max`, `min`, `amp`: Temperaturas del día completo
    -   `uc`: Unidades de calor calculadas

## Cálculos realizados

### Temperaturas nocturnas

Se consideran los períodos:

-   Desde las 00:00 hasta el amanecer
-   Desde el atardecer hasta las 23:59

### Temperaturas diurnas

Se considera el período entre amanecer y atardecer.

### Unidades de calor

```
UC = ((Tmax + Tmin) / 2) - Tbase
```

Donde:

-   `Tmax`: Temperatura máxima del día
-   `Tmin`: Temperatura mínima del día
-   `Tbase`: Temperatura base del cultivo (desde zona_manejo o cultivo)

## Logs y monitoreo

-   **Log de salida**: `storage/logs/resumen-temperaturas.log`
-   **Notificaciones**: Se envía email en caso de fallo
-   **Progreso**: Barra de progreso en tiempo real durante la ejecución

## Dependencias

-   Laravel Eloquent ORM
-   Carbon para manejo de fechas
-   Relaciones entre modelos:
    -   ZonaManejos ↔ Estaciones (many-to-many)
    -   ZonaManejos ↔ TipoCultivos (many-to-many)
    -   TipoCultivos ↔ Cultivo (belongs-to)

## 📋 Dependencias de Tablas

El comando depende de las siguientes tablas para funcionar correctamente:

### 🎯 **Tablas Principales (Críticas)**

1. **`zona_manejos`** - Zonas de manejo a procesar
2. **`estacion_dato`** - Datos de temperatura de las estaciones
3. **`forecast`** - Horarios de amanecer/atardecer por parcela
4. **`resumen_temperaturas`** - Tabla de salida (resultados)

### 🔗 **Tablas de Relación (Necesarias)**

5. **`zona_manejos_estaciones`** - Relación zonas ↔ estaciones
6. **`zona_manejos_tipo_cultivos`** - Relación zonas ↔ cultivos
7. **`estaciones`** - Información de estaciones meteorológicas
8. **`tipo_cultivos`** - Tipos de cultivo
9. **`cultivos`** - Cultivos base (contiene temp_base_calor)
10. **`parcelas`** - Información de parcelas agrícolas

### 📊 **Verificación de Dependencias**

Para verificar que todas las dependencias estén correctas:

```bash
# Verificar todas las dependencias
php artisan app:verificar-dependencias-temperatura --fecha=2025-06-29

# Verificar estado específico de zonas
php artisan app:verificar-datos-temperatura --fecha=2025-06-29
```

### ⚠️ **Requisitos Mínimos**

Para que el comando funcione correctamente, se necesitan:

-   **Al menos 1 zona de manejo** con estaciones asociadas
-   **Datos de temperatura** en `estacion_dato` para la fecha
-   **Pronósticos de clima** en `forecast` para la fecha
-   **Relaciones configuradas** entre zonas, estaciones y cultivos

## Consideraciones

-   El comando maneja zonas de manejo sin estaciones asociadas (las omite)
-   Usa `updateOrCreate` para evitar duplicados
-   Temperatura base por defecto: 10°C si no está configurada
-   Maneja errores individuales sin detener el proceso completo
-   **Valores por defecto**: Cuando no hay datos de temperatura, se usan valores 0 en lugar de NULL para cumplir con las restricciones de la base de datos
-   **Validación mejorada**: Verifica que existan datos válidos (max y min) antes de procesar cada zona de manejo
-   **Logging detallado**: Muestra qué zonas de manejo no tienen datos válidos para facilitar el debugging

## Comando de Verificación

Para ayudar con el debugging, se incluye un comando adicional que verifica el estado de los datos:

```bash
# Verificar todas las zonas de manejo
php artisan app:verificar-datos-temperatura --fecha=2025-06-29

# Verificar una zona específica
php artisan app:verificar-datos-temperatura --fecha=2025-06-29 --zona=69
```

Este comando muestra una tabla con:

-   **ID**: ID de la zona de manejo
-   **Nombre**: Nombre de la zona
-   **Parcela**: Nombre de la parcela asociada
-   **Estaciones**: Número de estaciones asociadas
-   **Datos Temp**: Cantidad de registros de temperatura para la fecha
-   **Forecast**: Cantidad de registros de forecast para la fecha
-   **Estado**: Estado general (OK, Sin estaciones, Sin datos temp, Sin forecast)

## Solución de problemas

### ✅ Error: "Column cannot be null" - RESUELTO

-   **Causa**: La base de datos no permite valores NULL en los campos de temperatura
-   **Solución**: El comando ahora usa valores 0 por defecto cuando no hay datos
-   **Estado**: Completamente resuelto - no más errores de integridad

### "Sin datos válidos" para muchas zonas

-   **Causa**: Las zonas no tienen datos de temperatura para la fecha especificada
-   **Solución**: Usar el comando de verificación para identificar el problema específico
-   **Verificación**: Ejecutar `php artisan app:verificar-datos-temperatura --fecha=YYYY-MM-DD`

### Zonas sin estaciones asociadas

-   **Causa**: Las zonas de manejo no tienen estaciones vinculadas
-   **Solución**: Verificar las relaciones en la tabla `zona_manejos_estaciones`
