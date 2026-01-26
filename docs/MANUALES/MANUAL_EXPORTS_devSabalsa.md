# Exports — Mediciones (Laravel Excel)

## Objetivo
Esta sección documenta los **Exports** ubicados en `app/Exports/` que generan archivos Excel a partir de datos de estaciones (`estacion_dato`) usando **Laravel Excel (Maatwebsite)**.

- **`MedicionesExportQuery`**: exporta **registros crudos** (una fila por registro en `estacion_dato`) para un rango de fechas y un conjunto de estaciones.
- **`MedicionesExportAllQuery`**: exporta un **resumen agregado por hora** (una fila por hora) para un conjunto de estaciones, calculando **MAX/MIN/AVG** para variables clave.

---

## Dependencias y contratos usados

Ambos exports implementan interfaces de `Maatwebsite\Excel\Concerns`:

- `FromQuery`: el export se alimenta desde un **Query Builder** (no desde una colección en memoria).
- `WithHeadings`: define encabezados de columnas.
- `WithChunkReading`: procesa el query en **chunks** (reduce uso de memoria).

Adicionalmente, `MedicionesExportAllQuery` usa:
- `WithMapping`: transforma cada fila antes de escribirla (formato/valores por defecto).
- `ShouldAutoSize`: autoajusta el ancho de columnas.
- `WithBatchInserts`: inserciones por lote (mejora rendimiento en exports grandes).
- `WithCalculatedFormulas`: indica que el writer debe calcular fórmulas (útil si en algún punto se agregan celdas con fórmulas).

> Paquete esperado: `maatwebsite/excel` (Laravel Excel).

---

## Uso típico desde Controller/Service

Ejemplo (orientativo) de descarga:

```php
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MedicionesExportQuery;
use App\Exports\MedicionesExportAllQuery;

public function exportMedicionesRaw(Request $request)
{
    $ids = $request->input('estaciones', []); // array de IDs
    $desde = $request->input('desde');        // string o Carbon parseable
    $hasta = $request->input('hasta');

    return Excel::download(
        new MedicionesExportQuery($ids, $desde, $hasta),
        'mediciones_raw.xlsx'
    );
}

public function exportMedicionesPorHora(Request $request)
{
    $ids = $request->input('estaciones', []);
    return Excel::download(
        new MedicionesExportAllQuery($ids),
        'mediciones_por_hora.xlsx'
    );
}
```

---

# 1) `MedicionesExportQuery` (registros crudos)

## Archivo
- `app/Exports/MedicionesExportQuery.php`

## Propósito
Exporta los registros de `estacion_dato` filtrando por:
- Lista de estaciones (`estacion_id IN (...)`)
- Rango de fechas (`created_at BETWEEN desde AND hasta`)

## Constructor
```php
public function __construct($ids, $desde, $hasta)
```

### Parámetros
- `$ids`: arreglo (o colección) de IDs de estación (`estacion_id`).
- `$desde`: fecha/hora inicio (string/Carbon).
- `$hasta`: fecha/hora fin (string/Carbon).

> Recomendación: normalizar `$desde`/`$hasta` a `Carbon` o strings con formato `Y-m-d H:i:s` antes de instanciar el export.

## Query (FromQuery)
```php
return EstacionDato::query()
  ->whereIn('estacion_id', $this->ids)
  ->whereBetween('created_at', [$this->desde, $this->hasta]);
```

### Notas técnicas
- El query no especifica `orderBy`; el orden dependerá del motor/índices.  
  Si se requiere orden estable, agregar `orderBy('created_at')` en el export o antes (según el diseño).
- Para rendimiento, se recomienda índice compuesto:
  - `(estacion_id, created_at)` en `estacion_dato`.

## Encabezados (`headings()`)
El export define encabezados (una columna por campo). En el código actual, incluye:

- `id`
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
- `created_at`
- `updated_at`
- `deleted_at`

> Importante: al ser `FromQuery`, la **selección real** de columnas dependerá del `SELECT` del query.  
> Como no hay un `select([...])` explícito, Eloquent selecciona `*` y el writer usará el mapeo default por propiedad/array.  
> Si el objetivo es garantizar el orden/consistencia, se recomienda agregar un `select([...])` alineado con `headings()`.

## Chunk size
```php
public function chunkSize(): int { return 1000; }
```

### Implicaciones
- Reduce uso de memoria en exports grandes.
- Si el servidor es limitado, considerar bajar el chunk (p. ej. 500).

---

# 2) `MedicionesExportAllQuery` (agregado por hora)

## Archivo
- `app/Exports/MedicionesExportAllQuery.php`

## Propósito
Exporta un resumen **por hora** a partir de `estacion_dato` para un conjunto de estaciones, calculando:

- `fecha` (agrupación por hora)
- `MAX / MIN / AVG` para:
  - `temperatura`
  - `co2`
  - `temperatura_suelo`
  - `conductividad_electrica`
  - `ph`
  - `nit`
  - `phos`
  - `pot`

Este export es útil para reportes en los que no se requiere el dato por minuto/registro, sino **tendencias horarias**.

## Constructor
```php
public function __construct($ids)
```

### Parámetro
- `$ids`: arreglo (o colección) de IDs de estación.

## Query (FromQuery)
### Selección agregada (MySQL/MariaDB)
Se usa un `selectRaw()` con `DATE_FORMAT` para agrupar por hora:

- `DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as fecha`
- `MAX(...) as max_*`
- `MIN(...) as min_*`
- `ROUND(AVG(...), 2) as avg_*`

Luego:
- `whereIn('estacion_id', $this->ids)`
- `groupBy('fecha')`
- `orderBy('fecha')`

> Compatibilidad: `DATE_FORMAT(...)` es específico de MySQL/MariaDB.  
> Si se cambia de motor (PostgreSQL/SQL Server), esta parte debe adaptarse.

### Consideraciones de zona horaria
- `created_at` normalmente se almacena en UTC.  
- `DATE_FORMAT(created_at, ...)` agrupa según la zona horaria configurada en la conexión DB.  
  Si los reportes deben agruparse en zona local, definir estrategia consistente (DB timezone vs conversión previa).

## Encabezados (`headings()`)
Columnas definidas (en orden):

1. Fecha (Hora)
2. Temperatura Máxima (°C)
3. Temperatura Mínima (°C)
4. Temperatura Promedio (°C)
5. CO2 Máximo (ppm)
6. CO2 Mínimo (ppm)
7. CO2 Promedio (ppm)
8. Temperatura Suelo Máxima (°C)
9. Temperatura Suelo Mínima (°C)
10. Temperatura Suelo Promedio (°C)
11. Conductividad Eléctrica Máxima (Ds/m)
12. Conductividad Eléctrica Mínima (Ds/m)
13. Conductividad Eléctrica Promedio (Ds/m)
14. pH Máximo
15. pH Mínimo
16. pH Promedio
17. Nitrógeno Máximo (ppm)
18. Nitrógeno Mínimo (ppm)
19. Nitrógeno Promedio (ppm)
20. Fósforo Máximo (ppm)
21. Fósforo Mínimo (ppm)
22. Fósforo Promedio (ppm)
23. Potasio Máximo (ppm)
24. Potasio Mínimo (ppm)
25. Potasio Promedio (ppm)

## Mapping (`map($row)`)
- Convierte `fecha` a `Y-m-d H:i:s`.
- Para cada métrica usa:
  - valor real si existe
  - `'N/A'` si es `null`

Ejemplo conceptual:
- `max_temperatura` → `$row->max_temperatura ?? 'N/A'`

### Implicaciones
- Si hay horas sin datos, no aparecen como filas (porque el query solo agrupa lo existente).
- Valores `null` pueden ocurrir si la variable no existe en algunos registros.

## Chunk y batch
```php
chunkSize(): 100   // reducido para ahorrar memoria
batchSize(): 50    // inserciones por lote pequeñas
```

### Motivo
- Al agrupar por hora, el query puede seguir retornando muchos registros si el rango temporal es grande.
- Chunk pequeño reduce picos de memoria.
- Batch pequeño evita escrituras masivas en memoria del writer.

---

## Recomendaciones de mantenimiento

### Consistencia entre headings y datos exportados
- `MedicionesExportQuery` define headings de muchos campos, pero no fuerza `select()` ni `map()`.  
  Si cambian columnas en `estacion_dato`, el export puede desalinearse respecto a headings esperados.
  - Recomendación: agregar `->select([...])` o implementar `WithMapping`.

### Rendimiento
- En tablas grandes, asegurar índices:
  - `estacion_dato(estacion_id, created_at)`
- Para `MedicionesExportAllQuery`, el `DATE_FORMAT` puede impedir uso óptimo de índices; si se requiere performance mayor:
  - considerar agrupar por “hour bucket” con expresiones indexables, o materializar agregados.

### Validaciones de entrada
- Antes de instanciar exports, validar:
  - que `$ids` no esté vacío,
  - formato de fechas (`desde/hasta`),
  - rango máximo permitido (para evitar exports excesivos).

---

## Archivos documentados
- `app/Exports/MedicionesExportQuery.php`
- `app/Exports/MedicionesExportAllQuery.php`
