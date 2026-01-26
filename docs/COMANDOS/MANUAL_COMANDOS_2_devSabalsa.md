# Documentación técnica — Comandos (Artisan) — Parte 2

## Alcance
Este documento describe **10 comandos de Laravel (Artisan)** adicionales, orientados a:
- Sincronización entre bases (pia_dev ⇄ aws).
- Ingesta MQTT (pap/#) y persistencia de mediciones.
- Procesamiento de alertas de enfermedades con optimizaciones de rendimiento.
- Población histórica de acumulados de enfermedades.
- Procesos de temperatura (resumen y máximas).
- Sincronización de viento y precipitación desde OpenWeather.

> **Ubicación esperada**: `app/Console/Commands/*`

---

## Índice rápido

| # | Comando | Archivo | Tipo | Impacto principal |
|---:|---|---|---|---|
| 1 | `fast-sync:enfermedad-horas-acumuladas` | `FastSyncEnfermedadHorasAcumuladasCondiciones.php` | Mantenimiento / Sync | Copia masiva `enfermedad_horas_acumuladas_condiciones` de `pia_dev` a `aws`. |
| 2 | `app:mqtt-debug-command` | `MqttDebugCommand.php` | Debug (stub) | Plantilla vacía (no implementado). |
| 3 | `mqtt:ingest` | `MqttIngestCommand.php` | Daemon / Ingesta | Se suscribe a `pap/#` (configurable) y crea `Measurement` por mensaje. |
| 4 | `diseases:populate-history` | `PopulateDiseaseHistoryCommand.php` | Batch / Backfill | Genera históricos en `enfermedad_horas_acumuladas_condiciones` a partir de `estacion_dato`. |
| 5 | `temperatura:procesar` | `ProcesarTemperaturaCommand.php` | Sync (Job directo) | Ejecuta `ProcesarTemperaturaDia` para un día (ayer por defecto). |
| 6 | `diseases:process-alerts` | `ProcessDiseaseAlertsCommand.php` | Batch / Optimizado | Procesa alertas por enfermedades usando `estacion_dato` (última hora por defecto). |
| 7 | `app:resumen-temperaturas-cronjob` | `ResumenTemperaturasCronjob.php` | Cronjob | Genera `ResumenTemperaturas` por zona (con amanecer/atardecer). |
| 8 | `diseases:send-alerts` | `SendDiseaseAlertsCommand.php` | Sync (Job directo) | Ejecuta `SendDiseaseAlertsJob` (sin colas). |
| 9 | `viento:sincronizar` | `SincronizarDatosVientoCommand.php` | Cola (Job) | Despacha job para sincronizar viento desde OpenWeather. |
| 10 | `precipitacion:sync` | `SincronizarPrecipitacionPluvialCommand.php` | Cola (Job) | Despacha job para sincronizar precipitación desde OpenWeather. |

---

## 1) `fast-sync:enfermedad-horas-acumuladas` — Sync rápido por SQL directo (pia_dev → aws)

**Archivo:** `FastSyncEnfermedadHorasAcumuladasCondiciones.php`  
**Descripción:** Sincronización rápida usando SQL directo y procesamiento en **chunks**.

### Firma
```bash
php artisan fast-sync:enfermedad-horas-acumuladas {--truncate : Limpiar tabla antes de sincronizar}
```

### Opciones
- `--truncate` (bool): ejecuta `TRUNCATE` en la tabla destino (`aws.enfermedad_horas_acumuladas_condiciones`) antes de sincronizar.

### Flujo (handle)
1. Cuenta registros en:
   - `DB::connection('pia_dev')->table('enfermedad_horas_acumuladas_condiciones')`
   - `DB::connection('aws')->table('enfermedad_horas_acumuladas_condiciones')`
2. Si viene `--truncate`, limpia la tabla en AWS.
3. Itera origen en chunks de `5000` por `id`:
   - Construye array de inserción mapeando columnas.
   - Inserta en AWS con `insertOrIgnore()` (ignora duplicados).
4. Recuenta registros en AWS y registra log (`Log::info`).

### Mapeo de columnas (importante)
Por cada registro de `pia_dev.enfermedad_horas_acumuladas_condiciones`:
- `id` → `id`
- `fecha` → `fecha`
- `minutos` → `minutos`
- `especie_id` → **`tipo_cultivo_id`** (ojo: el origen usa `especie_id`)
- `enfermedad_id` → `enfermedad_id`
- `estacion_id` → `estacion_id`
- `created_at/updated_at` → `now()`

### Efectos secundarios
- Inserta registros en AWS (posible gran volumen).
- Si se usa `--truncate`, se pierde el histórico previo en AWS (hard delete).

### Dependencias
- Conexiones de BD: `pia_dev`, `aws` (configuradas en `config/database.php`).
- Tabla: `enfermedad_horas_acumuladas_condiciones`
- Uso de `gc_collect_cycles()` para reducir presión de memoria.

### Ejemplos
```bash
# Sincronizar ignorando duplicados
php artisan fast-sync:enfermedad-horas-acumuladas

# Reemplazo total (peligroso si no hay respaldo)
php artisan fast-sync:enfermedad-horas-acumuladas --truncate
```

### Troubleshooting
- Errores por duplicados: debería tolerarlos por `insertOrIgnore`.
- Si `especie_id` no existe en `pia_dev`: la sincronización fallará (revisar esquema).
- Si el conteo no aumenta, validar:
  - conexiones correctas,
  - si AWS ya tenía esos IDs,
  - o si el `insertOrIgnore` está ignorando por constraints únicos.

---

## 2) `app:mqtt-debug-command` — Stub / plantilla

**Archivo:** `MqttDebugCommand.php`  
**Descripción:** Comando creado como plantilla. Actualmente no implementa lógica en `handle()`.

### Firma
```bash
php artisan app:mqtt-debug-command
```

### Estado actual
- `handle()` vacío (`//`).
- `description` por defecto (“Command description”).

> Recomendación: eliminarlo si no se usa, o completarlo como herramienta real de diagnóstico MQTT.

---

## 3) `mqtt:ingest` — Ingestor MQTT (suscripción + persistencia a DB)

**Archivo:** `MqttIngestCommand.php`  
**Descripción:** Se suscribe a un topic (default `pap/#`) y guarda mediciones en base de datos (modelo `Measurement`).

### Firma
```bash
php artisan mqtt:ingest
```

### Variables de entorno usadas
- `MQTT_HOST` (default `127.0.0.1`)
- `MQTT_PORT` (default `1883`)
- `MQTT_CLIENT_ID` (default: `pia_ingestor_{random}`)
- `MQTT_USERNAME` (opcional)
- `MQTT_PASSWORD` (opcional)
- `MQTT_TOPIC` (default `pap/#`)
- `MQTT_QOS` (default `1`)

### Flujo (handle)
1. Lee configuración de `.env`.
2. Entra en un loop infinito de reconexión:
   - Crea `ConnectionSettings` (keep-alive 60s + Last Will).
   - Conecta `MqttClient`.
   - Publica estado retained:
     - `system/pia_ingestor/status = online` (retain)
     - Last Will: `offline`
3. Se suscribe al topic configurado y procesa cada mensaje:
   - Log/console: imprime topic y contenido.
   - Intenta `json_decode` del payload.
   - Determina IMEI (`estacion_id`) desde:
     - `payload['estacion_id']`, o
     - regex en topic `data/{IMEI}`.
   - Construye DTO con transformaciones y guarda:
     - `Measurement::create($dto)`
4. Ejecuta `client->loop(false, true, 1)` en un loop interno:
   - Si falla el loop, rompe y fuerza reconexión.
   - Si falla la conexión, espera 5s y reintenta.

### Transformaciones relevantes (mapeo)
El DTO guarda:
- Identificación:
  - `imei` (string)
  - `transaction_id` (int|null desde `transaccion_id`)
- Sensores NPK (divisiones):
  - `temp_npk_c` = `temp_npk_lv1 / 10`
  - `hum_npk_pct` = `hum_npk_lv1 / 10`
  - `ph_npk` = `ph_npk_lv1 / 100`
  - `cond_us_cm`, `nit_mg_kg`, `pot_mg_kg`, `phos_mg_kg` (directo)
- Sensores SNS:
  - `temp_sns_c` = `temp_sns_lv1 / 100`
  - `hum_sns_pct` = `hum_sns_lv1 / 100`
  - `co2_ppm` = `co2_sns_lv1 / 100`
- Campos de red/modem/telemetría:
  - `voltaje_mv`, `contador_mnsj`, `tec`, `TON`, `CIT`, `SWV`, `RSRP`, `RSRQ`
  - `ARS`, `CELLID`, `MNC`, `MCC`, `RAT`, `LAC`, `PROJECT` (string)
- `raw_payload` (JSON completo del payload)
- `measured_at_utc` parseado desde `payload['fecha']`

### Parseo de fecha `payload['fecha']` (1NCE)
- Formato esperado: `YY/MM/DD,HH:MM:SS±QQ`
  - `QQ` es offset en **cuartos de hora** (ej: `-24` = -6h)
- Se crea la fecha en UTC y luego se ajusta `addHours(offsetHours)`.

> Nota: El nombre `measured_at_utc` puede ser confuso: el parser aplica offset al timestamp UTC.

### Dependencias
- Librería: `php-mqtt/client` (`PhpMqtt\Client\*`)
- Modelo Eloquent: `App\Models\Measurement`
- Logs: `Illuminate\Support\Facades\Log`

### Ejemplo de ejecución
```bash
php artisan mqtt:ingest
```

### Consideraciones operativas
- Es un proceso **long-running** (daemon). Recomendado:
  - correrlo bajo supervisor/systemd,
  - o usar colas/daemonización controlada.
- El comando imprime salida por cada mensaje; puede ser ruidoso en producción.

---

## 4) `diseases:populate-history` — Backfill histórico de acumulados por enfermedad

**Archivo:** `PopulateDiseaseHistoryCommand.php`  
**Descripción:** Pobla `enfermedad_horas_acumuladas_condiciones` usando históricos de `estacion_dato` y la configuración de riesgo de enfermedades.

### Firma
```bash
php artisan diseases:populate-history   {--estacion_id= : ID de estación (opcional)}   {--start_date= : Inicio YYYY-MM-DD (opcional)}   {--end_date= : Fin YYYY-MM-DD (opcional)}   {--enfermedad_id= : ID enfermedad (opcional)}   {--tipo_cultivo_id= : ID tipo cultivo (opcional)}   {--limit= : Límite de enfermedades (opcional)}   {--dry-run : Solo simular sin escribir}
```

### Opciones y defaults
- `--start_date`: si no viene, default = `now() - 7 días`.
- `--end_date`: si no viene, default = `now()`.
- `--limit`: **se lee**, pero **no se usa** en el procesamiento actual (no aplica un `limit()` real).
- `--dry-run`: no inserta nada, solo muestra lo que haría.

### Fuentes de datos (tablas)
- Configuración enfermedades:
  - `enfermedades e`
  - `tipo_cultivos_enfermedades ee` (join por `enfermedad_id`)
- Series de tiempo:
  - `estacion_dato` (requiere `humedad_relativa` y `temperatura` no-null)
- Destino:
  - `enfermedad_horas_acumuladas_condiciones`

### Flujo (handle)
1. Resuelve filtros y fechas.
2. Obtiene enfermedades a procesar:
   - `obtenerEnfermedades($enfermedadId, $tipoCultivoId)`
3. Obtiene estaciones con datos reales en el periodo:
   - `obtenerEstacionesDesdeEstacionDato(...)`
4. Por cada estación y por cada enfermedad:
   - carga `estacion_dato` en rango (ordenado por `created_at`)
   - recorre registro por registro y acumula “minutos” si cumple condiciones de riesgo
   - al romper condición:
     - inserta el acumulado previo si era > 0
     - inserta un **reinicio** con `minutos = 0` (siempre)
5. Inserta la acumulación final (si quedó pendiente).

### Lógica de acumulación (detalle)
- Considera **cada fila** de `estacion_dato` como **1 minuto**:
  - `acumulacionActual += 1`
- Condiciones de riesgo:
  - humedad dentro `[riesgo_humedad, riesgo_humedad_max]`
  - temperatura dentro `[riesgo_temperatura, riesgo_temperatura_max]`
- Inserción evita duplicados verificando:
  - `fecha`, `tipo_cultivo_id`, `enfermedad_id`, `estacion_id`, **y también `minutos`**.

### Efectos secundarios
- Inserta potencialmente muchos registros en `enfermedad_horas_acumuladas_condiciones`.
- Registra en consola cada inserción/reinicio (muy verboso).

### Ejemplos
```bash
# Simulación con defaults (últimos 7 días)
php artisan diseases:populate-history --dry-run

# Ejecutar realmente para una estación y rango
php artisan diseases:populate-history --estacion_id=65 --start_date=2026-01-01 --end_date=2026-01-07

# Ejecutar para una enfermedad específica
php artisan diseases:populate-history --enfermedad_id=2 --start_date=2026-01-01 --end_date=2026-01-03
```

### Riesgos / performance
- Para rangos grandes puede consumir memoria: hace `->get()` de `estacion_dato` por estación.
- Si `estacion_dato` no está muestreado por minuto, la métrica “minutos” será aproximada.

---

## 5) `temperatura:procesar` — Procesa temperatura máxima de un día (Job directo)

**Archivo:** `ProcesarTemperaturaCommand.php`  
**Descripción:** Ejecuta `ProcesarTemperaturaDia` para un día completo.

### Firma
```bash
php artisan temperatura:procesar {fecha?}
```

### Argumentos
- `fecha` (opcional): cualquier string parseable por `Carbon::parse()`. Recomendado `YYYY-MM-DD`.

### Flujo
1. Si se pasa fecha:
   - valida `Carbon::parse($fecha)`; si falla, retorna error.
2. Si no se pasa:
   - usa `Carbon::now('America/Mexico_City')->subDay()` (**ayer**).
3. Instancia el Job y ejecuta `handle()` directamente (sin cola):
   - `$job = new ProcesarTemperaturaDia($fechaCarbon); $job->handle();`

### Dependencias
- Job: `App\Jobs\ProcesarTemperaturaDia`
- Carbon.

### Ejemplos
```bash
# Ayer (MX)
php artisan temperatura:procesar

# Fecha específica
php artisan temperatura:procesar 2026-01-10
```

---

## 6) `diseases:process-alerts` — Procesamiento de alertas por enfermedades (optimizado)

**Archivo:** `ProcessDiseaseAlertsCommand.php`  
**Descripción:** Procesa alertas de enfermedades usando `estacion_dato` y actualiza:
- `enfermedad_horas_condiciones` (contador en curso)
- `enfermedad_horas_acumuladas_condiciones` (histórico al reiniciar)

Incluye optimizaciones (precarga y cache) para reducir consultas repetidas.

### Firma
```bash
php artisan diseases:process-alerts   {--start_date= : Inicio (YYYY-MM-DD HH:mm:ss)}   {--end_date= : Fin (YYYY-MM-DD HH:mm:ss)}   {--estaciones= : IDs separados por coma (ej: 65,66)}   {--all-estaciones : Procesar todas las estaciones activas}   {--dry-run : Simular sin escribir}
```

### Defaults
- Si no se pasan `start_date/end_date`:
  - procesa **última hora** (`now()-1h` a `now()`).
- Si no se pasan estaciones:
  - default = `[65, 66]` (comportamiento original).
- `--all-estaciones`:
  - toma IDs desde `estaciones` donde `status = 1`.

### Flujo (handle)
1. Resuelve rango de tiempo.
2. Determina estaciones a procesar (`determinarEstaciones()`).
3. Precarga una vez:
   - Enfermedades: `SELECT ee.* FROM ... tipo_cultivos_enfermedades ee ...`
4. Precarga cache de `enfermedad_horas_condiciones` para esas estaciones:
   - Key compuesta: `{estacion_id}_{tipo_cultivo_id}_{enfermedad_id}`
5. Lee `estacion_dato` en el rango y estaciones seleccionadas.
6. Agrupa por estación y procesa secuencialmente:
   - construye `$data` similar al `StationController`
   - llama `processDiseaseAlertsOptimizado(...)` por cada punto.

### Lógica optimizada (puntos clave)
- **Inserciones en lote**:
  - Nuevos registros en `enfermedad_horas_condiciones` se acumulan en array y se insertan al final de la estación.
  - Acumulados en `enfermedad_horas_acumuladas_condiciones` también por lote.
- **Updates**:
  - Se acumulan como queries parametrizados y se ejecutan al final (uno por uno).
- **Cálculo de “minutos transcurridos”**:
  - Si `minutos == 0`: suma 1
  - Si no: diferencia entre `created_at` actual y `fecha_ultima_transmision` anterior en minutos.

### Diferencia con `PopulateDiseaseHistoryCommand`
- Aquí **NO** asume 1 registro = 1 minuto (usa diferencia real por timestamps).
- En acumuladas **no registra** minutos 0 (solo si > 0).

### Dependencias
- Tablas:
  - `estacion_dato`
  - `enfermedades`
  - `tipo_cultivos_enfermedades`
  - `enfermedad_horas_condiciones`
  - `enfermedad_horas_acumuladas_condiciones`
  - `estaciones` (para `--all-estaciones`)
- Logs: `Log::error(...)` en fallas de bulk insert/update.

### Ejemplos
```bash
# Última hora, estaciones default 65/66
php artisan diseases:process-alerts

# Rango manual para estaciones específicas
php artisan diseases:process-alerts --start_date="2026-01-10 00:00:00" --end_date="2026-01-10 01:00:00" --estaciones=65,66

# Procesar todas las estaciones activas (requiere worker si se combina con otras colas, pero este comando es sync)
php artisan diseases:process-alerts --all-estaciones

# Simular
php artisan diseases:process-alerts --dry-run
```

### Notas
- El comando incluye un método `processDiseaseAlerts()` (legacy) para compatibilidad, pero el flujo principal usa la versión optimizada.

---

## 7) `app:resumen-temperaturas-cronjob` — Resumen de temperaturas por zona

**Archivo:** `ResumenTemperaturasCronjob.php`  
**Descripción:** Genera `ResumenTemperaturas` por zona de manejo, calculando:
- max/min/amplitud **nocturna**
- max/min/amplitud **diurna**
- max/min/amplitud **día completo**
- `uc` (unidades de calor) usando `temp_base_calor`

### Firma
```bash
php artisan app:resumen-temperaturas-cronjob {--fecha= : Fecha específica (YYYY-MM-DD)}
```

### Fecha por defecto (observación)
- Comentario indica “ayer por defecto”, pero el código usa:
  - `Carbon::now('America/Mexico_City')->format('Y-m-d')` (**hoy**).
- Si se pasa `--fecha`, se parsea y se formatea `Y-m-d`.

### Dependencias (modelos)
- `ZonaManejos`
- `EstacionDato`
- `Forecast`
- `ResumenTemperaturas`
- (Importa `ResumenTemperaturasJob`, pero no se usa)

### Flujo (handle)
1. Obtiene todas las zonas: `ZonaManejos::all()`.
2. Por cada zona:
   - obtiene desglose con `desgloseTemperaturas($zona, $fecha)`
   - valida que `dia.max` y `dia.min` existan y sean != 0 (si no, skip con warning).
   - obtiene `tempBaseCalor`:
     - prioridad: `$zona->temp_base_calor`
     - fallback: `$zona->tipoCultivos->first()->cultivo->temp_base_calor`
     - default final: `10`
   - calcula `uc = ((max + min) / 2) - tempBaseCalor`
   - `ResumenTemperaturas::updateOrCreate(...)` para persistir métricas.

### Cálculo de desglose (desgloseTemperaturas)
1. Obtiene horas día/noche vía `Forecast`:
   - `where parcela_id = zona.parcela_id`
   - `where fecha_prediccion = $fecha`
   - `where fecha_solicita = $fecha`
   - lee `sunriseTime` y `sunsetTime`
2. Obtiene estaciones de la zona:
   - `$zona->estaciones->pluck('id')`
3. Consultas agregadas:
- Nocturnas:
  - desde startOfDay a sunrise, y desde después de sunset a endOfDay
- Diurnas:
  - entre sunrise y sunset
- Día completo:
  - entre startOfDay y endOfDay

Todas calculan:
- `MAX(temperatura) as max`
- `MIN(temperatura) as min`
- `MAX - MIN as amplitud`

### Ejemplos
```bash
# Hoy (por defecto según código)
php artisan app:resumen-temperaturas-cronjob

# Fecha específica
php artisan app:resumen-temperaturas-cronjob --fecha=2026-01-10
```

### Riesgos / notas
- Si temperaturas válidas pueden ser 0°C, el filtro `== 0` podría descartar datos reales.
- Requiere que exista `Forecast` del día para obtener sunrise/sunset; si no existe, la zona queda sin procesar.

---

## 8) `diseases:send-alerts` — Envío de alertas por email (Job directo)

**Archivo:** `SendDiseaseAlertsCommand.php`  
**Descripción:** Ejecuta `SendDiseaseAlertsJob` directamente llamando `handle()`.

### Firma
```bash
php artisan diseases:send-alerts {--dry-run : Ejecutar en modo prueba sin enviar emails}
```

### Nota importante
- El comando declara `--dry-run`, pero **no lo pasa ni lo usa**.
- Si se requiere dry-run real, debe implementarse dentro del Job o ajustarse el comando.

### Flujo
1. Instancia Job:
   - `$job = new SendDiseaseAlertsJob();`
2. Ejecuta:
   - `$job->handle();`

### Dependencias
- Job: `App\Jobs\SendDiseaseAlertsJob`

### Ejemplos
```bash
php artisan diseases:send-alerts
php artisan diseases:send-alerts --dry-run   # actualmente no cambia el comportamiento (según el comando)
```

---

## 9) `viento:sincronizar` — Sincronización de viento (OpenWeather → DB)

**Archivo:** `SincronizarDatosVientoCommand.php`  
**Descripción:** Despacha el Job `SincronizarDatosViento` para sincronizar datos de viento desde OpenWeather.

### Firma
```bash
php artisan viento:sincronizar
```

### Flujo
1. `SincronizarDatosViento::dispatch();`
2. Requiere worker de colas para ejecutarse.

### Dependencias
- Job: `App\Jobs\SincronizarDatosViento`
- Configuración OpenWeather (probablemente en el Job: API key, endpoints, etc.)

### Ejemplo
```bash
php artisan viento:sincronizar
# en otra terminal / servicio:
php artisan queue:work
```

---

## 10) `precipitacion:sync` — Sincronización de precipitación pluvial (OpenWeather → DB)

**Archivo:** `SincronizarPrecipitacionPluvialCommand.php`  
**Descripción:** Despacha el Job `SincronizarPrecipitacionPluvial` para sincronizar datos de precipitación pluvial.

### Firma
```bash
php artisan precipitacion:sync {--parcela-id= : ID específico de parcela}
```

### Nota importante
- Declara opción `--parcela-id`, pero el comando **no la utiliza** (si se requiere, debe pasarse al Job).

### Flujo
1. `SincronizarPrecipitacionPluvial::dispatch();`
2. Requiere worker de colas.

### Ejemplo
```bash
php artisan precipitacion:sync
php artisan precipitacion:sync --parcela-id=123   # actualmente no cambia el comportamiento (según el comando)
# en otra terminal / servicio:
php artisan queue:work
```

---

## Consideraciones generales del lote

### Sync vs Cola
- **Ejecución directa / sync** (no requiere `queue:work`):
  - `fast-sync:enfermedad-horas-acumuladas`
  - `mqtt:ingest` (daemon; no cola)
  - `diseases:populate-history`
  - `temperatura:procesar`
  - `diseases:process-alerts`
  - `app:resumen-temperaturas-cronjob`
  - `diseases:send-alerts`
- **Despacha a cola** (requiere worker):
  - `viento:sincronizar`
  - `precipitacion:sync`

### Verbosidad / Logs
Varios comandos imprimen mucha salida por consola (especialmente `mqtt:ingest`, `populate-history`, `process-alerts`).  
Para producción, se recomienda:
- redirigir salida a logs rotados,
- o reducir verbosity en los comandos.

---
