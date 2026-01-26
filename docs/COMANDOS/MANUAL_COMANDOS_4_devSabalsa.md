# Documentación técnica — Comandos (Artisan) - Parte 4

## Alcance
Este documento describe **10 comandos de Laravel (Artisan)** de Laravel (Artisan) que cubren:
- Sincronización de catálogos desde `norman_prod` (fabricantes, parcelas, plagas, tipos de cultivo, relaciones).
- Sincronización/replicación de pronósticos (`forecast`) desde `aws` hacia la BD local.
- Sincronización operativa de pronósticos vía OpenWeatherMap (usando `ForeCastController`).
- Sincronización de nutrición por etapa y por etapa-tipo de cultivo.
- Sincronización masiva de resumen de temperaturas desde `pia_dev` hacia la BD local.

> **Ubicación esperada:** `app/Console/Commands/*`

---

## Índice rápido

| # | Comando | Archivo | Tipo | Conexiones | Impacto |
|---:|---|---|---|---|---|
| 1 | `app:sync-fabricantes` | `SyncFabricantes.php` | Sync catálogo | `norman_prod` → default | Inserta fabricantes nuevos (por `nombre`). |
| 2 | `app:sync-forecast` | `SyncForecast.php` | Sync masivo | `aws` → default | Copia registros de `forecast` de AWS a local (insertOrIgnore). |
| 3 | `forecasts:sync` | `SyncForecasts.php` | Operativo (API) | OpenWeatherMap | Ejecuta sincronización de pronósticos para parcelas (confirmación opcional). |
| 4 | `app:sync-nutricion-etapa` | `SyncNutricionEtapa.php` | Sync catálogo | `norman_prod` → default | Inserta variables de nutrición por etapa (posible problema con IDs). |
| 5 | `app:sync-nutricion-etapa-fenologica-tipo-cultivo` | `SyncNutricionEtapaFenologicaTipoCultivo.php` | Sync catálogo | `norman_prod` → default | Inserta rangos nutricionales por etapa/tipo cultivo (posible problema con IDs). |
| 6 | `app:sync-parcelas` | `SyncParcelas.php` | Sync catálogo | `norman_prod` → default | Inserta parcelas nuevas (por `id`). |
| 7 | `app:sync-plagas` | `SyncPlagas.php` | Sync catálogo | `norman_prod` → default | Inserta plagas nuevas (por `id`) y conserva timestamps. |
| 8 | `app:sync-resumen-remperaturas` | `SyncResumenRemperaturas.php` | Sync masivo | `pia_dev` → default | Upsert de `resumen_temperaturas` (por `id`). |
| 9 | `app:sync-tipo-cultivos` | `SyncTipoCultivos.php` | Sync catálogo | `norman_prod` → default | Inserta `tipo_cultivos` nuevos (por `id`), mapeando `cultivo_id` por nombre. |
| 10 | **(posible bug)** `php` | `SyncTipoCultivosEnfermedades.php` | Sync relación | `norman_prod` → default | Crea/actualiza relación tipo cultivo ↔ enfermedad (riesgos). |

> Nota: “default” es la conexión por defecto de Laravel (la BD principal del proyecto).

---

## 1) `app:sync-fabricantes` — Sync de fabricantes desde `norman_prod`

**Archivo:** `app/Console/Commands/SyncFabricantes.php`  
**Descripción (code):** “Sincroniza los fabricantes desde la base de datos norman_prod”

### Firma
```bash
php artisan app:sync-fabricantes
```

### Flujo (handle)
1. Obtiene todos los fabricantes desde `norman_prod.fabricante` (`get()`; no usa chunk).
2. Crea progress bar con el total.
3. Por cada registro:
   - Busca existencia local por `nombre`:
     - `Fabricante::where('nombre', $fabricante->nombre)->first()`
   - Si no existe, crea:
     - `id`, `nombre`, `status` (`estatus` → `status`).
4. Finaliza barra e imprime “Sincronización completada”.

### Campos sincronizados (origen → destino)
- `fabricante.id` → `fabricantes.id`
- `fabricante.nombre` → `fabricantes.nombre`
- `fabricante.estatus` → `fabricantes.status`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `fabricante`
- Modelo local: `App\Models\Fabricante`

### Ejemplo
```bash
php artisan app:sync-fabricantes
```

### Notas / riesgos
- La existencia se valida por **nombre**, pero se inserta el **id remoto**. Si el nombre ya existe con otro id, no se insertará (queda “desfasado” respecto a IDs).

---

## 2) `app:sync-forecast` — Copia de `forecast` desde `aws` hacia BD local

**Archivo:** `app/Console/Commands/SyncForecast.php`

### Firma
```bash
php artisan app:sync-forecast
```

### Propósito
Replica registros de `aws.forecast` hacia `forecast` en la BD local (conexión default), usando inserción por lotes con `insertOrIgnore()`.

### Flujo (handle)
1. Inicializa:
   - `$created = 0`
   - `$batchSize = 1000`
2. Lee `aws.forecast` por chunks de 1000 (`orderBy('id')->chunk(1000, ...)`).
3. Por cada registro:
   - Construye arreglo de inserción (sin `id`, se asume PK/estrategia local).
   - Normaliza campos de tiempo inválidos:
     - si `temperatureHighTime` / `temperatureLowTime` es `'0000-00-00 00:00:00'` → `null`.
   - Si el batch llega a 1000:
     - `DB::table('forecast')->insertOrIgnore($batch)`
     - Suma a `$created` el retorno del insert (`$inserted`).
4. Inserta remanente final.
5. Imprime: “Registros creados: X”.

### Campos insertados
- `parcela_id`
- `fecha_solicita`, `hora_solicita`
- `lat`, `lon`
- `fecha_prediccion`
- `summary`, `icon`, `uvindex`
- `sunriseTime`, `sunsetTime`
- `temperatureHigh`, `temperatureHighTime`
- `temperatureLow`, `temperatureLowTime`
- `precipProbability`
- `hourly` (si vacío → null)
- `created_at`, `updated_at` (conserva los del origen)

### Dependencias
- Conexión origen: `aws`
- Tabla origen: `forecast`
- Tabla destino: `forecast` (default)
- Facade: `DB`

### Ejemplo
```bash
php artisan app:sync-forecast
```

### Notas / riesgos
- El destino recibe datos con `insertOrIgnore`; para que “ignore” funcione, el destino debe tener índices/constraints que disparen conflicto (ej: unique).
- No hay progress bar; si el dataset es grande, la ejecución puede tardar sin feedback.

---

## 3) `forecasts:sync` — Sincronización de pronósticos (API OpenWeatherMap)

**Archivo:** `app/Console/Commands/SyncForecasts.php`  
**Descripción (code):** “Sincroniza pronósticos meteorológicos para todas las parcelas”

### Firma
```bash
php artisan forecasts:sync {--force : Forzar sincronización sin confirmación}
```

### Opciones
- `--force` (bool): omite confirmación interactiva.

### Flujo (handle)
1. Verifica API key:
   - `$apiKey = config('services.openweathermap.key')`
   - Si está vacía:
     - error y sugiere `OPENWEATHERMAP_KEY` en `.env`.
     - retorna `1`.
2. Si no viene `--force`, solicita confirmación:
   - `confirm('¿Deseas continuar con la sincronización?')`
3. Ejecuta sincronización creando un controlador:
   - `$controller = new ForeCastController();`
   - `$response = $controller->guardaPronostico();`
4. Lee `$data = $response->getData()` y revisa:
   - Si HTTP 200:
     - imprime parcelas procesadas y total.
     - muestra `warnings` si existen.
   - Si no:
     - imprime `errors` si existen y retorna `1`.
5. Captura excepciones y retorna `1`.

### Dependencias
- Controlador: `App\Http\Controllers\Api\ForeCastController`
- Config: `services.openweathermap.key` (mapeado desde `.env`)
- Requiere conectividad a OpenWeatherMap (la lógica vive en el controlador).

### Ejemplos
```bash
# Interactivo (pregunta confirmación)
php artisan forecasts:sync

# No interactivo
php artisan forecasts:sync --force
```

### Notas
- El comando depende de un **controller** (no un service). Si se desea mejor separación, migrar la lógica a un Service/Job y reutilizarlo desde el controller y el command.

---

## 4) `app:sync-nutricion-etapa` — Sync nutrición por etapa fenológica

**Archivo:** `app/Console/Commands/SyncNutricionEtapa.php`

### Firma
```bash
php artisan app:sync-nutricion-etapa
```

### Propósito
Sincroniza registros desde `norman_prod.nutricion_etapa` hacia la tabla/modelo local `NutricionEtapa`.

### Flujo (handle)
1. Lee `norman_prod.nutricion_etapa` en chunks de 1000 (`orderBy('id')->chunk(1000, ...)`).
2. Por cada registro:
   - Verifica existencia local por `id`:
     - `NutricionEtapa::where('id', $registro->id)->exists()`
   - Si no existe:
     - crea un `NutricionEtapa` y guarda:
       - `variable`
       - `etapa_fenologica_id` ← `etapa_id` (origen)
3. Imprime “Sincronización completada”.

### Campos sincronizados (origen → destino)
- `nutricion_etapa.variable` → `nutricion_etapas.variable`
- `nutricion_etapa.etapa_id` → `nutricion_etapas.etapa_fenologica_id`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `nutricion_etapa`
- Modelo local: `App\Models\NutricionEtapa`

### Ejemplo
```bash
php artisan app:sync-nutricion-etapa
```

### Riesgo importante (IDs)
- El comando verifica existencia por `id` **pero NO asigna** `$nuevo->id = $registro->id`.
- Consecuencia probable:
  - se insertan registros con ID autoincremental local,
  - en ejecuciones posteriores, el `exists(id = origen.id)` seguirá dando falso,
  - lo cual puede generar **duplicados**.
- Recomendación:
  - asignar `id` explícitamente, o
  - cambiar el criterio de existencia a (por ejemplo) combinación `variable + etapa_fenologica_id`.

---

## 5) `app:sync-nutricion-etapa-fenologica-tipo-cultivo` — Sync rangos nutricionales por etapa/tipo cultivo

**Archivo:** `app/Console/Commands/SyncNutricionEtapaFenologicaTipoCultivo.php`

### Firma
```bash
php artisan app:sync-nutricion-etapa-fenologica-tipo-cultivo
```

### Propósito
Importa registros desde `norman_prod.nutricion_etapa_especie` hacia el modelo local `NutricionEtapaFenologicaTipoCultivo`, con un filtro de fecha.

### Filtro fijo aplicado
- Solo registros con:
  - `created_at > '2025-03-27 00:00:00'`

### Flujo (handle)
1. Lee `norman_prod.nutricion_etapa_especie` con filtro de `created_at`, en chunks de 1000.
2. Por cada registro:
   - Verifica existencia local por `id`:
     - `NutricionEtapaFenologicaTipoCultivo::where('id', $registro->id)->exists()`
   - Verifica que exista el tipo cultivo local:
     - `DB::table('tipo_cultivos')->where('id', $registro->especie_id)->exists()`
   - Si no existe y tipo cultivo existe:
     - inserta un nuevo registro con mapeos (ver abajo).
3. Imprime “Sincronización completada”.

### Campos sincronizados (origen → destino)
- `especie_id` → `tipo_cultivo_id`
- `especie_etapa_id` → `etapa_fenologica_tipo_cultivo_id`
- `variable` → `variable`
- `min_val` → `min_val`
- `max_val` → `max_val`
- `muy_bajo` → `bajo`
- `optimo` → `optimo_min`
- `optimo_max` → `optimo_max`
- `muy_alto` → `alto`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `nutricion_etapa_especie`
- Tablas locales requeridas:
  - `tipo_cultivos` (para validar FK)
- Modelo local: `App\Models\NutricionEtapaFenologicaTipoCultivo`

### Ejemplo
```bash
php artisan app:sync-nutricion-etapa-fenologica-tipo-cultivo
```

### Riesgo importante (IDs)
- Igual que el comando anterior: valida existencia por `id` pero **no asigna** `id` al nuevo registro.
- Resultado probable: **duplicados** en ejecuciones posteriores (por mismatch de IDs).
- Recomendación: asignar `id` o cambiar el criterio de existencia.

---

## 6) `app:sync-parcelas` — Sync de parcelas desde `norman_prod`

**Archivo:** `app/Console/Commands/SyncParcelas.php`  
**Descripción (code):** “Carga los registros de parcelas desde la base de datos remota a la base de datos local”

### Firma
```bash
php artisan app:sync-parcelas
```

### Flujo (handle)
1. Lee `norman_prod.parcela` por chunks de 1000 ordenado por `id`.
2. Inserta solo cuando no exista localmente por `id`.

### Campos sincronizados
- `id`
- `cliente_id`
- `nombre`
- `superficie`
- `lat`, `lon`
- `status` ← `estatus` (origen)

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `parcela`
- Modelo local: `App\Models\Parcelas`

### Ejemplo
```bash
php artisan app:sync-parcelas
```

---

## 7) `app:sync-plagas` — Sync de plagas desde `norman_prod`

**Archivo:** `app/Console/Commands/SyncPlagas.php`

### Firma
```bash
php artisan app:sync-plagas
```

### Flujo (handle)
1. Lee `norman_prod.plaga` por chunks de 1000.
2. Si no existe local por `id`, crea un nuevo `Plaga` y asigna campos.
3. Copia `created_at` y `updated_at` del origen y desactiva timestamps automáticos:
   - `$dato->timestamps = false;`

### Campos sincronizados
Incluye (según código):
- `id`, `nombre`, `descripcion`, `slug`, `imagen`
- `posicion1..posicion6`
- `umbral_min`, `umbral_max`
- `unidades_calor_ciclo`
- `created_at`, `updated_at`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `plaga`
- Modelo local: `App\Models\Plaga`

### Ejemplo
```bash
php artisan app:sync-plagas
```

---

## 8) `app:sync-resumen-remperaturas` — Sync de resumen de temperaturas (pia_dev → local) con upsert

**Archivo:** `app/Console/Commands/SyncResumenRemperaturas.php`  
**Descripción (code):** “Sincroniza el resumen de temperaturas desde la base de datos pia_dev”

### Firma
```bash
php artisan app:sync-resumen-remperaturas
```

### Propósito
Upsert masivo de `pia_dev.resumen_temperaturas` hacia `resumen_temperaturas` en la BD local.

### Flujo (handle)
1. Cuenta total de registros en `pia_dev.resumen_temperaturas`.
2. Crea progress bar.
3. Procesa por chunks de 1000 y arma batch de 1000.
4. Ejecuta `upsert` en tabla local:
   - keys: `['id']`
   - updates: lista de columnas (ver abajo)
5. Inserta remanente final, finaliza barra e imprime totales.

### Mapeo de columnas (origen → destino)
- `id` → `id`
- `zonamanejo_id` → `zona_manejo_id`
- `fecha` → `fecha`
- `max_nocturna`, `min_nocturna`, `amp_nocturna`
- `max_diurna`, `min_diurna`, `amp_diurna`
- `max`, `min`, `amp`
- `uc`, `uf`

### Columnas actualizadas en upsert
- `zona_manejo_id`, `fecha`
- `max_nocturna`, `min_nocturna`, `amp_nocturna`
- `max_diurna`, `min_diurna`, `amp_diurna`
- `max`, `min`, `amp`
- `uc`, `uf`

### Dependencias
- Conexión: `pia_dev`
- Tabla origen: `resumen_temperaturas`
- Tabla destino: `resumen_temperaturas` (default)
- Facade: `DB`

### Ejemplo
```bash
php artisan app:sync-resumen-remperaturas
```

### Notas
- No sincroniza `created_at`/`updated_at`. Si la tabla local los requiere, depende de defaults o de que sean nullable.

---

## 9) `app:sync-tipo-cultivos` — Sync de tipos de cultivo desde `norman_prod.especie`

**Archivo:** `app/Console/Commands/SyncTipoCultivos.php`

### Firma
```bash
php artisan app:sync-tipo-cultivos
```

### Propósito
Crea registros en `tipo_cultivos` (modelo `TipoCultivos`) a partir de `norman_prod.especie`, y vincula con `cultivo_id` usando el modelo `Cultivo` (búsqueda por nombre).

### Flujo (handle)
1. Lee `norman_prod.especie` por chunks de 1000.
2. Por cada registro:
   - valida si existe localmente `TipoCultivos` por `id`.
   - si no existe:
     - crea un `TipoCultivos` con:
       - `id` = `especie.id`
       - `cultivo_id` = `Cultivo::where('nombre', $registro->nombre)->first()->id`
       - `nombre` = `especie.nombre`
       - `status` = `1`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `especie`
- Modelos locales:
  - `App\Models\TipoCultivos`
  - `App\Models\Cultivo`

### Ejemplo
```bash
php artisan app:sync-tipo-cultivos
```

### Riesgos
- Si no existe un `Cultivo` con el mismo `nombre`, el `first()` devuelve `null` y fallará en `->id`.
  - Recomendación: validar null y registrar warning, o crear el `Cultivo` faltante antes.

---

## 10) **Firma incorrecta** `php` — Sync tipo cultivo ↔ enfermedad (con riesgos)

**Archivo:** `app/Console/Commands/SyncTipoCultivosEnfermedades.php`  
**Descripción (code):** “Sincroniza las relaciones entre tipos de cultivo y enfermedades desde la base de datos norman_prod”

### Firma (tal cual en el código)
```bash
php artisan php
```

> **Importante:** El `$signature` está definido como `'php'`, lo cual es casi seguro un error.
> Esto hace que el comando se registre como `php` en Artisan (confuso) o puede colisionar con nombres internos.
> Recomendación: renombrar a algo como `app:sync-tipo-cultivos-enfermedades`.

### Flujo (handle)
1. Cuenta total en `norman_prod.especie_enfermedad`.
2. Crea progress bar.
3. Procesa en chunks de 1000:
   - busca registro local por `id`.
   - si existe: hace `update([...])`.
   - si no existe: hace `create([...])`.
   - incrementa contadores `created`/`updated`.
   - captura errores por registro y continúa.
4. Finaliza y muestra resumen.

### Campos sincronizados
- `id`
- `tipo_cultivo_id` ← `especie_id`
- `enfermedad_id`
- umbrales/riesgos:
  - `riesgo_humedad`, `riesgo_humedad_max`
  - `riesgo_temperatura`, `riesgo_temperatura_max`
  - `riesgo_medio`
  - `riesgo_mediciones`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `especie_enfermedad`
- Modelo local: `App\Models\TipoCultivosEnfermedad`

### Ejemplo (si se mantiene el signature actual)
```bash
php artisan php
```

---

## Consideraciones generales del lote

### Conexiones requeridas
- `norman_prod` (catálogos: fabricante, parcela, plaga, especie, especie_enfermedad, nutrición)
- `pia_dev` (resumen_temperaturas)
- `aws` (forecast)

### Idempotencia
- Insert-only (por `id` o `nombre`): idempotente **solo si** el criterio coincide con lo insertado.
- `upsert` (resumen_temperaturas): idempotente por `id`.
- `insertOrIgnore` (forecast): idempotente si hay constraints únicos adecuados.
- **Atención** a comandos 4 y 5: validan existencia por `id` remoto pero no insertan ese `id`.

### Recomendación de ejecución
- Ejecutar primero los catálogos base (`cultivos`, `tipo_cultivos`, etc.) antes de relaciones (`tipo_cultivos_enfermedades`, nutrición por etapa).
- Para `forecasts:sync`: asegurar `.env` con `OPENWEATHERMAP_KEY` y el mapeo en `config/services.php`.

---
