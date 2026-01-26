# Documentación técnica — Comandos (Artisan) -Parte 1

## Alcance
Este documento describe **10 comandos de Laravel (Artisan)** ubicados en `app/Console/Commands`, con el objetivo de que cualquier desarrollador pueda entender:
- Qué hace cada comando.
- Cómo se ejecuta y con qué parámetros.
- Qué dependencias (modelos/jobs/tablas) utiliza.
- Qué efectos secundarios produce (DB, logs, colas, etc.).
- Riesgos y troubleshooting.

---

## Índice rápido

| # | Comando | Archivo | Tipo | Impacto |
|---:|---|---|---|---|
| 1 | `estaciones:asignar` | `AsignarEstacionesCommand.php` | Operativo | Asigna estaciones a zonas (pivot). |
| 2 | `indicadores:calcular-estres` | `CalcularIndicadoresEstresCommand.php` | Cola (Job) | Encola cálculo de indicadores. |
| 3 | `calcular:unidades-calor` | `CalcularUnidadesCalorCommand.php` | Sync (Job) | Calcula unidades de calor. |
| 4 | `calcular:unidades-frio` | `CalcularUnidadesFrioCommand.php` | Sync (Job) | Calcula unidades de frío. |
| 5 | `diseases:clean` | `CleanDiseaseDataCommand.php` | Mantenimiento (DB) | Elimina registros (con filtros / dry-run). |
| 6 | `forecasts:clean` | `CleanOldForecasts.php` | Mantenimiento (DB) | Borra forecasts y hourlies antiguos (confirmación interactiva). |
| 7 | `weather:clean` | `CleanOldWeatherData.php` | Mantenimiento (DB) | Borra forecasts antiguos y sus hourlies (`--force`). |
| 8 | `calcular:desglose-temperaturas` | `DesgloseTemperaturasCommand.php` | Sync (Job) | Genera resumen/desglose de temperaturas. |
| 9 | `diagnostico:exportacion` | `DiagnosticoExportacion.php` | Diagnóstico | Verifica consulta de exportación (usa DB `aws`). |
| 10 | `diagnostico:zonas-temperatura` | `DiagnosticoZonasTemperaturaCommand.php` | Diagnóstico | Detecta zonas con faltantes (estaciones/forecast/datos). |

> Nota: Algunos comandos usan `dispatchSync()` (ejecución inmediata). Otros usan `dispatch()` (requiere worker de colas).

---

## 1) `estaciones:asignar` — Asignación de estaciones a zonas de manejo

**Archivo:** `app/Console/Commands/AsignarEstacionesCommand.php`  
**Descripción:** Asignar estaciones a zonas de manejo.

### Firma
```bash
php artisan estaciones:asignar {--zona=} {--estacion=} {--auto}
```

### Opciones
- `--auto` (bool): asignación automática.
- `--zona=` (int): ID de zona (manual; requiere `--estacion`).
- `--estacion=` (int): ID de estación (manual; requiere `--zona`).

### Flujo (handle)
1. Si viene `--auto` → ejecuta `asignacionAutomatica()`.
2. Si vienen `--zona` y `--estacion` → ejecuta `asignacionManual()`.
3. Si no → `mostrarOpciones()` (imprime estado y cómo usar el comando).

### Reglas de negocio y validaciones
- **Zonas sin estaciones**: se obtienen con `ZonaManejos::whereDoesntHave('estaciones')`.
- **Estaciones disponibles**: se obtienen con `Estacion::whereDoesntHave('zonaManejos')`.
- Manual:
  - Valida existencia de zona y estación.
  - Valida que la estación **no tenga ninguna zona**: `if ($estacion->zonaManejos()->exists())`.
  - No valida que la zona esté vacía (podría permitir múltiples estaciones por zona si el modelo lo admite).

### Efectos secundarios
- Inserta relación en pivot (many-to-many):
  - `$zona->estaciones()->attach($estacionId)`
- No usa transacción.

### Dependencias
- Modelos: `ZonaManejos`, `Estacion`
- Relaciones esperadas:
  - `ZonaManejos::estaciones()`
  - `Estacion::zonaManejos()`

### Ejemplos
```bash
# Ver estado, zonas sin estaciones y estaciones disponibles
php artisan estaciones:asignar

# Asignación automática (1 estación por zona sin estaciones)
php artisan estaciones:asignar --auto

# Asignación manual
php artisan estaciones:asignar --zona=12 --estacion=5
```

### Troubleshooting
- “No hay estaciones disponibles para asignar.” → todas las estaciones ya tienen una zona (o el método `whereDoesntHave('zonaManejos')` no coincide por relación mal definida).
- “Zona X no encontrada / Estación Y no encontrada.” → IDs incorrectos o distinto entorno/DB.
- Si se ven duplicados en el pivot: revisar si el pivot tiene índice único (recomendado) o cambiar `attach` por `syncWithoutDetaching`.

---

## 2) `indicadores:calcular-estres` — Cálculo de indicadores de estrés (Job en cola)

**Archivo:** `app/Console/Commands/CalcularIndicadoresEstresCommand.php`  
**Descripción:** Calcula indicadores de estrés para las zonas de manejo.

### Firma
```bash
php artisan indicadores:calcular-estres   {--fecha= : Fecha específica (Y-m-d)}   {--dias=2 : Días de pronóstico}   {--force : Forzar ejecución}
```

### Opciones
- `--fecha=` (string, opcional): fecha `Y-m-d`.
- `--dias=` (int, default 2): días de pronóstico a procesar.
- `--force` (bool): **se lee**, pero **no se usa** en el comando (no se pasa al Job).

### Flujo (handle)
1. Lee `fecha`, `dias`, `force`.
2. Imprime contexto.
3. Encola el job:
   - `CalcularIndicadoresEstresJob::dispatch($fecha, $dias);`
4. Retorna `0` si encola correctamente; `1` si hay excepción.

### Dependencias
- Job: `App\Jobs\CalcularIndicadoresEstresJob`
- Requiere configuración de colas y worker:
  - `QUEUE_CONNECTION` y `php artisan queue:work`

### Ejemplos
```bash
# Fecha por defecto (lo decide el Job; el comando imprime "ayer")
php artisan indicadores:calcular-estres

# Fecha específica
php artisan indicadores:calcular-estres --fecha=2026-01-10

# Cambiar días de pronóstico
php artisan indicadores:calcular-estres --dias=5
```

### Troubleshooting
- Si no se ejecuta “nada” después de enviar:
  - verificar `queue:work` y `failed_jobs`.
- `--force` no tiene efecto real actualmente:
  - si es requerido, implementar en el Job y pasar el parámetro.

---

## 3) `calcular:unidades-calor` — Unidades de calor por zona (sync)

**Archivo:** `app/Console/Commands/CalcularUnidadesCalorCommand.php`  
**Descripción:** Calcula las unidades de calor por zona de manejo.

### Firma
```bash
php artisan calcular:unidades-calor {--fecha=}
```

### Opciones
- `--fecha=` (string, opcional): fecha `Y-m-d`.
  - Si no se envía: usa `Carbon::now('America/Mexico_City')->format('Y-m-d')`.

### Flujo
1. Resuelve fecha (opción o “hoy” en `America/Mexico_City`).
2. Ejecuta el job **sin cola** (sincrónico):
   - `CalcularUnidadesCalorJob::dispatchSync($fecha);`

### Dependencias
- Job: `App\Jobs\CalcularUnidadesCalorJob`
- No requiere worker de colas (es `dispatchSync`).

### Ejemplos
```bash
php artisan calcular:unidades-calor
php artisan calcular:unidades-calor --fecha=2026-01-10
```

### Notas técnicas
- `dispatchSync` bloquea el proceso hasta terminar; en servidores con cron/schedule puede impactar tiempos.

---

## 4) `calcular:unidades-frio` — Unidades de frío por hora (sync)

**Archivo:** `app/Console/Commands/CalcularUnidadesFrioCommand.php`  
**Descripción:** Calcula las unidades de frío por hora para cada zona de manejo.

### Firma
```bash
php artisan calcular:unidades-frio {--fecha=}
```

### Opciones
- `--fecha=` (string, opcional): fecha `Y-m-d`.
  - Default: `Carbon::now('America/Mexico_City')->format('Y-m-d')`.

### Flujo
1. Resuelve fecha.
2. Ejecuta job sincrónico:
   - `CalcularUnidadesFrioJob::dispatchSync($fecha);`

### Dependencias
- Job: `App\Jobs\CalcularUnidadesFrioJob`

### Ejemplos
```bash
php artisan calcular:unidades-frio
php artisan calcular:unidades-frio --fecha=2026-01-10
```

---

## 5) `diseases:clean` — Limpieza de tablas de enfermedades (con filtros y dry-run)

**Archivo:** `app/Console/Commands/CleanDiseaseDataCommand.php`  
**Descripción:** Limpia datos de las tablas de enfermedades (para pruebas).

### Firma
```bash
php artisan diseases:clean   {--estacion_id= : ID de estación (opcional)}   {--start_date= : Inicio YYYY-MM-DD (opcional)}   {--end_date= : Fin YYYY-MM-DD (opcional)}   {--enfermedad_id= : ID enfermedad (opcional)}   {--tipo_cultivo_id= : ID tipo cultivo (opcional)}   {--dry-run : Solo mostrar qué se eliminaría}
```

### Tablas afectadas
- `enfermedad_horas_acumuladas_condiciones`
- `enfermedad_horas_condiciones`

### Filtros aplicados (si se proporcionan)
- `estacion_id = ?`
- `fecha >= start_date 00:00:00`
- `fecha <= end_date 23:59:59`
- `enfermedad_id = ?`
- `tipo_cultivo_id = ?`

### Flujo
1. Lee opciones.
2. Si `--dry-run`, solo **cuenta** registros a eliminar.
3. Si no es dry-run:
   - cuenta registros,
   - ejecuta `delete()` contra ambas tablas con los mismos filtros.
4. Si hay error: escribe `Log::error(...)` con trace.

### Efectos secundarios
- Eliminación definitiva (hard delete) sobre DB.
- Log en `storage/logs/laravel.log` si hay error.

### Ejemplos
```bash
# Ver qué se eliminaría (recomendado primero)
php artisan diseases:clean --dry-run

# Eliminar por estación y rango
php artisan diseases:clean --estacion_id=3 --start_date=2026-01-01 --end_date=2026-01-15

# Eliminar por enfermedad y cultivo
php artisan diseases:clean --enfermedad_id=2 --tipo_cultivo_id=5
```

### Riesgos / recomendaciones
- Ejecutar primero con `--dry-run`.
- Si el volumen es grande, considerar eliminación por lotes o con ventana de mantenimiento.

---

## 6) `forecasts:clean` — Limpieza de pronósticos antiguos (Forecast + ForecastHourly)

**Archivo:** `app/Console/Commands/CleanOldForecasts.php`  
**Descripción:** Limpia pronósticos antiguos de la base de datos.

### Firma
```bash
php artisan forecasts:clean {--days=30 : Número de días para mantener}
```

### Comportamiento
- Calcula `cutoffDate = now() - days`.
- Cuenta:
  - `Forecast` donde `fecha_solicita < cutoffDate`
  - `ForecastHourly` donde `fecha < cutoffDate`
- Pide confirmación interactiva: `confirm('¿Deseas continuar?')`.
- Borra en **transacción**:
  1. `ForecastHourly` primero (por FK).
  2. `Forecast` después.
- Muestra progress bar y totales borrados.

### Dependencias
- Modelos: `App\Models\Forecast`, `App\Models\ForecastHourly`
- DB Transaction: `Illuminate\Support\Facades\DB`

### Ejemplos
```bash
# Mantener 30 días (default)
php artisan forecasts:clean

# Mantener 90 días
php artisan forecasts:clean --days=90
```

### Notas para ejecución no interactiva
Este comando **siempre pregunta confirmación** y no tiene `--force`.
- Para automatizar (cron), se requiere entrada por stdin:
  - Ejemplo: `printf "yes
" | php artisan forecasts:clean --days=30`

### Troubleshooting
- Si no elimina hourlies por FK:
  - confirmar que la FK realmente existe y que los criterios de borrado coinciden (`fecha` vs `created_at`, etc.).

---

## 7) `weather:clean` — Limpieza de datos de pronósticos (Forecast + ForecastHourly) con `--force`

**Archivo:** `app/Console/Commands/CleanOldWeatherData.php`  
**Descripción:** Limpia datos antiguos de pronósticos del clima.

### Firma
```bash
php artisan weather:clean {--days=7 : Días a mantener} {--force : Confirmar sin preguntar}
```

### Comportamiento
- `cutoffDate = now() - days`
- Cuenta:
  - `Forecast` donde `fecha_solicita < cutoffDate`
  - `ForecastHourly` donde `created_at < cutoffDate` (**solo para conteo**)
- Si no viene `--force`, pide confirmación interactiva.
- Elimina:
  1. Obtiene IDs de `Forecast` antiguos: `pluck('id')`
  2. Borra `ForecastHourly` **por `forecast_id`** usando esos IDs
  3. Borra `Forecast` antiguos

### Dependencias
- Modelos: `Forecast`, `ForecastHourly`
- Logs: `Illuminate\Support\Facades\Log`

### Ejemplos
```bash
# Mantener 7 días (default)
php artisan weather:clean

# Mantener 30 días con confirmación forzada (ideal para cron)
php artisan weather:clean --days=30 --force
```

### Nota importante (conteo vs borrado)
El conteo de hourlies usa `created_at < cutoffDate`, pero el borrado real se hace por `forecast_id` de forecasts antiguos.
- Por eso, el número “Forecast Hourlies a eliminar” puede **no coincidir** con el total borrado.

### Riesgos / performance
- `pluck('id')` puede cargar muchos IDs en memoria si hay muchos forecasts.
  - Alternativa futura: eliminar con subquery o por chunks.

---

## 8) `calcular:desglose-temperaturas` — Resumen / desglose de temperaturas (sync)

**Archivo:** `app/Console/Commands/DesgloseTemperaturasCommand.php`  
**Descripción:** Calcula el desglose de temperaturas para una fecha específica.

### Firma
```bash
php artisan calcular:desglose-temperaturas {--fecha=}
```

### Opciones
- `--fecha=` (string, opcional): fecha a procesar. Si no viene, el comando imprime “hoy”, pero la lógica real queda en el Job.

### Flujo
1. Toma `--fecha` (puede ser null).
2. Ejecuta job sincrónico:
   - `ResumenTemperaturasJob::dispatchSync($fecha);`

### Dependencias
- Job: `App\Jobs\ResumenTemperaturasJob`

### Ejemplos
```bash
php artisan calcular:desglose-temperaturas
php artisan calcular:desglose-temperaturas --fecha=2026-01-10
```

---

## 9) `diagnostico:exportacion` — Diagnóstico de consulta de exportación (DB aws)

**Archivo:** `app/Console/Commands/DiagnosticoExportacion.php`  
**Descripción:** Diagnostica la consulta de exportación de datos de estación.

### Firma
```bash
php artisan diagnostico:exportacion {zona_manejo_id} {periodo=1} {start_date?} {end_date?}
```

### Argumentos
- `zona_manejo_id` (requerido): zona de manejo a evaluar.
- `periodo` (default `1`): selector de rango y agrupación.
- `start_date` (opcional): se usa principalmente en `periodo=9`.
- `end_date` (opcional): se usa principalmente en `periodo=9`.

### Comportamiento clave
- Fuerza conexión de DB en runtime:
  - `config(['database.default' => 'aws']);`
  - Impacta **todo** lo que se consulte en ese proceso.

### Flujo del diagnóstico
1. Verifica que exista `ZonaManejos::find(zona_manejo_id)`.
2. Verifica estaciones asociadas:
   - `$zona_manejo->estaciones`
   - Si no hay, termina con error.
3. Verifica tipos de cultivo asociados:
   - `$zona_manejo->tipoCultivos` (solo warn si no hay).
4. Calcula periodo (fechas y “grupo”) con `calcularPeriodo()`:
   - Retorna `[desde, hasta, grupo]`.
   - Asegura `desde <= hasta` (si no, intercambia).
5. Cuenta datos en rango:
   - `EstacionDato::whereIn('estacion_id', ...)->whereBetween('created_at', [desde, hasta])->count()`
   - Si no hay datos, muestra min/max de fechas disponibles.
6. Construye consulta agregada de exportación:
   - Selecciona un `selectRaw` que incluye:
     - `fecha` calculada según el “grupo” (día/semana/mes/4h)
     - agregados `MAX/MIN/AVG` de:
       - `temperatura`, `co2`, `temperatura_suelo`, `conductividad_electrica`, `ph`, `nit`, `phos`, `pot`
   - `groupBy('fecha')`
   - Imprime SQL y bindings.
   - Ejecuta y muestra el primer resultado si existe.

### Periodos (calcularPeriodo)
- Base: `desde = now()`, `hasta = now() - X`, luego swap si necesario.
- Casos principales:
  - `1` → últimas 24h, grupo `4_horas`
  - `2` → 48h, grupo `4_horas`
  - `3` → 7 días, grupo `d`
  - `4` → 14 días, grupo `d`
  - `5` → 30 días, grupo `d`
  - `6` → 60 días, grupo `s` (semana)
  - `7` → 180 días, grupo `s`
  - `8` → 365 días, grupo `m` (mes)
  - `9` → usa `start_date/end_date`, grupo `4_horas`
- Casos `10..14` duplican lógica similar a los primeros (mantener ojo en refactor).

### getSelectClause(grupo)
- `d`: `DATE_FORMAT(created_at, "%d-%m-%Y") as fecha`
- `s`: semana ISO `"%V"`
- `m`: `"%m-%Y"`
- `4_horas`: bloques 0-3→4, 4-7→8, …, 20-23→24
- Otros (`8_horas`, `12_horas`, `crudos`) definidos pero no necesariamente usados por `calcularPeriodo()` actual.

### Dependencias
- Modelos: `EstacionDato`, `ZonaManejos`, (importa `TipoCultivos` pero no lo usa directamente)
- Relaciones esperadas:
  - `ZonaManejos::estaciones`
  - `ZonaManejos::tipoCultivos`
- Requiere DB connection `aws` configurada en `config/database.php`.

### Ejemplos
```bash
# Diagnóstico últimas 24h (grupo 4 horas)
php artisan diagnostico:exportacion 12 1

# Diagnóstico 7 días (grupo diario)
php artisan diagnostico:exportacion 12 3

# Rango manual (periodo 9)
php artisan diagnostico:exportacion 12 9 2026-01-01 2026-01-10
```

### Troubleshooting
- “No hay estaciones asociadas” → la zona no tiene relación cargada o pivot vacío.
- “No hay datos en el rango” → revisar timezone, rango, y que `created_at` exista en `estacion_dato`.
- Si falla con SQL:
  - revisar nombres de tabla/alias (`estacion_dato`) y columnas (`temperatura_suelo`, `conductividad_electrica`, etc.).

---

## 10) `diagnostico:zonas-temperatura` — Diagnóstico de zonas problemáticas para ResumenTemperaturasJob

**Archivo:** `app/Console/Commands/DiagnosticoZonasTemperaturaCommand.php`  
**Descripción:** Diagnostica zonas de manejo problemáticas para el Job de `ResumenTemperaturasJob`.

### Firma
```bash
php artisan diagnostico:zonas-temperatura {--fecha=}
```

### Opciones
- `--fecha=` (string, opcional): fecha `Y-m-d`
  - Default: `Carbon::now()->subDay()->format('Y-m-d')` (ayer).

### Validaciones por cada zona
Recorre `ZonaManejos::all()` y verifica:
1. Estaciones asociadas (`$zona->estaciones`):
   - si vacío → “Sin estaciones asociadas”
2. Forecast del día:
   - `Forecast::where('parcela_id', $zona->parcela_id)->where('fecha_prediccion', $fecha)->first()`
   - si no existe → “Sin forecast para la fecha”
3. Datos de estación en el día:
   - `EstacionDato::whereIn('estacion_id', ...)->whereBetween(created_at, [startOfDay, endOfDay])->count()`
   - si 0 → “Sin datos de estación”

Al final imprime resumen:
- Total zonas, OK, sin estaciones, sin forecast, sin datos.

### Dependencias
- Modelos: `ZonaManejos`, `Forecast`, `EstacionDato`
- Campos usados:
  - `ZonaManejos.parcela_id`
  - `Forecast.parcela_id`, `Forecast.fecha_prediccion`
  - `EstacionDato.estacion_id`, `EstacionDato.created_at`

### Ejemplos
```bash
# Diagnóstico de ayer
php artisan diagnostico:zonas-temperatura

# Diagnóstico de una fecha específica
php artisan diagnostico:zonas-temperatura --fecha=2026-01-10
```

### Uso recomendado
- Antes de correr `calcular:desglose-temperaturas` (o si el Job falla), para ubicar zonas con datos incompletos.

---

## Consideraciones generales del lote

### Sync vs Cola
- `dispatchSync()`:
  - `calcular:unidades-calor`
  - `calcular:unidades-frio`
  - `calcular:desglose-temperaturas`
- `dispatch()` (cola):
  - `indicadores:calcular-estres`

### Operaciones destructivas
- `diseases:clean`, `forecasts:clean`, `weather:clean` eliminan datos.
  - Recomendación: ejecutar primero en staging y/o con dry-run (cuando exista).

### Timezone
- Algunos comandos fijan la fecha por defecto con `America/Mexico_City`.
  - Esto debe ser consistente con el resto del sistema (jobs, consultas, forecast).

---
