# Documentación técnica — Comandos (Artisan) — Parte 3

## Alcance
Este documento describe **10 comandos de Laravel (Artisan)** relacionados con:
- Sincronización de catálogos desde `norman_prod` hacia la BD local (clientes, cultivos, enfermedades, estaciones, etapas, relaciones).
- Sincronización masiva de datos históricos entre bases (`norman_old` → `aws`, `pia_dev` → `aws`).
- Despacho a cola de un job de OpenWeather (presión atmosférica).

> **Ubicación esperada de comandos:** `app/Console/Commands/*`

---

## Índice rápido

| # | Comando | Archivo | Tipo | Conexiones | Impacto |
|---:|---|---|---|---|---|
| 1 | `presion:sync` | `SincronizarPresionAtmosfericaCommand.php` | Cola (Job) | — | Despacha sincronización de presión (OpenWeather). |
| 2 | `app:sync-clientes` | `SyncClientes.php` | Sync | `norman_prod` → default | Inserta clientes (solo nuevos). |
| 3 | `app:sync-cultivos` | `SyncCultivos.php` | Sync | `norman_prod` → default | Inserta cultivos/especies (solo nuevos). |
| 4 | `app:sync-enfermedades` | `SyncEnfermedades.php` | Sync | `norman_prod` → default | Inserta enfermedades (solo nuevas). |
| 5 | `sync:enfermedad-horas-acumuladas` | `SyncEnfermedadHorasAcumuladasCondiciones.php` | Sync masivo | `pia_dev` → `aws` | Inserta en lote acumulados de enfermedad. |
| 6 | `app:sync-estacion-dato` | `SyncEstacionDato.php` | Sync masivo | `norman_old` → `aws` | Copia `estacion_dato` a `aws.estacion_dato_pruebas` con filtros. |
| 7 | `app:sync-estaciones` | `SyncEstaciones.php` | Sync | `norman_prod` → default | Inserta estaciones (solo nuevas, por `uuid`). |
| 8 | `app:sync-estacion-variables-medicion` | `SyncEstacionVariablesMedicion.php` | Sync | `norman_prod` → default | Crea relación estación ↔ variables por slugs. |
| 9 | `app:sync-etapa-fenologicas` | `SyncEtapaFenologicas.php` | Sync | `norman_prod` → default | Inserta/actualiza etapas fenológicas. |
| 10 | `app:sync-etapa-fenologica-tipo-cultivo` | `SyncEtapaFenologicaTipoCultivo.php` | Sync | `norman_prod` → default | Inserta relación etapa ↔ tipo_cultivo (solo nuevas). |

> Nota: “default” se refiere a la conexión por defecto de Laravel (la BD principal del proyecto).  
> Nota: Varios comandos son **insert-only** (no actualizan registros existentes).

---

## 1) `presion:sync` — Sincronización de presión atmosférica (OpenWeather)

**Archivo:** `app/Console/Commands/SincronizarPresionAtmosfericaCommand.php`  
**Descripción:** Despacha un Job para sincronizar datos de presión atmosférica desde OpenWeather.

### Firma
```bash
php artisan presion:sync {--parcela-id= : ID específico de parcela}
```

### Opciones
- `--parcela-id=`: Declarada, **pero no se utiliza** en el comando (no se pasa al Job).

### Flujo (handle)
1. Muestra mensajes de inicio.
2. `SincronizarPresionAtmosferica::dispatch();`
3. Informa que el Job fue enviado a cola.
4. Maneja excepción y retorna código `1` si falla.

### Dependencias
- Job: `App\Jobs\SincronizarPresionAtmosferica`
- Requiere worker de colas:
  - `php artisan queue:work`

### Ejemplos
```bash
php artisan presion:sync
# (opción declarada pero no efectiva actualmente)
php artisan presion:sync --parcela-id=123
```

### Notas / mejoras sugeridas
- Si se requiere filtrar por parcela, pasar `--parcela-id` al Job o implementarlo en el comando.

---

## 2) `app:sync-clientes` — Sync de clientes desde `norman_prod`

**Archivo:** `app/Console/Commands/SyncClientes.php`  
**Descripción:** Copia clientes desde `norman_prod.cliente` hacia el modelo local `Clientes`.

### Firma
```bash
php artisan app:sync-clientes
```

### Flujo
1. Lee desde `DB::connection('norman_prod')->table('cliente')`.
2. Procesa en `chunk(1000)` ordenado por `id`.
3. Por cada registro:
   - Verifica existencia local por `Clientes::where('id', $registro->id)->exists()`.
   - Si **no existe**, inserta con Eloquent.

### Campos sincronizados
- `id`
- `nombre`
- `empresa`
- `ubicacion`
- `telefono`
- `status` ← `estatus` (origen)

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `cliente`
- Modelo local: `App\Models\Clientes`

### Ejemplo
```bash
php artisan app:sync-clientes
```

### Consideraciones
- **Insert-only:** si cambia un cliente en `norman_prod`, este comando **no lo actualiza** localmente (solo inserta cuando no existe).

---

## 3) `app:sync-cultivos` — Sync de cultivos (especies) desde `norman_prod`

**Archivo:** `app/Console/Commands/SyncCultivos.php`  
**Descripción:** Copia especies/cultivos desde `norman_prod.especie` hacia el modelo local `Cultivo`.

### Firma
```bash
php artisan app:sync-cultivos
```

### Flujo
1. Lee `norman_prod.especie` en `chunk(1000)` ordenado por `id`.
2. Inserta solo registros nuevos (verificación por `id`).

### Campos sincronizados
- `id`
- `nombre`
- `descripcion`
- `imagen`
- `icono`
- `temp_base_calor`
- `tipo_vida`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `especie`
- Modelo local: `App\Models\Cultivo`

### Ejemplo
```bash
php artisan app:sync-cultivos
```

### Consideraciones
- **Insert-only:** no actualiza registros ya existentes.

---

## 4) `app:sync-enfermedades` — Sync de enfermedades desde `norman_prod`

**Archivo:** `app/Console/Commands/SyncEnfermedades.php`  
**Descripción:** Copia enfermedades desde `norman_prod.enfermedad` hacia el modelo local `Enfermedades`.

### Firma
```bash
php artisan app:sync-enfermedades
```

### Flujo
1. Lee `norman_prod.enfermedad` en `chunk(1000)` ordenado por `id`.
2. Inserta solo registros nuevos (por `id`).

### Campos sincronizados
- `id`
- `nombre`
- `slug`
- `status` ← `estatus` (origen)

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `enfermedad`
- Modelo local: `App\Models\Enfermedades`

### Ejemplo
```bash
php artisan app:sync-enfermedades
```

### Consideraciones
- **Insert-only:** no actualiza registros ya existentes.

---

## 5) `sync:enfermedad-horas-acumuladas` — Sync masivo `pia_dev` → `aws`

**Archivo:** `app/Console/Commands/SyncEnfermedadHorasAcumuladasCondiciones.php`  
**Descripción:** Sincroniza `enfermedad_horas_acumuladas_condiciones` desde `pia_dev` hacia `aws` en lotes.

### Firma
```bash
php artisan sync:enfermedad-horas-acumuladas   {--batch-size=1000 : Tamaño del lote para procesamiento}   {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}
```

### Opciones
- `--batch-size=` (int, default `1000`): tamaño de chunk/lote.
- `--dry-run` (bool): no inserta, solo simula conteos y logs.

### Flujo
1. Valida conexiones contando registros:
   - `pia_dev.enfermedad_horas_acumuladas_condiciones`
   - `aws.enfermedad_horas_acumuladas_condiciones`
2. Define barra de progreso con total del origen (`pia_devCount`).
3. Lee origen por `chunk($batchSize)` ordenado por `id`.
4. Acumula filas en `$batch` y cuando alcanza `$batchSize`:
   - Inserta en AWS con `insert($batch)` (no ignora duplicados).
   - En dry-run: solo incrementa contador simulado.
5. Inserta remanente final.
6. Imprime resumen y registra `Log::info(...)`.

### Mapeo de columnas (origen → destino)
- `id` → `id`
- `fecha` → `fecha`
- `minutos` → `minutos`
- `especie_id` → `tipo_cultivo_id`
- `enfermedad_id` → `enfermedad_id`
- `estacion_id` → `estacion_id`
- `created_at` / `updated_at` → `Carbon::now()` (no conserva timestamps originales)

### Dependencias
- Conexiones: `pia_dev`, `aws`
- Tabla: `enfermedad_horas_acumuladas_condiciones`
- Facades: `DB`, `Log`
- `Carbon` (timestamps)

### Ejemplos
```bash
# Default batch 1000
php artisan sync:enfermedad-horas-acumuladas

# Batch más grande
php artisan sync:enfermedad-horas-acumuladas --batch-size=5000

# Simulación
php artisan sync:enfermedad-horas-acumuladas --dry-run
```

### Riesgos / troubleshooting
- El comando usa `insert()` (no `insertOrIgnore`): si AWS ya tiene registros con los mismos IDs, puede fallar por duplicados.
- La variable `$actualizados` se muestra en el resumen pero **no se actualiza** (permanece 0).
- `insert()` en Query Builder típicamente retorna `true/false`, no el número de filas: el contador `insertados` puede no reflejar la cantidad real. Recomendación: usar `insertOrIgnore` o contabilizar por `count($batch)`.

---

## 6) `app:sync-estacion-dato` — Sync masivo `norman_old` → `aws.estacion_dato_pruebas`

**Archivo:** `app/Console/Commands/SyncEstacionDato.php`  
**Descripción:** Extrae datos desde `norman_old.estacion_dato` y los inserta en `aws.estacion_dato_pruebas`. Permite filtrar por estación y por fecha (única o rango).

### Firma
```bash
php artisan app:sync-estacion-dato   {--estacion= : ID de la estación a sincronizar}   {--fecha= : Fecha única a sincronizar (formato: YYYY-MM-DD)}   {--fecha-inicio= : Fecha inicial del período (formato: YYYY-MM-DD)}   {--fecha-fin= : Fecha final del período (formato: YYYY-MM-DD)}
```

### Reglas de validación
- No se permite usar `--fecha` junto con `--fecha-inicio/--fecha-fin`.
- Si se usa período, **ambas** deben existir (`--fecha-inicio` y `--fecha-fin`).
- Valida formato `YYYY-MM-DD`.
- Valida que `fecha-inicio <= fecha-fin`.

### Flujo
1. Define destino fijo:
   - `$tablaDestino = 'estacion_dato_pruebas'` (en AWS).
2. Construye query base en `norman_old.estacion_dato` con filtros.
3. Cuenta total para progress bar.
4. Reconstruye la query (porque `count()` consume el builder) y procesa en `chunk(1000)`.
5. Inserta en AWS usando `insertOrIgnore($batch)` por lotes (`$batchSize = 1000`).
6. Imprime resumen y registra `Log::info(...)` con filtros aplicados.

### Campos insertados (batch)
Se inserta un arreglo con (valores `?? null`):
- `id_origen`
- `radiacion_solar`
- `viento`
- `precipitacion_acumulada`
- `humedad_relativa`
- `potencial_de_hidrogeno`
- `conductividad_electrica`
- `temperatura`
- `temperatura_lvl1`
- `humedad_15`
- `direccion_viento`
- `velocidad_viento`
- `co2`
- `ph`
- `phos`
- `nit`
- `pot`
- `estacion_id`
- `temperatura_suelo`
- `alertas`
- `capacidad_productiva`
- `bateria`
- `created_at`, `updated_at` (conserva timestamps del origen)

> Nota: No se inserta `id` (solo `id_origen`), lo cual sugiere que `estacion_dato_pruebas` tiene PK autoincremental o distinta estrategia.

### Dependencias
- Conexiones: `norman_old` (origen), `aws` (destino)
- Tabla origen: `estacion_dato`
- Tabla destino: `estacion_dato_pruebas`
- Facades: `DB`, `Log`

### Ejemplos
```bash
# Sin filtros (copiar todo el origen)
php artisan app:sync-estacion-dato

# Solo una estación
php artisan app:sync-estacion-dato --estacion=65

# Un día específico
php artisan app:sync-estacion-dato --fecha=2026-01-10

# Rango de fechas
php artisan app:sync-estacion-dato --fecha-inicio=2026-01-01 --fecha-fin=2026-01-07

# Rango + estación
php artisan app:sync-estacion-dato --estacion=65 --fecha-inicio=2026-01-01 --fecha-fin=2026-01-07
```

### Riesgos / performance
- El `count()` sobre rangos grandes puede ser pesado.
- El batch está hardcodeado a 1000 en el `chunk()` (aunque existe `$batchSize`).
- `insertOrIgnore` evita duplicados, pero requiere que el destino tenga un índice/constraint que dispare el “ignore”.

---

## 7) `app:sync-estaciones` — Sync de estaciones desde `norman_prod`

**Archivo:** `app/Console/Commands/SyncEstaciones.php`  
**Descripción:** Copia estaciones desde `norman_prod.inventario_estacion` hacia el modelo local `Estaciones`. Usa barra de progreso.

### Firma
```bash
php artisan app:sync-estaciones
```

### Flujo
1. Obtiene todas las estaciones de `norman_prod.inventario_estacion` con `get()` (sin chunk).
2. Crea progress bar con el total.
3. Por cada estación:
   - busca local por `uuid` (`Estaciones::where('uuid', ...)->first()`).
   - si no existe, crea registro local con `Estaciones::create([...])`.
4. Finaliza progress bar e imprime “completada”.

### Campos insertados
- `id`
- `uuid` (usado como llave de existencia)
- `tipo_estacion_id`
- `cliente_id`
- `fabricante_id`
- `almacen_id`
- `celular`
- `caracteristicas`
- `status`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `inventario_estacion`
- Modelo local: `App\Models\Estaciones`

### Ejemplo
```bash
php artisan app:sync-estaciones
```

### Consideraciones
- **Insert-only:** no actualiza estaciones existentes.
- Usa `get()` (carga todo en memoria). Si el volumen crece, conviene migrar a `chunk()`.

---

## 8) `app:sync-estacion-variables-medicion` — Relación estación ↔ variables (por slugs)

**Archivo:** `app/Console/Commands/SyncEstacionVariablesMedicion.php`  
**Descripción:** Para cada estación en `norman_prod.inventario_estacion`, toma el campo `variables` (lista separada por comas) y crea registros en el pivot local `EstacionVariable` usando slugs en `VariablesMedicion`.

### Firma
```bash
php artisan app:sync-estacion-variables-medicion
```

### Flujo
1. Lee `norman_prod.inventario_estacion` en `chunk(1000)`.
2. Para cada estación:
   - si existe `$registro->variables`:
     - separa por coma: `explode(',', $registro->variables)`
     - por cada slug:
       - busca `VariablesMedicion::where('slug', $name)->first()`
       - si existe, crea relación:
         - `EstacionVariable::firstOrCreate(['estacion_id' => ..., 'variables_medicion_id' => ...])`
       - si no existe, imprime warning.

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `inventario_estacion` (campo `variables`)
- Modelos locales:
  - `App\Models\VariablesMedicion`
  - `App\Models\EstacionVariable`

### Ejemplo
```bash
php artisan app:sync-estacion-variables-medicion
```

### Troubleshooting
- “Variable no encontrada: X”:
  - el slug no existe en `variables_medicion` local,
  - o no se ha sincronizado ese catálogo previamente.

---

## 9) `app:sync-etapa-fenologicas` — Sync de etapas fenológicas (insert + update)

**Archivo:** `app/Console/Commands/SyncEtapaFenologicas.php`  
**Descripción:** Sincroniza etapas fenológicas desde `norman_prod.etapa_fenologica`. A diferencia de otros comandos, **sí actualiza** registros existentes.

### Firma
```bash
php artisan app:sync-etapa-fenologicas
```

### Flujo
1. Obtiene **todas** las etapas desde `norman_prod.etapa_fenologica` con `get()`.
2. Crea progress bar.
3. Para cada etapa:
   - busca local por `id`.
   - si existe:
     - `update([...])`
   - si no:
     - `create([...])`
   - captura excepciones por etapa (no detiene todo el proceso).
4. Imprime totales creados/actualizados.

### Campos sincronizados
- `id`
- `nombre`
- `estacionalidad`
- `status` ← `estatus` (origen)

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `etapa_fenologica`
- Modelo local: `App\Models\EtapaFenologica`

### Ejemplo
```bash
php artisan app:sync-etapa-fenologicas
```

### Consideraciones
- Usa `get()` (carga todo en memoria). Si aumenta el volumen, cambiar a `chunk()`.

---

## 10) `app:sync-etapa-fenologica-tipo-cultivo` — Sync relación etapa ↔ tipo_cultivo

**Archivo:** `app/Console/Commands/SyncEtapaFenologicaTipoCultivo.php`  
**Descripción:** Sincroniza la tabla puente `especie_etapa` desde `norman_prod` y crea registros locales en `EtapaFenologicaTipoCultivo`.

### Firma
```bash
php artisan app:sync-etapa-fenologica-tipo-cultivo
```

### Flujo
1. Lee `norman_prod.especie_etapa` en `chunk(1000)` ordenado por `id`.
2. Por cada registro:
   - verifica si ya existe local por `id`.
   - valida que existan FKs localmente:
     - `tipo_cultivos.id = especie_id`
     - `etapa_fenologicas.id = etapa_id`
   - si no existe y FKs válidas:
     - inserta:
       - `id`
       - `tipo_cultivo_id` ← `especie_id`
       - `etapa_fenologica_id` ← `etapa_id`
3. Imprime “Sincronización completada”.

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `especie_etapa`
- Tablas/relaciones locales validadas por Query Builder:
  - `tipo_cultivos`
  - `etapa_fenologicas`
- Modelo local: `App\Models\EtapaFenologicaTipoCultivo`

### Ejemplo
```bash
php artisan app:sync-etapa-fenologica-tipo-cultivo
```

### Consideraciones
- **Insert-only:** no actualiza registros existentes.
- Si la tabla local se llama distinto o usa otro esquema, los checks `DB::table('tipo_cultivos')` / `DB::table('etapa_fenologicas')` deben ajustarse.

---

## Consideraciones generales del lote

### Conexiones requeridas
- `norman_prod` (catálogos)
- `norman_old` (histórico `estacion_dato`)
- `pia_dev` (histórico enfermedades)
- `aws` (destino de sincronizaciones masivas)

### Idempotencia (qué pasa si se ejecuta varias veces)
- `insert-only` con verificación previa (`exists()` / `first()`): en general idempotente (no duplica), pero **no actualiza**.
- `insertOrIgnore`: idempotente si hay constraints/índices adecuados.
- `insert()` (sin ignore): puede fallar si hay duplicados (ver comando #5).

### Recomendación de ejecución
- Para cargas masivas (#5 y #6): ejecutar en ventanas controladas y monitorear tiempo/uso de CPU/IO.
- Para comandos con `dispatch()` (#1): asegurar `queue:work` activo y revisar `failed_jobs`.

---
