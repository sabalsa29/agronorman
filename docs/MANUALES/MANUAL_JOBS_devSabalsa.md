# Jobs (Laravel)

Este documento describe los **Jobs** ubicados en `app/Jobs`.

- **Qué hacen** (propósito funcional)
- **Qué reciben** (parámetros)
- **Qué consultan/actualizan** (modelos/tablas)
- **Cómo ejecutarlos** (cola / comandos / dispatch)
- **Consideraciones** (idempotencia, rendimiento, configuración)

---

## ¿Qué es un Job en Laravel?

Un **Job** es una clase que representa una **tarea** (normalmente pesada o repetitiva) que se puede ejecutar:

- **En segundo plano** usando el sistema de colas (`implements ShouldQueue`), para no bloquear una solicitud web.
- **De forma síncrona** (en el mismo proceso) usando `dispatchSync()` o instanciando el job y llamando a `handle()`.

En producción, cuando se usa cola, se requiere:

- Un *queue driver* configurado (por ejemplo: `database`, `redis`, etc.).
- Un *worker* corriendo (`php artisan queue:work`) o un supervisor.

---

## Convenciones observadas en este proyecto

- Zona horaria: varias rutinas trabajan explícitamente con `America/Mexico_City`.
- En la mayoría de cálculos se busca **idempotencia** usando `updateOrCreate()` (mismo `fecha + zona_manejo_id` → se actualiza).
- Los procesos de **clima externo** usan OpenWeather mediante `Http::get(...)` y toman su API key desde `config('services.openweathermap.key')`.
- Las alertas por enfermedad se basan en:
  - Tabla `enfermedad_horas_acumuladas_condiciones`
  - Configuración en `tipo_cultivos_enfermedades`
  - Envío de correo a `config('services.disease_alert_email')`

---

## Resumen de Jobs

| Job | Propósito | Entradas | Salidas / Persistencia | Ejecución típica |
|---|---|---|---|---|
| `CalcularIndicadoresEstresJob` | Calcula indicadores de estrés por zona/cultivo (diurno/nocturno) | `fecha` opcional, `diasPronostico` | `indicador_calculados` (via `IndicadorCalculado`) | Desde `CalcularIndicadoresEstresCommand` (queued) |
| `CalcularUnidadesCalorJob` | Calcula unidades de calor por zona para un día | `fecha` (Y-m-d) | `unidades_calor_zonas` (`UnidadesCalorZona`) | `CalcularUnidadesCalorCommand` (sync) |
| `CalcularUnidadesFrioJob` | Calcula unidades de frío por hora y zona | `fecha` (Y-m-d) | `unidades_frios` (`UnidadesFrio`) | `CalcularUnidadesFrioCommand` (sync) |
| `ProcesarTemperaturaDia` | Estadística diaria por zona y UC por plaga | `fecha` (por defecto ayer) | `unidades_calor_plagas` (`UnidadesCalorPlaga`) | `ProcesarTemperaturaCommand` (directo) |
| `ProcessDiseaseAlertsJob` | Acumula minutos consecutivos con condiciones de riesgo | `fechaInicio`, `fechaFin` (opcionales) | `enfermedad_horas_acumuladas_condiciones` | Puede ejecutarse en cola (clase Job); hay un Command alterno optimizado |
| `ResumenTemperaturasJob` | Resumen térmico (nocturno/diurno/día) y UC/UF por zona | `fecha` opcional | `resumen_temperaturas` (`ResumenTemperaturas`) | Job preparado para cola; existe command cron alterno |
| `SendDiseaseAlertJob` | Envía un correo de alerta individual | datos de alerta | envío de mail (`DiseaseAlertMail`) | Trigger desde lógica de alertas |
| `SendDiseaseAlertsJob` | Evalúa acumulaciones recientes y envía correos (alto riesgo) | sin entradas | envío de mail (`emails.high_risk_disease_alert`) | `SendDiseaseAlertsCommand` ejecuta `handle()` directo |
| `SincronizarDatosViento` | Sincroniza viento actual y pronóstico desde OpenWeather | sin entradas | `datos_vientos` (`DatosViento`) + tabla de errores | Desde `SincronizarDatosVientoCommand` (queued) |
| `SincronizarPrecipitacionPluvial` | Sincroniza lluvia actual y pronóstico desde OpenWeather | sin entradas | `precipitacion_pluvials` (`PrecipitacionPluvial`) + tabla de errores | Desde `SincronizarPrecipitacionPluvialCommand` (queued) |
| `SincronizarPresionAtmosferica` | Sincroniza **presión atmosférica** actual + pronóstico (3 días) | OpenWeather (`/weather`, `/forecast`) | `presion_atmosferica` + registro de errores en `parcela_error_presion` | `php artisan presion:sync` (dispatch) |
| `UpdateWeatherForecastJob` | Actualiza pronóstico meteorológico (por parcela) vía controlador | `Api\ForeCastController::guardaPronostico()` | Depende del controlador (forecast / hourly, etc.) | Scheduler / dispatch manual |

---

## 1) CalcularIndicadoresEstresJob

**Archivo:** `app/Jobs/CalcularIndicadoresEstresJob.php`

### Propósito
Calcula **indicadores de estrés** por:

- Zona de manejo (estación virtual)
- Tipo de cultivo asociado a la zona
- Variable (ej. `temperatura`, `humedad_relativa`, etc.)
- Momento del día: **DIURNO** y **NOCTURNO**

El resultado se guarda como porcentajes y horas por “escala” (1 a 5).

### Entradas (constructor)
- `fecha` *(opcional)*: fecha a procesar.
- `diasPronostico` *(default: 2)*: existe el parámetro, pero la generación de fechas futuras está **comentada** actualmente; se procesa **solo una fecha**.

### Flujo principal (handle)
1. Define timezone `America/Mexico_City`.
2. Determina la fecha a procesar:
   - Por defecto: **ayer**.
3. Carga parámetros desde `TipoCultivoEstres` y construye escalas por variable/tipo de cultivo.
4. Obtiene todas las zonas `ZonaManejos::with(['estaciones','tipoCultivos'])->get()`.
5. Por cada zona:
   - Valida que tenga estaciones.
   - Obtiene `sunriseTime` y `sunsetTime` desde `Forecast`.
   - Para cada variable y tipo de cultivo aplicable:
     - Calcula escalas **diurnas** y **nocturnas**.
     - Para diurno/nocturno usa:
       - **Datos reales:** `EstacionDato`.
       - **Pronóstico:** `ForecastHourly`.
6. Guarda con `IndicadorCalculado::updateOrCreate(...)`.

### Persistencia y tablas/modelos
- Lectura:
  - `zona_manejos` (`ZonaManejos`) + relaciones `estaciones`, `tipoCultivos`
  - `forecast` (`Forecast`) para amanecer/atardecer
  - `estacion_dato` (`EstacionDato`) para mediciones reales
  - `forecast_hourly` (`ForecastHourly`) para pronóstico horario
  - `tipo_cultivo_estres` (`TipoCultivoEstres`), `indicadores` (`Indicador`)
- Escritura:
  - `indicador_calculados` (`IndicadorCalculado`)

### Consideraciones
- **Fallback de “registro de prueba”:** si no hay porcentajes/horas, se fuerzan valores de prueba antes de guardar.
- **Pronóstico limitado:** cuando `calcularPronostico` está activo, solo procesa variables `temperatura` y `humedad_relativa`.
- Hay logs específicos para `zona_manejo_id == 62`.

### Cómo ejecutarlo
- Desde comando (queued):
  - `CalcularIndicadoresEstresCommand` despacha el job (`dispatch($fecha, $dias)`).

---

## 2) CalcularUnidadesCalorJob

**Archivo:** `app/Jobs/CalcularUnidadesCalorJob.php`

### Propósito
Calcula **unidades de calor** por zona de manejo para una fecha:

\[ UC = ((Tmax + Tmin) / 2) - TempBaseCalor \]

Donde `TempBaseCalor` se toma del **primer** tipo de cultivo asociado a la zona.

### Entradas
- `fecha` *(opcional)*: si no se proporciona, usa `hoy` en `America/Mexico_City` como `Y-m-d`.

### Flujo
1. Filtra zonas que tienen estaciones con datos de temperatura en la fecha.
2. Para cada zona:
   - Obtiene `MAX(temperatura)` y `MIN(temperatura)` del día.
   - Obtiene `temp_base_calor` del primer `tipoCultivo` asociado.
   - Calcula UC.
   - Guarda en `UnidadesCalorZona::updateOrCreate([fecha, zona], [unidades])`.

### Persistencia
- Lectura: `estacion_dato`, `zona_manejos`, relación `tipoCultivos`.
- Escritura: `unidades_calor_zonas` (`UnidadesCalorZona`).

### Cómo ejecutarlo
- Comando ejecuta en modo **síncrono**:
  - `CalcularUnidadesCalorCommand` usa `CalcularUnidadesCalorJob::dispatchSync($fecha)`.

---

## 3) CalcularUnidadesFrioJob

**Archivo:** `app/Jobs/CalcularUnidadesFrioJob.php`

### Propósito
Calcula **unidades de frío** por zona y por **hora** (solo en horas con datos).

### Entradas
- `fecha` *(opcional)*: si no se proporciona, usa `hoy` en `America/Mexico_City` como `Y-m-d`.

### Flujo
1. Filtra zonas con estaciones que tengan datos ese día.
2. Por zona:
   - Detecta horas con datos (`HOUR(created_at)` distinct).
   - Por cada hora con datos:
     - Calcula promedio de temperatura.
     - Aplica la escala:
       - `<= 1.4` → `0`
       - `(1.4, 2.4]` → `0.5`
       - `(2.4, 9.1]` → `1`
       - `(9.1, 12.4]` → `0.5`
       - `(12.4, 15.9]` → `0`
       - `(15.9, 18]` → `-0.5`
       - `> 18` → `-1`
     - Guarda con `UnidadesFrio::updateOrCreate([fechaHora, zona], [unidades])`.

### Persistencia
- Lectura: `estacion_dato`, `zona_manejos`.
- Escritura: `unidades_frios` (`UnidadesFrio`).

### Cómo ejecutarlo
- Comando en modo **síncrono**:
  - `CalcularUnidadesFrioCommand` usa `CalcularUnidadesFrioJob::dispatchSync($fecha)`.

---

## 4) ProcesarTemperaturaDia

**Archivo:** `app/Jobs/ProcesarTemperaturaDia.php`

### Propósito
Procesa un día completo de `estacion_dato` para:

- Mostrar/registrar estadísticas (max/min/promedio)
- Calcular **unidades de calor por plaga** y por zona

### Entradas
- `fecha` *(opcional)*:
  - Si no se proporciona, usa **ayer**.
  - El job normaliza a rango `startOfDay()` → `endOfDay()`.

### Flujo
1. Obtiene todos los registros del día: `EstacionDato::whereBetween(...)->get()`.
2. Obtiene todas las plagas: `Plaga::all()`.
3. Para cada zona (`ZonaManejos::with('estaciones')->get()`):
   - Filtra los datos del día por estaciones de la zona.
   - Calcula max/min/promedio.
   - Por cada plaga:
     - `tMax = max(tempMaxZona, plaga.umbral_max)`
     - `tMin = tempMinZona`
     - `tBase = plaga.umbral_min`
     - `uc = max(0, ((tMax + tMin)/2 - tBase))`
     - Actualiza o crea registro en `UnidadesCalorPlaga` por `zona + plaga + fecha`.

### Persistencia
- Lectura: `estacion_dato`, `plagas`, `zonas_manejos`.
- Escritura: `unidades_calor_plagas` (`UnidadesCalorPlaga`).

### Consideraciones
- **Memoria/rendimiento:** carga *todos* los `EstacionDato` del día en memoria (posible impacto si hay mucho volumen).
- El comando asociado ejecuta `handle()` directamente, por lo que **no** depende de un worker.

### Cómo ejecutarlo
- `ProcesarTemperaturaCommand` instancia y ejecuta:
  - `new ProcesarTemperaturaDia($fecha)->handle()`

---

## 5) ProcessDiseaseAlertsJob

**Archivo:** `app/Jobs/ProcessDiseaseAlertsJob.php`

### Propósito
Procesa registros recientes de `estacion_dato` para detectar tramos consecutivos donde se cumplan condiciones de riesgo para enfermedades, y guarda acumulaciones en:

- `enfermedad_horas_acumuladas_condiciones` (en realidad guarda **minutos**).

### Entradas
- `fechaInicio` *(opcional)*
- `fechaFin` *(opcional)*

Si no se proporcionan, el job usa por defecto **última hora**.

### Flujo
1. Determina rango de fechas.
2. Consulta `estacion_dato` en rango y agrupa por estación.
3. Obtiene enfermedades configuradas por `tipo_cultivos_enfermedades`.
4. Por cada estación y enfermedad:
   - Lee umbrales (humedad/temperatura min/max) con defaults.
   - Recorre cada dato y evalúa condiciones.
   - Acumula `+1` minuto por registro consecutivo que cumpla.
   - Cuando se rompe la racha, inserta un registro en `enfermedad_horas_acumuladas_condiciones`.

### Persistencia
- Lectura: `estacion_dato`, `tipo_cultivos_enfermedades` (+ join con `enfermedades`).
- Escritura: `enfermedad_horas_acumuladas_condiciones`.

### Consideraciones
- La acumulación suma **1 minuto por registro**, asumiendo que los registros llegan a frecuencia ~1/min.
- En el proyecto existe `ProcessDiseaseAlertsCommand` con una versión **optimizada** (cachés + batch inserts) que no utiliza este job directamente.

---

## 6) ResumenTemperaturasJob

**Archivo:** `app/Jobs/ResumenTemperaturasJob.php`

### Propósito
Genera un resumen de temperaturas por zona y fecha:

- Máxima/mínima/amplitud:
  - Nocturna (antes de amanecer y después de atardecer)
  - Diurna (entre amanecer y atardecer)
  - Día completo
- Calcula:
  - `uc = ((max_dia + min_dia)/2) - temp_base_calor`
  - `uf = max(0, temp_base_calor - min_dia)`

### Entradas
- `fecha` *(opcional)*: si no se proporciona, usa **ayer** (timezone México).

### Configuración de cola
- `public $timeout = 600` (10 min)
- `public $tries = 3`
- Implementa `failed(Throwable $e)` para logging.

### Flujo
1. Determina fecha.
2. Obtiene zonas que tienen estaciones (`whereHas('estaciones')`).
3. Para cada zona:
   - Obtiene amanecer/atardecer desde `Forecast` (último `fecha_solicita`).
   - Consulta `EstacionDato` en los rangos nocturno/diurno/día.
   - Valida datos (`tieneDatosValidos`).
   - Guarda en `ResumenTemperaturas::updateOrCreate([fecha,zona], [...campos])`.

### Persistencia
- Lectura: `forecast`, `estacion_dato`, `zona_manejos`.
- Escritura: `resumen_temperaturas` (`ResumenTemperaturas`).

### Consideraciones
- La validación descarta casos donde `max` o `min` sea `0` (si existiera un 0°C real, podría filtrarse).
- Existe un comando alterno `ResumenTemperaturasCronjob` con lógica similar ejecutada desde Artisan.

---

## 7) SendDiseaseAlertJob

**Archivo:** `app/Jobs/SendDiseaseAlertJob.php`

### Propósito
Envía una **alerta individual** por correo usando `DiseaseAlertMail`.

### Entradas
Recibe todos los datos necesarios del evento (IDs y valores):

- `enfermedadId`, `tipoCultivoId`, `zonaManejoId`
- `horasAcumuladas`, `umbralMedio`, `umbralMaximo`
- `enfermedadNombre`

### Flujo
1. Obtiene destinatario desde `config('services.disease_alert_email')`.
2. Si no está configurado, registra warning y termina.
3. Envía correo con `Mail::to($to)->send(new DiseaseAlertMail($payload))`.

### Dependencias
- `App\Mail\DiseaseAlertMail`
- Configuración: `services.disease_alert_email`

---

## 8) SendDiseaseAlertsJob

**Archivo:** `app/Jobs/SendDiseaseAlertsJob.php`

### Propósito
Evalúa registros recientes de `enfermedad_horas_acumuladas_condiciones` (última hora) y envía correos cuando el riesgo es alto.

### Configuración de cola
- `timeout = 300`
- `tries = 3`
- Implementa `failed(Throwable $e)`

### Flujo
1. Define rango `now` y `now - 1h` en `America/Mexico_City`.
2. Consulta registros y calcula horas: `(minutos / 60.0)`.
3. Determina estado:
   - `ALTO RIESGO` si `horas >= riesgo_mediciones`
   - `RIESGO MEDIO` si `horas >= riesgo_medio`
   - `BAJO` en otro caso
4. Envía correo **solo** para `ALTO RIESGO` (riesgo medio está comentado en el job).
5. El correo usa vistas Blade:
   - `emails.high_risk_disease_alert`

### Dependencias/config
- `config('services.disease_alert_email')` (destinatario)
- `config('services.brevo.from_address')` (remitente)

### Cómo se ejecuta en este proyecto
- `SendDiseaseAlertsCommand` **no lo despacha** a cola: instancia el job y llama `handle()` directamente.

---

## 9) SincronizarDatosViento

**Archivo:** `app/Jobs/SincronizarDatosViento.php`

### Propósito
Sincroniza datos de **viento** (actual + pronóstico) desde OpenWeather para cada parcela con coordenadas.

### Fuentes de datos externas
- Endpoint actual: `https://api.openweathermap.org/data/2.5/weather`
- Endpoint pronóstico: `https://api.openweathermap.org/data/2.5/forecast`

### Entradas
- No recibe parámetros.

### Flujo
1. Obtiene parcelas con `lat/lon` válidos y excluye parcelas marcadas como error activo (`ParcelaErrorViento::activas()`).
2. Por cada parcela:
   - Busca zona de manejo asociada (`ZonaManejos::where('parcela_id', parcela->id)->first()`).
   - Obtiene datos actuales y pronóstico.
   - Guarda en `DatosViento` con deduplicación por `parcela_id + fecha_hora_dato + tipo_dato`.
3. Si falla una parcela, registra/actualiza error en `ParcelaErrorViento` e incrementa intentos.

### Persistencia
- Escritura:
  - `datos_vientos` (`DatosViento`) con `tipo_dato` = `historico|pronostico`
  - tabla de control de errores: `ParcelaErrorViento`

### Config requerida
- `config('services.openweathermap.key')`

### Cómo ejecutarlo
- `SincronizarDatosVientoCommand` lo despacha a cola: `SincronizarDatosViento::dispatch()`.

---

## 10) SincronizarPrecipitacionPluvial

**Archivo:** `app/Jobs/SincronizarPrecipitacionPluvial.php`

### Propósito
Sincroniza datos de **precipitación** (actual + pronóstico) desde OpenWeather para cada parcela con coordenadas.

### Fuentes externas
- Endpoint actual: `https://api.openweathermap.org/data/2.5/weather` (usa `rain.1h`)
- Endpoint pronóstico: `https://api.openweathermap.org/data/2.5/forecast` (usa `rain.3h` y `pop`)

### Flujo
1. Obtiene parcelas con `lat/lon` válidos y excluye `ParcelaErrorPrecipitacion::activas()`.
2. Por parcela:
   - Busca zona de manejo.
   - Obtiene actuales (`precipitacion_mm = rain['1h'] ?? 0`).
   - Obtiene pronóstico (`precipitacion_mm = rain['3h'] ?? 0`, `precipitacion_probabilidad = pop*100`).
   - Guarda en `PrecipitacionPluvial` con deduplicación por `parcela_id + fecha_hora_dato + tipo_dato`.
3. Maneja errores registrando en `ParcelaErrorPrecipitacion`.

### Config requerida
- `config('services.openweathermap.key')`

### Cómo ejecutarlo
- `SincronizarPrecipitacionPluvialCommand` lo despacha a cola: `SincronizarPrecipitacionPluvial::dispatch()`.

---

## Operación (cola vs ejecución directa)

- Jobs despachados a cola (asíncronos) en este repo:
  - `CalcularIndicadoresEstresJob` (via command)
  - `SincronizarDatosViento`
  - `SincronizarPrecipitacionPluvial`

- Jobs ejecutados directamente (síncronos) desde commands:
  - `CalcularUnidadesCalorJob` (`dispatchSync`)
  - `CalcularUnidadesFrioJob` (`dispatchSync`)
  - `ProcesarTemperaturaDia` (instancia + `handle()`)
  - `SendDiseaseAlertsJob` (instancia + `handle()`)

Si se decide mover más jobs a cola, estandarizar:

- Driver (`QUEUE_CONNECTION`) y worker en servidor
- Estrategia de reintentos (`tries`, `backoff`, `timeout`)
- Idempotencia por claves naturales (`fecha + zona`, `fecha_hora + parcela`, etc.)

---

# 11) `SincronizarPresionAtmosferica`

## Archivo
- `app/Jobs/SincronizarPresionAtmosferica.php`

## Propósito
Obtiene datos de **presión atmosférica** desde **OpenWeather** para las parcelas con coordenadas válidas, y guarda:

- **Dato actual** (tipo `historico`)
- **Pronóstico** (tipo `pronostico`) para ~72 horas (24 registros de 3 horas)

Además, administra una tabla de **errores por parcela** para excluir parcelas problemáticas en ejecuciones posteriores.

## Tipo de ejecución (cola)
- Implementa `ShouldQueue`.
- Propiedades:
  - `public $timeout = 300;` (5 minutos)
  - `public $tries = 3;` (reintentos por fallo)

## Dependencias
### Modelos / tablas
- `App\Models\Parcelas` (origen de coordenadas, `lat`, `lon`)
- `App\Models\ZonaManejos` (se busca por `parcela_id`)
- `App\Models\PresionAtmosferica` (persistencia de datos)
- `App\Models\ParcelaErrorPresion` (persistencia de errores por parcela)

### Configuración
- Requiere API Key: `config('services.openweathermap.key')`
  - usualmente proviene de `.env` y `config/services.php`.

### Integración externa
- OpenWeather:
  - `https://api.openweathermap.org/data/2.5/weather`
  - `https://api.openweathermap.org/data/2.5/forecast`
- Requests via `Http::get(...)` con:
  - `lat`, `lon`, `appid`, `units=metric`

## Flujo de ejecución (handle)
1. Obtiene parcelas con coordenadas válidas:
   - `lat` y `lon` no nulos, distintos de 0
2. Excluye parcelas con error activo:
   - `ParcelaErrorPresion::activas()->pluck('parcela_id')`
3. Itera cada parcela:
   - intenta procesarla (`procesarParcela`)
   - si falla:
     - registra error en log
     - guarda/actualiza error en `parcela_error_presion` (`guardarErrorParcela`)
4. Al final registra:
   - número de parcelas exitosas
   - listado de nuevas parcelas con error (en log)

## Detalle de procesamiento por parcela (`procesarParcela`)
1. Busca zona de manejo:
   - `ZonaManejos::where('parcela_id', $parcela->id)->first()`
   - si no existe, log y termina.
2. Obtiene “dato actual”:
   - `obtenerDatosActuales($parcela)`
3. Obtiene “pronóstico”:
   - `obtenerDatosPronostico($parcela)`
4. Guarda:
   - `guardarDatos(..., [$datosActuales], 'historico')`
   - `guardarDatos(..., $datosPronostico, 'pronostico')`

## Estructura de datos consumida (OpenWeather)
### `obtenerDatosActuales()`
Retorna un arreglo con:
- `fecha_hora_dato` (now, `America/Mexico_City`)
- `pressure` (hPa)
- `sea_level` (si viene en respuesta)
- `grnd_level` (si viene en respuesta)
- `datos_raw` (JSON con `temp`, `humidity`, `pressure`)

### `obtenerDatosPronostico()`
Retorna un arreglo de registros (máx. 24):
- `fecha_hora_dato` (timestamp del item)
- `pressure`
- `sea_level`
- `grnd_level`
- `datos_raw` (JSON con `temp`, `humidity`, `pressure`)

> Nota: el job toma los primeros **24** elementos de `list` (cada uno es un bloque de 3 horas) ⇒ aproximadamente **72 horas**.

## Persistencia (`guardarDatos`)
Antes de insertar, valida idempotencia básica:
- verifica si existe registro con:
  - `parcela_id`
  - `fecha_hora_dato`
  - `tipo_dato`

Si no existe, crea en `PresionAtmosferica` con campos (según el código):
- `parcela_id`
- `zona_manejo_id`
- `fecha_solicita` (día de ejecución, `Y-m-d`)
- `hora_solicita` (`H:i:s`)
- `lat`, `lon`
- `fecha_hora_dato`
- `pressure`, `sea_level`, `grnd_level`
- `tipo_dato` (`historico` | `pronostico`)
- `fuente` = `openweather`
- `datos_raw` (JSON)

### Idempotencia (importante)
- El job **no actualiza** registros existentes: solo inserta cuando no existe.
- Si se requiere “recalcular/reescribir” (por cambios en la fuente), convendría migrar a `updateOrCreate()`.

## Manejo de errores por parcela (`guardarErrorParcela`)
- Si ya existe registro en `ParcelaErrorPresion`:
  - actualiza `error_mensaje`, `ultimo_intento`, `activo=true`
  - incrementa intentos con `$errorExistente->incrementarIntento()`
- Si no existe:
  - crea registro con:
    - `error_tipo = 'api_error'`
    - `error_mensaje`
    - `intentos_fallidos = 1`
    - `ultimo_intento = now()`
    - `activo = true`

## Método `failed()`
Registra log si el job termina en estado “failed”.

## Cómo ejecutarlo
### Vía Command (recomendado)
En este proyecto existe el comando:

```bash
php artisan presion:sync
# opcional (según signature del command):
php artisan presion:sync --parcela-id=123
```

Internamente el command hace:
- `SincronizarPresionAtmosferica::dispatch();`

### Vía dispatch manual
```php
\App\Jobs\SincronizarPresionAtmosferica::dispatch();
```

---

# 12) `UpdateWeatherForecastJob`

## Archivo
- `app/Jobs/UpdateWeatherForecastJob.php`

## Propósito
Ejecuta la actualización de pronósticos meteorológicos llamando directamente a:

- `App\Http\Controllers\Api\ForeCastController::guardaPronostico()`

y registra en logs el resultado (`parcelas_procesadas`, `total_parcelas`, `warnings`).

## Tipo de ejecución (cola)
- Implementa `ShouldQueue`.
- No define explícitamente `timeout/tries` (usa defaults del worker).

## Dependencias
- Controlador: `App\Http\Controllers\Api\ForeCastController`
- Logs: `Illuminate\Support\Facades\Log`

> Nota: el archivo importa `App\Jobs\ResumenTemperaturasJob`, pero **no se usa** en el código proporcionado (posible intención de encadenar procesos).

## Flujo de ejecución (`handle()`)
1. Log de inicio:
   - “Iniciando actualización de pronósticos…”
2. Ejecuta controlador:
   - `$result = $controller->guardaPronostico()`
   - `$data = $result->getData()`
3. Log de éxito con:
   - `parcelas_procesadas`
   - `total_parcelas`
   - `warnings` (si existen)
4. En excepción:
   - log error con `message` y `trace`

## Persistencia
Este job **no escribe directamente** en tablas; la persistencia depende de lo que realice `guardaPronostico()` (típicamente `forecast` / `forecast_hourly` u otras relacionadas).

## Cómo ejecutarlo
### Dispatch manual
```php
\App\Jobs\UpdateWeatherForecastJob::dispatch();
```

### Scheduler (típico)
En `app/Console/Kernel.php`:
```php
$schedule->job(new \App\Jobs\UpdateWeatherForecastJob())->hourly();
```

> Ajustar frecuencia según consumo de API y volumen de parcelas.

## Consideraciones técnicas
- El job depende de un **Controller** (acoplamiento). A mediano plazo conviene mover la lógica a un **Service** (p. ej. `ForecastService`) y que tanto el controller como el job lo consuman.
- Si se requiere ejecutar resúmenes posteriores (ej. `ResumenTemperaturasJob`), se puede:
  - encadenar jobs, o
  - disparar un evento al finalizar, o
  - programarlo por cron separado.

---

## Checklist de configuración (todos jobs)

- `.env`:
  - `QUEUE_CONNECTION=...` (si van en cola)
  - `OPENWEATHER_KEY=...` (si se usa OpenWeather)
- `config/services.php`:
  - `openweathermap.key` apuntando al env
- Worker:
  - `php artisan queue:work` (local)
  - Supervisor/daemon en producción

