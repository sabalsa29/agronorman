# Documentación de Catálogos (Laravel): Cultivos, Plagas, Enfermedades, Etapas Fenológicas y Texturas de Suelo

Este documento describe **cómo funcionan** (y cómo mantener) las secciones:

- **Etapas fenológicas**
- **Cultivos**
- **Plagas**
- **Texturas de suelo**
- **Enfermedades**

La documentación está basada en los controladores adjuntos:

- `EtapaFenologicaController.php`
- `CultivoController.php`
- `PlagaController.php`
- `TipoSueloController.php`
- `EnfermedadesController.php`

> Alcance actual: estas secciones son principalmente **CRUDs**, con excepciones:
> - **Cultivos**: tiene edición avanzada (subida de imagen/icono) y UI para etapas fenológicas.
> - **Texturas de suelo (TipoSuelo)**: no es un CRUD clásico; es una **edición masiva** de umbrales por registro.
> - **Enfermedades**: además del CRUD, incluye **configuración por tipo de cultivo** y un endpoint **JSON** para alimentar el frontend.

---

## 1) Arquitectura base 

### 1.1 Flujo básico de un CRUD en Laravel
1) **Ruta** recibe petición  
2) **Controller** ejecuta acción (`index/create/store/edit/update/destroy`)  
3) **Validación** (aquí se hace inline con `$request->validate(...)`)  
4) **Modelo Eloquent** consulta/guarda en BD  
5) **Vista Blade** o **JSON** responde al cliente

### 1.2 Convención visual 
En la mayoría de vistas se envían:
- `section_name`
- `section_description`

Eso ayuda a que la UI sea consistente (títulos, breadcrumb, encabezados, etc.).

### 1.3 Lo que “se complica” en catálogos reales
- **Relaciones N:M** (ej. Plagas ↔ Cultivos)
- **Pivotes con campos extra** (ej. Enfermedades ↔ Tipos de cultivo con umbrales/riesgos)
- **Subida/gestión de archivos** (ej. Cultivos: imagen + icono)
- **Procesamiento por periodo/estaciones** (ej. JSON de Enfermedades con zona_manejo → estaciones → datos por hora)

---

## 2) Etapas fenológicas

### 2.1 ¿Qué hace esta sección?
Administra el catálogo de **etapas fenológicas** (etapas de desarrollo del cultivo).  
Actualmente es un CRUD simple con filtro de “activos”.

### 2.2 Controlador
**Archivo:** `EtapaFenologicaController.php`  
**Clase:** `EtapaFenologicaController`

### 2.3 Vistas que usa (Blade)
- `etapasfenologicas.index`
- `etapasfenologicas.create`
- `etapasfenologicas.edit`

### 2.4 Acciones y comportamiento real

#### `index()`
- Consulta solo etapas **activas** y con `nombre` no nulo:
  - `EtapaFenologica::where('status', 1)->whereNotNull('nombre')->get()`
- Devuelve la vista con `list`.

#### `create()`
- Solo devuelve la vista de creación.

#### `store(Request $request)`
- Valida:
  - `nombre` requerido, string, max 255
- Crea usando **mass assignment**:
  - `EtapaFenologica::create($request->all())`

#### `edit(EtapaFenologica $etapasfenologica)`
- Usa **Route Model Binding**.
- Envía a vista la etapa en la variable `etapa`.

#### `update(Request $request, EtapaFenologica $etapasfenologica)`
- Valida `nombre`
- Actualiza con `update($request->all())`

#### `destroy(EtapaFenologica $etapasfenologica)`
- Borra el registro (`delete()`)

### 2.5 Puntos / recomendaciones 
- **Mass assignment** (`$request->all()`): asegúrate de que el modelo `EtapaFenologica` tenga bien definidos sus `$fillable` (o usa `$request->only([...])`). Agrega nuevos campos si existen modificaciones en las tablas relacionadas a EtapaFenologica.
- Si esta tabla está referenciada por otras (ej. etapas por cultivo), considera **soft deletes** o bloquear borrados con dependencias.

---

## 3) Cultivos

### 3.1 ¿Qué hace esta sección?
Administra el catálogo de **cultivos**, con una edición que permite:
- Actualizar datos base (`nombre`, `descripcion`, etc.)
- Subir/actualizar **imagen** e **icono**
- Mostrar una lista de **etapas fenológicas** (para UI)

### 3.2 Controlador
**Archivo:** `CultivoController.php`  
**Clase:** `CultivoController`  
**Models usados:** `Cultivo`, `EtapaFenologica`

### 3.3 Vistas
- `cultivos.index`
- `cultivos.create`
- `cultivos.edit`

> Nota: `store()` y `show()` están presentes pero actualmente **sin implementación**.

### 3.4 Acciones y comportamiento real

#### `index()`
- Lista todos los cultivos:
  - `Cultivo::all()`

#### `create()`
- Devuelve formulario de creación.

#### `store(Request $request)`
- Actualmente está en blanco (`//`).  
  Si ya tienes la UI, aquí faltaría:
  - validación
  - create
  - redirect

#### `edit(Cultivo $cultivo)`
- Carga etapas fenológicas activas (`status = 1`):
  - `EtapaFenologica::where('status', 1)->whereNotNull('nombre')->get()`
- Envía a vista:
  - `etapas_fenologicas`
  - `cultivo`
  - `cultivo_id`

#### `update(Request $request, Cultivo $cultivo)`
Validación inline:
- `nombre` requerido
- `descripcion` opcional
- `temp_base_calor` numérico opcional
- `tipo_vida` entero opcional
- `imagen` e `icono` como archivos de imagen, con mimes y tamaño max
- `etapas_fenologicas` como array opcional + `exists:etapa_fenologicas,id`

Actualización:
- Asigna campos simples al modelo.
- Maneja archivos:
  - Si suben `imagen`: borra anterior en `Storage::disk('public')` y guarda en `cultivos/imagenes`
  - Si suben `icono`: borra anterior y guarda en `cultivos/iconos`
- Guarda y redirige a:
  - `route('cultivos.edit', $cultivo)` con success

#### `destroy(Cultivo $cultivo)`
- Elimina (`delete()`) y redirige a `cultivos.index`.

### 3.5 Archivos + etapas
1) **Archivos (public disk)**
   - Debe existir `storage:link` para servir archivos:
     ```bash
     php artisan storage:link
     ```
   - La UI debe apuntar a `storage/<path>` si usas `public` disk.

2) **Etapas fenológicas**
   - El controlador **valida** `etapas_fenologicas`, pero **no las persiste**.
   - Si la intención es relacionarlas, faltaría algo como:
     - `sync(...)` hacia una pivote (N:M) o actualizar una tabla de configuración.

> Recomendación: documentar (o implementar) la relación real `Cultivo ↔ EtapaFenologica` para que la UI no “parezca guardar” pero no guarde. Pendiente y se recomienda como mejora continua.

---

## 4) Plagas

### 4.1 ¿Qué hace esta sección?
Administra el catálogo de **plagas** y su asociación con **cultivos** (N:M).

### 4.2 Controlador
**Archivo:** `PlagaController.php`  
**Clase:** `PlagaController`  
**Models usados:** `Plaga`, `Cultivo`

### 4.3 Vistas
- `plagas.index`
- `plagas.create`
- `plagas.edit`

### 4.4 Acciones y comportamiento real

#### `index()`
- Carga plagas con sus cultivos relacionados:
  - `Plaga::with('cultivos')->get()`
- Construye un campo calculado por cada plaga:
  - `nombres_cultivos = "Cultivo1, Cultivo2, ..."`
- Devuelve `plagas.index` con:
  - `"list" => compact('plagues')`
  > Ojo: aquí `list` contiene un array con clave `plagues`. En la vista se accede como `$list['plagues']` (o ajusta para pasar `plagues` directo).

#### `create()`
- Carga todos los cultivos:
  - `Cultivo::all()`
- Envía a vista para poder seleccionar cultivos al crear la plaga.

#### `store(Request $request)`
- Crea plaga asignando campos manualmente:
  - `nombre`, `slug` (`Str::slug`), `descripcion`, `unidades_calor_ciclo`, `umbral_min`, `umbral_max`, `imagen`
- Relación N:M:
  - `$plaga->cultivos()->sync($request['cultivo_id']);`
- Redirige a `route('plaga.index')`.

#### `edit(Plaga $plaga)`
- Carga cultivos + cultivos seleccionados:
  - `$plaga->cultivos()->pluck('cultivo_id')->toArray()`
- Envía `selectedCultivos` para que la UI marque checks/selected.

#### `update(Request $request, Plaga $plaga)`
- Actualiza campos (incluye regenerar slug).
- Sincroniza cultivos con `sync($request['cultivo_id'])`
- Redirige a `plaga.index`

#### `destroy(Plaga $plaga)`
- Elimina (`delete()`) y redirige.

### 4.5 Relación N:M y consistencia
- Recomendaciones:
  - Validar que `cultivo_id` sea array y que exista en BD (`exists:cultivos,id`).
  - Agregar índice único en pivote (si no existe) para evitar duplicados.

---

## 5) Texturas de suelo (Tipos de suelo / umbrales)

### 5.1 ¿Qué hace esta sección?
Permite editar los **umbrales** por cada tipo de suelo.

A diferencia de los demás catálogos, aquí el flujo es:
- Ver una tabla con todos los `TipoSuelo`
- Editar valores por fila (bajo/óptimo/alto)
- Guardar todo de golpe

### 5.2 Controlador
**Archivo:** `TipoSueloController.php`  
**Clase:** `TipoSueloController`  
**Model usado:** `TipoSuelo`

### 5.3 Vista
- `tiposuelo.index`

### 5.4 Acciones y comportamiento real

#### `index()`
- Obtiene todos los tipos de suelo:
  - `TipoSuelo::all()`
- Devuelve la vista con `suelos`.

#### `updateSuelos(Request $request)`
- Obtiene todos los registros y actualiza campos por ID:
  - `bajo`
  - `optimo_min`
  - `optimo_max`
  - `alto`
- Espera inputs con este patrón:
  - `bajo[ID]`, `optimo_min[ID]`, `optimo_max[ID]`, `alto[ID]`
  (en el request se leen como: `"bajo.{$suelo->id}"`, etc.)
- Guarda cada registro y redirige a:
  - `route('textura-suelo.index')` con success

### 5.5 Edición masiva segura
Recomendaciones para robustez:
- Validar que esos campos sean numéricos (`nullable|numeric`)
- Usar **transacción** para evitar que se guarden “a medias”
- Si el catálogo crece, optimizar guardado (update en lote / query builder), recomendado.

---

## 6) Enfermedades (CRUD + configuración por cultivo + JSON)

### 6.1 ¿Qué hace esta sección?
1) CRUD de catálogo de enfermedades (`Enfermedades`)
2) Configurar **parámetros de riesgo** por **tipo de cultivo** (tabla `tipo_cultivos_enfermedades`)
3) Endpoint JSON para frontend: calcula estatus por hora con datos de estaciones y umbrales de riesgo

### 6.2 Controlador
**Archivo:** `EnfermedadesController.php`  
**Clase:** `EnfermedadesController`

**Models usados (según controlador):**
- `Enfermedades`
- `TipoCultivos`
- `TipoCultivosEnfermedad` (relación enfermedad ↔ tipo cultivo con campos extra)
- `Cultivo` (aparece importado, pero el flujo principal usa `TipoCultivos`)

> Nota importante: en este módulo aparecen **dos conceptos**:
> - `Cultivo` (catálogo general)
> - `TipoCultivos` (catálogo usado para parametrizar riesgo en enfermedades)
>
> Si ambos representan “lo mismo”, conviene unificar. Si no, documenta claramente su diferencia (p. ej. “tipo de cultivo” para motor de enfermedades).

---

### 6.3 CRUD de enfermedades

#### `index()`
- Carga enfermedades con sus tipos de cultivo:
  - `Enfermedades::with('tipoCultivos')->get()`
- Genera una cadena:
  - `tipo_cultivos_list = "Tipo1, Tipo2, ..."`
- Envía a `enfermedades.index`

#### `create()`
- Devuelve `enfermedades.create`

#### `store(Request $request)`
- Valida:
  - `nombre` único en `enfermedades`
  - `status` boolean
- Crea:
  - `slug` con `Str::slug`
- Redirige a `enfermedades.index`

#### `edit(Enfermedades $enfermedade)`
- Devuelve `enfermedades.edit` con el registro.

#### `update(Request $request, Enfermedades $enfermedade)`
- Valida `nombre` único ignorando el ID actual.
- Actualiza `nombre`, `slug`, `status`.

#### `destroy(Enfermedades $enfermedade)`
- Borra el registro y redirige.

---

### 6.4 Configuración de “Tipos de cultivo” por enfermedad (pivote con campos extra)

Aquí se administra la tabla de configuración **`tipo_cultivos_enfermedades`** mediante el modelo `TipoCultivosEnfermedad`.
Campos que el controlador maneja:
- `riesgo_humedad`
- `riesgo_temperatura`
- `riesgo_humedad_max`
- `riesgo_temperatura_max`
- `riesgo_medio` (int)
- `riesgo_mediciones` (int)

#### `cultivosIndex(Enfermedades $enfermedad)`
- Lista la configuración existente por enfermedad:
  - `TipoCultivosEnfermedad::with('enfermedad')->where('enfermedad_id', ...)->get()`
- Vista:
  - `enfermedades.enfermedad_cultivo.index`

#### `cultivosCreate(Enfermedades $enfermedad)`
- Carga `TipoCultivos::all()` para elegir a cuáles aplicar la enfermedad.
- Vista:
  - `enfermedades.enfermedad_cultivo.create`

#### `cultivosStore(Request $request)`
- Valida:
  - `enfermedad_id` existe
  - `cultivo_ids` array con 1+ elementos (en tabla `tipo_cultivos`)
  - parámetros de riesgo opcionales
- **Estrategia actual:** elimina asociaciones existentes de esa enfermedad y crea nuevas.
  - Esto significa: “lo que selecciono hoy reemplaza todo lo anterior”.
- Crea 1 registro por tipo cultivo seleccionado.

> Si más adelante se necesita “agregar sin borrar”, se cambiaría esta estrategia. IMPORTANTE

#### `cultivosEdit(Enfermedades $enfermedad, TipoCultivos $tipoCultivo...........)`
- Busca el registro exacto (enfermedad + tipo cultivo).
- Vista:
  - `enfermedades.enfermedad_cultivo.edit`

#### `cultivosUpdate(Request $request, Enfermedades $enfermedad, TipoCultivos $tipoCultivo)`
- Valida parámetros
- Hace `updateOrCreate` con claves:
  - `enfermedad_id`, `tipo_cultivo_id`
- Actualiza umbrales y redirige.

#### `cultivosDestroy(Enfermedades $enfermedad, TipoCultivos $tipoCultivo)`
- Elimina la relación específica (no la enfermedad).

---

### 6.5 Endpoint JSON: `jsonEnfermedades(Request $request)`
Este método alimenta el frontend con datos calculados.

#### Parámetros que recibe
- `enfermedad_id`
- `tipo_cultivo_id`
- `zona_manejo_id`..................
- `periodo` (código numérico)
- `startDate`, `endDate` (fechas personalizadas)

#### Fuentes de datos (tablas consultadas en el controlador)
- `zona_manejos_estaciones` → obtener `estacion_id` de una zona de manejo
- `estacion_dato` → obtener datos por estación (temperatura, humedad) y agregarlos por hora
- `tipo_cultivos_enfermedades` → obtener umbrales de riesgo por enfermedad y tipo cultivo

#### Lógica de cálculo (resumen)
1) Se obtienen las estaciones asociadas a la zona de manejo.
2) Se calcula el rango de fechas:
   - si viene `periodo`: se usa `calcularPeriodoExacto($periodo)` (hora exacta)
   - si no: usa `startDate/endDate`, con defaults (últimas 24h).
3) Se consultan datos por hora y se calculan promedios (temperatura/humedad).
4) Se determina `estatus` por hora:
   - usa umbrales (`riesgo_humedad`, `riesgo_humedad_max`, `riesgo_temperatura`, `riesgo_temperatura_max`)
   - evalúa con `verificarCondicionesRiesgo(...)`
   - genera el estatus (por ejemplo, “Sin riesgo” vs “con condiciones favorables”, según tu UI)

#### Helper: `verificarCondicionesRiesgo(...)`
Valida que:
- humedad esté dentro del rango `[riesgo_humedad, riesgo_humedad_max]`
- temperatura esté dentro del rango `[riesgo_temperatura, riesgo_temperatura_max]`
y regresa `true` solo si ambas se cumplen.

---

### 6.6 Periodos exactos: `calcularPeriodoExacto($periodo)`
- Redondea “fin” a la hora actual exacta (`startOfHour()`).
- Calcula inicio restando horas según el código:
  - 1: 24h
  - 2: 48h
  - 3: 168h (1 semana)
  - 4: 336h (2 semanas)
  - 5: 720h (≈ 30 días)
  - 6: 1440h (≈ 60 días)
  - 7: 4320h (≈ 180 días)
  - 8: 8760h (≈ 365 días)
  - 9: usa `startDate` y `endDate` desde la request (si existen)
- Retorna:
  - `['Y-m-d H:00:00', 'Y-m-d H:00:00']` (inicio/fin)

---

## 7) Checklist rápido para evitar errores en producción

- **Validación**
  - No confíes en `$request->all()` si tu modelo no tiene `$fillable` controlado.
- **Borrados**
  - Si un catálogo está referenciado, prefiere soft delete o restringe el borrado.
- **Relaciones N:M**
  - Usa `sync([...])` en transacción si guardas más cosas.
- **Archivos**
  - Borra archivos anteriores para no acumular basura
  - Asegura `storage:link`
- **Texturas de suelo**
  - Protege con validación numérica y transacciones si es crítico.
- **Enfermedades JSON**
  - Revisa que el rango de fechas y el timezone sean consistentes
  - Si el frontend manda `periodo`, usa `calcularPeriodoExacto()` para no “cortar” horas

