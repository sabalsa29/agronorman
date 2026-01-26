# Documentación técnica — Comandos (Artisan) — Parte 6 

## Alcance
Este documento describe **3 comandos** de Laravel (Artisan) enfocados en **validación y diagnóstico** de datos y dependencias para el flujo de temperaturas (p. ej. generación de `resumen_temperaturas`) y actividad de estaciones.

---

## Índice rápido

| # | Comando | Archivo | Propósito |
|---:|---|---|---|
| 1 | `app:verificar-datos-temperatura` | `app/Console/Commands/VerificarDatosTemperatura.php` | Valida por zona (para una fecha) si existen **datos de estación** y **forecast** requeridos. |
| 2 | `app:verificar-dependencias-temperatura` | `app/Console/Commands/VerificarDependenciasTemperatura.php` | Verifica que existan tablas clave y que haya datos para una fecha (estación, forecast y resúmenes). |
| 3 | `estaciones:verificar` | `app/Console/Commands/VerificarEstacionesCommand.php` | Diagnóstico de actividad de estaciones (últimos N días): registros, último dato y asignación a zonas. |

---

## 1) `app:verificar-datos-temperatura` — Verificación por zona (datos de temperatura + forecast)

**Archivo:** `app/Console/Commands/VerificarDatosTemperatura.php`  
**Descripción:** Verifica el estado de los datos de temperatura para debugging.

### Firma
```bash
php artisan app:verificar-datos-temperatura   {--fecha= : Fecha específica (YYYY-MM-DD)}   {--zona= : ID de zona de manejo específica}
```

### Opciones
| Opción | Tipo | Default | Descripción |
|---|---:|---:|---|
| `--fecha=` | string | `yesterday` | Fecha a evaluar (`Y-m-d`). |
| `--zona=` | int | null | Si se indica, evalúa solo esa `zona_manejos.id`. |

### Flujo (handle)
1. Resuelve `fecha`:
   - si viene `--fecha`: `Carbon::parse(...)->format('Y-m-d')`
   - si no: `Carbon::yesterday()->format('Y-m-d')`
2. Obtiene zonas:
   - si viene `--zona`: `ZonaManejos::where('id', $zonaId)->get()`
   - si no: `ZonaManejos::all()`
3. Imprime una tabla con columnas:
   - `ID`, `Nombre`, `Parcela`, `Estaciones`, `Datos Temp`, `Forecast`, `Estado`
4. El cuerpo de la tabla se construye en `verificarZonas($zonasManejo, $fecha)`.

### Lógica de verificación por zona (`verificarZonas`)
Por cada zona:

- **Estaciones asignadas**
  - Usa relación: `$zona->estaciones` → IDs: `$estacionIds`.

- **Datos de temperatura**
  - Cuenta registros en `estacion_dato`:
    - `whereIn('estacion_id', $estacionIds)`
    - `whereDate('created_at', $fecha)`
    - `count()`

- **Forecast**
  - Cuenta en `forecast`:
    - `where('parcela_id', $zona->parcela_id)`
    - `where('fecha_prediccion', $fecha)`
    - `where('fecha_solicita', $fecha)`
    - `count()`

- **Estado**
  - Regla de estado (prioridad en orden):
    1. Sin estaciones → `Sin estaciones`
    2. Sin datos temp (`$datosTemp == 0`) → `Sin datos temp`
    3. Sin forecast (`$forecast == 0`) → `Sin forecast`
    4. Caso contrario → `OK`

### Dependencias
- Modelos:
  - `App\Models\ZonaManejos` (relaciones esperadas: `estaciones`, `parcela`)
  - `App\Models\EstacionDato`
  - `App\Models\Forecast`

### Ejemplos
```bash
# Verificar fecha por defecto (ayer)
php artisan app:verificar-datos-temperatura

# Verificar fecha específica
php artisan app:verificar-datos-temperatura --fecha=2026-01-10

# Verificar una zona específica
php artisan app:verificar-datos-temperatura --zona=12 --fecha=2026-01-10
```

### Interpretación rápida
- `Sin estaciones`: la zona no tiene estaciones asociadas; no es posible generar resumen confiable.
- `Sin datos temp`: hay estaciones asignadas pero no existen registros en `estacion_dato` para esa fecha.
- `Sin forecast`: hay datos de estación pero falta `forecast` para la parcela/fecha.

---

## 2) `app:verificar-dependencias-temperatura` — Verificación de dependencias (tablas + datos por fecha)

**Archivo:** `app/Console/Commands/VerificarDependenciasTemperatura.php`  
**Descripción:** Verifica todas las dependencias necesarias para el comando de resumen de temperaturas.

### Firma
```bash
php artisan app:verificar-dependencias-temperatura   {--fecha= : Fecha específica (YYYY-MM-DD)}
```

### Opciones
| Opción | Tipo | Default | Descripción |
|---|---:|---:|---|
| `--fecha=` | string | `yesterday` | Fecha a evaluar (`Y-m-d`). |

### Flujo (handle)
1. Resuelve `fecha` (misma estrategia que el comando anterior).
2. Ejecuta 3 validaciones:
   - `verificarTablasPrincipales()`
   - `verificarTablasRelacion()`
   - `verificarDatosFecha($fecha)`

> El comando usa `DB::table(...)->count()` y captura excepciones por tabla/consulta (si la tabla no existe o hay error de conexión).

### Tablas verificadas

#### Tablas principales (`verificarTablasPrincipales`)
- `zona_manejos` — Zonas de manejo
- `estacion_dato` — Datos de estaciones
- `forecast` — Pronósticos de clima
- `resumen_temperaturas` — Resúmenes de temperatura

#### Tablas de relación / soporte (`verificarTablasRelacion`)
- `zona_manejos_estaciones` — Relación zonas-estaciones
- `zona_manejos_tipo_cultivos` — Relación zonas-cultivos
- `estaciones` — Estaciones meteorológicas
- `tipo_cultivos` — Tipos de cultivo
- `cultivos` — Cultivos base
- `parcelas` — Parcelas agrícolas

### Datos verificados para una fecha (`verificarDatosFecha`)
- **Datos de estación (estacion_dato)**
  - `whereDate('created_at', $fecha)->count()`
- **Pronósticos (forecast)**
  - `where('fecha_prediccion', $fecha)->where('fecha_solicita', $fecha)->count()`
- **Resúmenes existentes (resumen_temperaturas)**
  - `where('fecha', $fecha)->count()`

### Dependencias
- Facade: `Illuminate\Support\Facades\DB`
- Carbon: `Carbon\Carbon`

### Ejemplos
```bash
# Validar dependencias para ayer
php artisan app:verificar-dependencias-temperatura

# Validar dependencias para una fecha específica
php artisan app:verificar-dependencias-temperatura --fecha=2026-01-10
```

### Uso recomendado
Ejecutar este comando antes de correr procesos masivos de resumen de temperaturas para detectar:
- tablas faltantes,
- tablas vacías en entornos nuevos,
- ausencia de forecast o de datos de estaciones para la fecha objetivo.

---

## 3) `estaciones:verificar` — Diagnóstico de actividad de estaciones (últimos N días)

**Archivo:** `app/Console/Commands/VerificarEstacionesCommand.php`  
**Descripción:** Verificar estado y actividad de las estaciones.

### Firma
```bash
php artisan estaciones:verificar {--dias=7}
```

### Opciones
| Opción | Tipo | Default | Descripción |
|---|---:|---:|---|
| `--dias=` | int | 7 | Ventana de días hacia atrás (desde `now()`) para evaluar actividad. |

### Flujo (handle)
1. Obtiene `dias` y calcula:
   - `$fechaInicio = Carbon::now()->subDays($dias)`
2. Carga estaciones:
   - `Estacion::with('zonaManejos')->get()`
3. Por cada estación:
   - **Último dato** (dentro de la ventana):
     - `EstacionDato::where('estacion_id', $id)`
     - `where('created_at', '>=', $fechaInicio)`
     - `orderBy('created_at','desc')->first()`
   - **Cantidad de registros** (dentro de la ventana):
     - misma condición, `count()`
   - **Estado**
     - default: `Inactiva`
     - si `$registros > 0` → `Activa`
     - else si `$ultimoDato` → `Reciente`
   - **Zonas**
     - `$zonas = $estacion->zonaManejos->pluck('id')->implode(', ')`
     - si vacío → `Sin asignar`
4. Muestra una tabla con columnas:
   - `ID`, `UUID`, `Zonas`, `Último Dato`, `Registros`, `Estado`
5. Muestra un resumen:
   - conteos por estado (`Activa`, `Reciente`, `Inactiva`) y `Sin asignar`.

### Dependencias
- Modelos:
  - `App\Models\Estacion` (relación esperada: `zonaManejos`)
  - `App\Models\EstacionDato`
- Carbon.

### Ejemplos
```bash
# Últimos 7 días
php artisan estaciones:verificar

# Últimos 30 días
php artisan estaciones:verificar --dias=30
```

### Nota técnica (posible ajuste)
En el código actual, `ultimoDato` usa el mismo filtro temporal (`created_at >= $fechaInicio`) que `registros`.  
Esto hace que la condición:
```php
elseif ($ultimoDato) { $estado = 'Reciente'; }
```
sea prácticamente inalcanzable (si hay `ultimoDato`, entonces `registros` normalmente será > 0 y el estado quedará como `Activa`).  
Si se desea distinguir:
- **Activa** = tiene registros en la ventana,
- **Reciente** = tiene registros pero fuera de la ventana (o un “último dato” antiguo),
entonces `ultimoDato` debería calcularse **sin** el filtro `created_at >= $fechaInicio` (o con una ventana distinta).

---

## Flujo sugerido de diagnóstico (temperaturas)
1. `php artisan app:verificar-dependencias-temperatura --fecha=YYYY-MM-DD`
2. `php artisan app:verificar-datos-temperatura --fecha=YYYY-MM-DD`
3. Si aplica, ejecutar el proceso principal (cron/job) de resumen de temperaturas para esa fecha.
