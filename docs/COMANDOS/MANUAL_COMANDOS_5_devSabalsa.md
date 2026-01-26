# Documentación técnica — Comandos (Artisan) — Parte 5

## Alcance
Este documento cubre **10 comandos** de Laravel (Artisan) orientados a:
- Sincronización de catálogos/relaciones desde `norman_prod` (tipos de suelo, tipos de estación, relaciones con plagas, zonas de manejo y usuarios).
- Sincronización de series/históricos (unidades de frío, unidades de calor por plaga).
- Actualización de pronósticos meteorológicos vía controlador (`ForeCastController`).

> **Ubicación esperada de comandos:** `app/Console/Commands/*`  
> **Requisito general:** configurar conexiones DB (`norman_prod`) en `config/database.php`.

---

## Índice rápido

| # | Comando | Archivo | Tipo | Origen → destino | Notas clave |
|---:|---|---|---|---|---|
| 1 | `app:sync-tipo-cultivos-plagas` | `SyncTipoCultivosPlagas.php` | Sync relación | `norman_prod.especie_plaga` → local | Inserta por `id`, **sin validar FK plaga**. |
| 2 | `app:sync-tipo-estaciones` | `SyncTipoEstaciones.php` | Sync catálogo | `norman_prod.tipo_estacion` → local | Inserta por `nombre`, asigna `id` remoto. |
| 3 | `app:sync-tipo-suelos` | `SyncTipoSuelos.php` | Sync catálogo | `norman_prod.tipos_suelo` → local | Inserta por `tipo_suelo`, asigna `id` remoto. |
| 4 | `app:sync-unidades-calor-plaga` | `SyncUnidadesCalorPlaga.php` | Sync histórico | `norman_prod.unidades_calor_plaga` → local | Valida existencia de **plaga** en local. |
| 5 | `app:sync-unidades-frio` | `SyncUnidadesFrio.php` | Sync histórico | `norman_prod.unidades_frio` → local | Inserta por `id`, mapea `zona_id → zona_manejo_id`. |
| 6 | `app:sync-users` | `SyncUsers.php` | Sync catálogo | `norman_prod.users` → local | Copia `password` desde origen tal cual. |
| 7 | `app:sync-zona-manejos` | `SyncZonaManejos.php` | Sync catálogo (create/update) | `norman_prod.estacion_virtual` → local | Crea/actualiza por `id`, progress bar y resumen. |
| 8 | `app:sync-zona-manejos-tipo-cultivos` | `SyncZonaManejosTipoCultivos.php` | Sync relación | `norman_prod.estacion_virtual` → local | **Riesgo de duplicados**: no asigna `id` remoto. |
| 9 | `forecast:update` | `UpdateForecast.php` | Operativo (API) | — | **Archivo incompleto** (incluye `...`). |
| 10 | `weather:update` | `UpdateWeatherForecast.php` | Operativo (API) | — | Usa `Api\ForeCastController::guardaPronostico()`. |

---

## 1) `app:sync-tipo-cultivos-plagas` — Sync relación tipo_cultivo ↔ plaga

**Archivo:** `app/Console/Commands/SyncTipoCultivosPlagas.php`  
**Descripción:** (no definida con detalle en el código: `Command description`)

### Firma
```bash
php artisan app:sync-tipo-cultivos-plagas
```

### Fuente / destino
- **Origen:** `norman_prod.especie_plaga`
- **Destino:** modelo local `TipoCultivosPlaga`

### Flujo (handle)
1. Lee `norman_prod.especie_plaga` por chunks de **1000** (ordenado por `id`).
2. Por cada registro:
   - Si ya existe localmente `TipoCultivosPlaga` con el mismo `id`, lo omite.
   - Inserta registro nuevo con:
     - `id` (conserva id remoto)
     - `tipo_cultivo_id` ← `especie_id`
     - `plaga_id`
     - `created_at`, `updated_at` (conserva timestamps del origen)
     - `timestamps = false`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `especie_plaga`
- Modelo destino: `App\Models\TipoCultivosPlaga`

### Ejemplo
```bash
php artisan app:sync-tipo-cultivos-plagas
```

### Riesgos / notas
- El comentario indica “insertar solo si la plaga existe”, pero **el comando no valida** la existencia de `plaga_id` en tabla local `plagas`.  
  Si existen constraints, la inserción puede fallar; si no, puede dejar referencias huérfanas.

---

## 2) `app:sync-tipo-estaciones` — Sync de tipos de estación

**Archivo:** `app/Console/Commands/SyncTipoEstaciones.php`  
**Descripción:** Sincroniza los tipos de estación desde la base de datos `norman_prod`.

### Firma
```bash
php artisan app:sync-tipo-estaciones
```

### Fuente / destino
- **Origen:** `norman_prod.tipo_estacion`
- **Destino:** modelo local `TipoEstacion`

### Flujo (handle)
1. Muestra mensaje de inicio.
2. Obtiene todos los registros (`get()`).
3. Crea progress bar por total.
4. Por cada registro:
   - Busca existencia local por `nombre`.
   - Si no existe, crea:
     - `id`
     - `nombre`
     - `tipo_nasa`
     - `status` ← `estatus`
5. Finaliza progress bar e imprime mensaje final.

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `tipo_estacion`
- Modelo destino: `App\Models\TipoEstacion`

### Ejemplo
```bash
php artisan app:sync-tipo-estaciones
```

### Notas
- La existencia se valida por **nombre**, pero se inserta el **id remoto**. Si el nombre ya existe con otro id, no se insertará.

---

## 3) `app:sync-tipo-suelos` — Sync de tipos de suelo

**Archivo:** `app/Console/Commands/SyncTipoSuelos.php`  
**Descripción:** Sincroniza los tipos de suelo desde la base de datos `norman_prod`.

### Firma
```bash
php artisan app:sync-tipo-suelos
```

### Fuente / destino
- **Origen:** `norman_prod.tipos_suelo`
- **Destino:** modelo local `TipoSuelo`

### Flujo (handle)
1. Muestra mensaje de inicio.
2. Obtiene todos los registros (`get()`).
3. Crea progress bar por total.
4. Por cada registro:
   - Busca existencia local por `tipo_suelo`.
   - Si no existe, crea:
     - `id`
     - `tipo_suelo`
     - `bajo`
     - `optimo_min` ← `optimo`
     - `optimo_max`
     - `alto`
5. Finaliza progress bar e imprime mensaje final.

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `tipos_suelo`
- Modelo destino: `App\Models\TipoSuelo`

### Ejemplo
```bash
php artisan app:sync-tipo-suelos
```

---

## 4) `app:sync-unidades-calor-plaga` — Sync unidades de calor por plaga

**Archivo:** `app/Console/Commands/SyncUnidadesCalorPlaga.php`  
**Descripción:** (no definida con detalle en el código: `Command description`)

### Firma
```bash
php artisan app:sync-unidades-calor-plaga
```

### Fuente / destino
- **Origen:** `norman_prod.unidades_calor_plaga`
- **Destino:** modelo local `UnidadesCalorPlaga`

### Flujo (handle)
1. Lee `norman_prod.unidades_calor_plaga` por chunks de **1000**.
2. Por cada registro:
   - Si ya existe localmente por `id`, lo omite.
   - Valida que `plaga_id` exista en tabla local `plagas`:
     - si no existe, omite el registro.
   - Inserta:
     - `id`
     - `zona_manejo_id` ← `zonamanejo_id`
     - `plaga_id`
     - `uc`
     - `fecha`
     - `created_at`, `updated_at` (conserva timestamps del origen)
     - `timestamps = false`
3. Imprime “Sincronización completada.”

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `unidades_calor_plaga`
- Tabla local usada para validación: `plagas`
- Modelo destino: `App\Models\UnidadesCalorPlaga`

### Ejemplo
```bash
php artisan app:sync-unidades-calor-plaga
```

### Notas
- Sí valida existencia de `plaga_id`, pero no valida existencia de `zona_manejo_id` en tabla local de zonas.

---

## 5) `app:sync-unidades-frio` — Sync unidades de frío

**Archivo:** `app/Console/Commands/SyncUnidadesFrio.php`  
**Descripción:** (no definida con detalle en el código: `Command description`)

### Firma
```bash
php artisan app:sync-unidades-frio
```

### Fuente / destino
- **Origen:** `norman_prod.unidades_frio`
- **Destino:** modelo local `UnidadesFrio`

### Flujo (handle)
1. Lee `norman_prod.unidades_frio` por chunks de **1000** (ordenado por `id`).
2. Por cada registro:
   - Si ya existe localmente por `id`, lo omite.
   - Ejecuta `UnidadesFrio::unsetEventDispatcher();` (desactiva eventos/observers de Eloquent).
   - Inserta:
     - `id`
     - `zona_manejo_id` ← `zona_id`
     - `fecha`
     - `unidades`
     - `created_at`, `updated_at` (conserva timestamps del origen)
     - `timestamps = false`
3. Imprime “Sincronización completada.”

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `unidades_frio`
- Modelo destino: `App\Models\UnidadesFrio`

### Ejemplo
```bash
php artisan app:sync-unidades-frio
```

### Notas / mejoras sugeridas
- `unsetEventDispatcher()` dentro del loop puede ser innecesario repetidamente; podría ejecutarse una sola vez antes de procesar.

---

## 6) `app:sync-users` — Sync de usuarios

**Archivo:** `app/Console/Commands/SyncUsers.php`  
**Descripción:** (no definida con detalle en el código: `Command description`)

### Firma
```bash
php artisan app:sync-users
```

### Fuente / destino
- **Origen:** `norman_prod.users`
- **Destino:** modelo local `User`

### Flujo (handle)
1. Lee `norman_prod.users` por chunks de **1000**.
2. Por cada registro:
   - Si ya existe localmente por `id`, lo omite.
   - Inserta:
     - `id`
     - `nombre` ← `name`
     - `email`
     - `password` (copia literal)
     - `cliente_id`
     - `status` ← `estatus`
3. Imprime “Sincronización completada.”

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `users`
- Modelo destino: `App\Models\User`

### Ejemplo
```bash
php artisan app:sync-users
```

### Riesgos / notas
- El comando copia `password` tal cual desde el origen. Esto asume que el password ya está **hasheado** con el mismo algoritmo/configuración.  
  Si el origen guarda password en texto plano (no recomendado) o con un esquema distinto, la autenticación puede fallar.

---

## 7) `app:sync-zona-manejos` — Sync de zonas de manejo (create + update)

**Archivo:** `app/Console/Commands/SyncZonaManejos.php`  
**Descripción:** Sincroniza las zonas de manejo desde `norman_prod`.

### Firma
```bash
php artisan app:sync-zona-manejos
```

### Fuente / destino
- **Origen:** `norman_prod.estacion_virtual`
- **Destino:** modelo local `ZonaManejos`

### Flujo (handle)
1. Muestra mensaje de inicio.
2. Obtiene todos los registros de `estacion_virtual` (`get()`).
3. Crea progress bar (con formato custom) y contadores:
   - `creados`, `actualizados`.
4. Por cada registro:
   - Busca existencia local por `id`.
   - Si no existe: `ZonaManejos::create([...])`
   - Si existe: `$zonaManejoExistente->update([...])`
   - Incrementa contador correspondiente.
5. Finaliza progress bar y muestra resumen.

### Campos sincronizados (origen → destino)
- `id`
- `parcela_id`
- `tipo_suelo_id`
- `nombre`
- `fecha_inicial_uca`
- `temp_base_calor`
- `edad_cultivo`
- `fecha_siembra`
- `objetivo_produccion`
- `status` ← `estatus`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `estacion_virtual`
- Modelo destino: `App\Models\ZonaManejos`

### Ejemplo
```bash
php artisan app:sync-zona-manejos
```

### Notas
- Usa `get()` (carga todo en memoria). Si `estacion_virtual` crece, considerar migrar a `chunk()`.

---

## 8) `app:sync-zona-manejos-tipo-cultivos` — Sync relación zona_manejo ↔ tipo_cultivo

**Archivo:** `app/Console/Commands/SyncZonaManejosTipoCultivos.php`  
**Descripción:** (no definida con detalle en el código: `Command description`)

### Firma
```bash
php artisan app:sync-zona-manejos-tipo-cultivos
```

### Fuente / destino
- **Origen:** `norman_prod.estacion_virtual`
- **Destino:** modelo local `ZonaManejosTipoCultivos`

### Flujo (handle)
1. Lee `norman_prod.estacion_virtual` por chunks de **1000**.
2. Por cada registro:
   - Verifica si existe localmente por `id` (del registro remoto).
   - Si no existe, crea:
     - `zona_manejo_id` ← `registro.id`
     - `tipo_cultivo_id` ← `registro.especie_id`
     - `save()`

### Dependencias
- Conexión: `norman_prod`
- Tabla origen: `estacion_virtual`
- Modelo destino: `App\Models\ZonaManejosTipoCultivos`

### Ejemplo
```bash
php artisan app:sync-zona-manejos-tipo-cultivos
```

### Riesgo importante (posibles duplicados)
- El comando verifica existencia por `id` remoto (`where('id', $registro->id)`), pero **no asigna** `$nuevo->id = $registro->id`.  
  Si el modelo usa `id` autoincremental local, una segunda ejecución puede volver a insertar (porque nunca existirá un registro local con `id == id_remoto`), generando duplicados.
- Recomendación:
  - asignar explícitamente `id` remoto al crear el registro, o
  - cambiar el criterio de existencia a una llave compuesta: `(zona_manejo_id, tipo_cultivo_id)`.

---

## 9) `forecast:update` — Actualización de pronósticos (versión “debug”)

**Archivo:** `app/Console/Commands/UpdateForecast.php`  
**Descripción:** Actualiza los pronósticos del clima para todas las parcelas.

### Firma
```bash
php artisan forecast:update
```

### Estado del archivo proporcionado
El archivo contiene una línea `...` dentro del `handle()` (código incompleto), por lo que la documentación solo puede cubrir lo visible.

### Comportamiento visible
- En un `try/catch`:
  - imprime “Respuesta del controlador” y hace `json_encode($response, JSON_PRETTY_PRINT)`.
  - cuenta registros en:
    - `\App\Models\Forecast`
    - `\App\Models\ForecastHourly`
  - si ambos conteos > 0: “Pronósticos actualizados exitosamente”.
  - si no: “No se guardaron registros en la base de datos”.
- En error:
  - muestra el mensaje y registra en log (`forecast:update`).

### Dependencias
- Controlador: `App\Http\Controllers\ForeCastController` (no `Api\...`)
- Modelos: `App\Models\Forecast`, `App\Models\ForecastHourly`
- Logs: `Log`

### Ejemplo
```bash
php artisan forecast:update
```

### Pendiente para documentar completamente
- Falta el bloque donde se instancia y ejecuta el controlador (o servicio) y cómo guarda forecast/forecast_hourlies.

---

## 10) `weather:update` — Actualización de pronósticos (API)

**Archivo:** `app/Console/Commands/UpdateWeatherForecast.php`  
**Descripción:** Actualiza los pronósticos del clima para todas las parcelas.

### Firma
```bash
php artisan weather:update {--force : Forzar actualización incluso si ya existen datos}
```

### Opciones
- `--force`: declarada en la firma, **no se utiliza** dentro del comando (no hay `option('force')`).

### Flujo (handle)
1. Imprime mensaje de inicio.
2. En `try`:
   - instancia `App\Http\Controllers\Api\ForeCastController`.
   - ejecuta `$controller->guardaPronostico()`.
   - obtiene `$data = $result->getData()`.
   - imprime:
     - “Actualización completada exitosamente”
     - “Parcelas procesadas: X/Y”
   - si existen `warnings`, los imprime uno por uno.
   - registra en logs:
     - `parcelas_procesadas`, `total_parcelas`, `warnings`.
   - retorna `Command::SUCCESS`.
3. En `catch`:
   - imprime error y registra stacktrace en logs.
   - retorna `Command::FAILURE`.

### Dependencias
- Controlador: `App\Http\Controllers\Api\ForeCastController`
- Logs: `Log`
- Requiere que `guardaPronostico()` retorne un objeto con método `getData()` (típicamente `JsonResponse`).

### Ejemplos
```bash
php artisan weather:update
# (declarada, pero actualmente no cambia el comportamiento)
php artisan weather:update --force
```

---

## Consideraciones generales del lote

### Conexiones requeridas
- `norman_prod` para todos los `app:sync-*` del lote.

### Orden sugerido de ejecución (si se requiere integridad referencial)
1. Catálogos base:
   - `app:sync-tipo-suelos`
   - `app:sync-tipo-estaciones`
   - (plagas, tipos de cultivo, etc. desde lotes previos)
2. Zonas:
   - `app:sync-zona-manejos`
3. Relaciones / series:
   - `app:sync-zona-manejos-tipo-cultivos`
   - `app:sync-tipo-cultivos-plagas`
   - `app:sync-unidades-frio`
   - `app:sync-unidades-calor-plaga`

### Idempotencia
- Insert por `id` con asignación explícita de `id`: suele ser idempotente.
- Comandos que validan existencia por `id` pero **no insertan ese id** (ver #8) pueden **no ser idempotentes**.

---
