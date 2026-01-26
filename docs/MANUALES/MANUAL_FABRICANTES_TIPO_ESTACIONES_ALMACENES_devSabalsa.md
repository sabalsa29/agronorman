# Documentación de Catálogos: **Fabricantes**, **Tipo de Estaciones** y **Almacenes**

Este documento describe cómo funcionan (y cómo mantener) las secciones:

- **Fabricantes**
- **Tipo de estaciones**
- **Almacenes**

> Estado actual: son módulos **CRUD** (Crear, Listar, Editar, Eliminar) implementados en Laravel mediante controladores tipo *resource*.

---

## 1) Patrón base (CRUD en Laravel)

En estos módulos se repite el mismo flujo:

1) **Ruta** apunta al controlador (generalmente `Route::resource(...)`)  
2) **Controller** ejecuta la acción (`index/create/store/edit/update/destroy`)  
3) **Modelo (Eloquent)** consulta o persiste datos (`::all()`, `save()`, `delete()`)  
4) **Vista Blade** renderiza pantallas (index/create/edit)

**Modelo mental:**
```
Ruta -> Controller -> Model (DB) -> view() / redirect()->route()
```

**Nota técnica:**  
En los 3 controladores se usa **Route Model Binding**, por eso en métodos como `edit(Fabricante $fabricante)` Laravel resuelve el registro automáticamente desde la URL.

---

## 2) Sección: **Fabricantes**

### 2.1 ¿Qué es?
Catálogo de **fabricantes de estaciones meteorológicas**.

### 2.2 Backend (estructura real)
- **Controlador:** `FabricanteController`
- **Modelo:** `App\Models\Fabricante`
- **Vistas:**
  - `fabricantes.index`
  - `fabricantes.create`
  - `fabricantes.edit`
- **Ruta usada en redirects:**
  - `fabricantes.index`

### 2.3 Campos que administra
En el controlador se gestionan estos campos:

- `nombre`
- `status`

**Comportamiento de `status`:**
- Al **crear**, se asigna fijo: `status = 1`
- Al **editar**, se toma desde request: `$request->status`

> Implicación: el formulario de edición debe enviar `status` de forma consistente (por ejemplo 0/1 o true/false).

### 2.4 Acciones del controlador (CRUD)

#### `index()`
- Consulta: `Fabricante::all()`
- Renderiza: `fabricantes.index`
- Variables UI:
  - `section_name = "Fabricantes"`
  - `section_description = "Fabricantes de estaciones meteorológicas"`
  - `list = compact('fabricantes')`

#### `create()`
- Renderiza: `fabricantes.create`
- UI:
  - `"Crear fabricante"`

#### `store(Request $request)`
- Crea:
  - `nombre = $request->nombre`
  - `status = 1`
- Guarda: `$fabricante->save()`
- Redirige a `fabricantes.index` con mensaje de éxito

#### `edit(Fabricante $fabricante)`
- Renderiza `fabricantes.edit` pasando `fabricante`

#### `update(Request $request, Fabricante $fabricante)`
- Actualiza:
  - `nombre = $request->nombre`
  - `status = $request->status`
- Guarda y redirige

#### `destroy(Fabricante $fabricante)`
- Elimina: `$fabricante->delete()`
- Redirige con éxito

### 2.5 Puntos “difíciles” / recomendaciones
- **Validación:** actualmente no se valida (`Request` directo). Recomendado:
  - `nombre` requerido y único
  - `status` tipo boolean/int permitido
- **Borrado:** si Fabricante está referenciado por estaciones, podría fallar por FK.
  - Considera **soft delete** o bloquear borrado si tiene dependencias.

---

##  3) Sección: **Tipo de Estaciones**

### 3.1 ¿Qué es?
Catálogo de **tipos de estación**. Incluye el campo `tipo_nasa`, útil para clasificar / mapear tipos compatibles con integraciones externas (o nomenclatura de origen).

### 3.2 Backend (estructura real)
- **Controlador:** `TipoEstacionController`
- **Modelo:** `App\Models\TipoEstacion`
- **Vistas:**
  - `tipo_estacion.index`
  - `tipo_estacion.create`
  - `tipo_estacion.edit`
- **Ruta usada en redirects:**
  - `tipo_estacion.index`

### 3.3 Campos que administra
- `nombre`
- `tipo_nasa`
- `status`

**Comportamiento de `status`:**
- En `store()` y `update()` se guarda desde request: `$request->status` (sin conversión a boolean).

> Implicación: el formulario debe enviar un valor consistente (por ejemplo `0/1`), porque Laravel no lo normaliza aquí.

### 3.4 Acciones del controlador (CRUD)

#### `index()`
- Consulta: `TipoEstacion::all()`
- Renderiza: `tipo_estacion.index`
- Variables:
  - `section_name = "Tipo de Estación"`
  - `section_description = "Listado de tipos de estaciones"`
  - `list = $list`

#### `create()`
- Renderiza: `tipo_estacion.create`

#### `store(Request $request)`
- Crea:
  - `nombre = $request->nombre`
  - `tipo_nasa = $request->tipo_nasa`
  - `status = $request->status`
- Guarda y redirige

#### `edit(TipoEstacion $tipo_estacion)`
- Renderiza `tipo_estacion.edit` pasando:
  - `tipoEstacion` (nota el nombre de la variable en la vista)

#### `update(Request $request, TipoEstacion $tipo_estacion)`
- Actualiza los 3 campos y guarda

#### `destroy(TipoEstacion $tipo_estacion)`
- Elimina el registro y redirige

### 3.5 Puntos “difíciles” / recomendaciones
- **Validación recomendada:**
  - `nombre` requerido y único
  - `tipo_nasa` requerido u opcional, pero con formato definido (string/int)
  - `status` restringido a `0/1`
- **Compatibilidad con UI:** si el input `status` es checkbox, en HTML no siempre se envía si está desmarcado.
  - Si es checkbox, conviene usar `$request->boolean('status')` como en Almacenes, o normalizar en backend.

---

## 4) Sección: **Almacenes**

### 4.1 ¿Qué es?
Catálogo de **almacenes** (ubicaciones lógicas/físicas para inventario o recursos del sistema).

### 4.2 Backend (estructura real)
- **Controlador:** `AlmacenController`
- **Modelo:** `App\Models\Almacen`
- **Vistas:**
  - `almacenes.index`
  - `almacenes.create`
  - `almacenes.edit`
- **Ruta usada en redirects:**
  - `almacenes.index`

> Nota: el parámetro del modelo en métodos `edit/update/destroy` se llama `$almacene` (singular “irregular”).

### 4.3 Campos que administra
- `nombre`
- `status`

**Comportamiento de `status`:**
- En `store()` y `update()` se usa: `$request->boolean('status')`
  - Esto convierte valores comunes a boolean (true/false).
  - Si el campo no viene en el request, devuelve `false`.

### 4.4 Acciones del controlador (CRUD)

#### `index()`
- Consulta: `Almacen::all()`
- Renderiza `almacenes.index` con:
  - `section_name = "Almacenes"`
  - `section_description = "Listado de almacenes"`
  - `list = $list`

#### `create()`
- Renderiza `almacenes.create`

#### `store(Request $request)`
- Crea:
  - `nombre = $request->nombre`
  - `status = $request->boolean('status')`
- Guarda y redirige

#### `edit(Almacen $almacene)`
- Renderiza `almacenes.edit` pasando variable:
  - `almacen` (en la vista)

#### `update(Request $request, Almacen $almacene)`
- Actualiza nombre y status (boolean) y guarda

#### `destroy(Almacen $almacene)`
- Elimina el registro y redirige

### 4.5 Puntos “difíciles” / recomendaciones
- **Validación:** agregar reglas (nombre requerido y único).
- **Soft delete:** si almacenes se referencian (movimientos, inventario, etc.), evitar hard delete.

---

## 5) Rutas sugeridas (estándar)

Se manejan estos módulos como resources:

```php
Route::resource('fabricantes', FabricanteController::class);
Route::resource('tipo_estacion', TipoEstacionController::class);
Route::resource('almacenes', AlmacenController::class);
```

---

## 6) Checklist de QA rápido (para producción)

- Crear con datos válidos e inválidos (validación).
- Confirmar que `status` se guarda como esperas:
  - Fabricantes: crea siempre `1` --De momento, revisar
  - Tipo de estación: depende de `request->status`
  - Almacenes: depende de `boolean('status')`
- Editar: revisar que los formularios envíen `status` correctamente.
- Eliminar: verificar si hay dependencias y si no rompe por FK.

---

## 7) Mejoras recomendadas (si quieres robustecer)

1) Migrar validación a `FormRequest`:
   - `StoreFabricanteRequest`, `UpdateFabricanteRequest`, etc.
2) Unificar manejo de `status`:
   - usar boolean en todos, o `0/1` en todos, pero consistente.
3) Agregar índices únicos:
   - `fabricantes.nombre`, `tipo_estaciones.nombre`, `almacenes.nombre` (si aplica)
4) SoftDeletes si hay relaciones aguas abajo.

MANUAL_FABRICANTES_TIPO_ESTACIONES_ALMACENES