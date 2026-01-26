# Documentación de la sección **Usuarios** (**Clientes**)

En el sistema, la sección que visualmente aparece como **“Usuarios”** corresponde en realidad al catálogo/gestión de **Clientes**.

- **Entidad real (backend / BD):** `Clientes`
- **Controlador:** `ClientesController`
- **Vistas:** `resources/views/clientes/*`
- **Nombre de sección usado en logs:** `clientes`

> Importante: en varias pantallas el texto dice “Usuario(s)”, pero los registros gestionados aquí son **clientes** (empresa/ubicación/teléfono, etc.).

---

## 1) ¿Qué hace esta sección?

Esta sección permite:

- Listar clientes (con reglas por rol)
- Crear, editar y eliminar clientes (solo **Super Admin**)
- Ver detalles de un cliente (vista show)
- Asignar **grupos** a un cliente (solo **Super Admin**)

Además, cada acción relevante se registra en un **log de auditoría** mediante el trait `LogsPlatformActions`.

---

## 2) Acceso por roles (lo más importante)

El controlador aplica reglas de acceso desde el inicio, usando `Auth::user()` y el método `$user->isSuperAdmin()`.

### 2.1 Listado (`index`)
El listado cambia según el rol del usuario autenticado:

- **Super Admin**
  - Ve **todos** los clientes (`Clientes::all()`)
  - Se registra el evento de “ver lista” en el log, incluyendo `total_clientes`
  - Renderiza la vista: `clientes.index` con:
    - `section_name = "Usuarios"`
    - `section_description = "Lista de Usuarios"`
    - `list = Clientes::all()`
 
- **Administrador** (`role_id == 2`)
  - Si tiene `cliente_id`, **redirige** a:
    - `route('usuarios.index', ['id' => $user->cliente_id])`
  - Esto sugiere que existe otro módulo “usuarios” que lista/gestiona usuarios “internos” por cliente.

- **Cliente** (`role_id == 3`)
  - Solo ve su propio cliente: `Clientes::where('id', $user->cliente_id)->get()`
  - Renderiza `clientes.index` con:
    - `section_name = "Mi Cliente"`
    - `section_description = "Información de mi cliente"`

- Si no hay sesión o no existe `cliente_id` para roles que lo requieren:
  - `abort(403, ...)`

--- 

## 3) Datos que se administran (campos del cliente)

En `store`/`update` el controlador gestiona estos campos:

- `nombre`
- `empresa`
- `ubicacion`
- `telefono`
- `status` (por defecto `1` al crear)

> La validación se delega a `StoreClientesRequest` y `UpdateClientesRequest`.

---

## 4) Endpoints y acciones del controlador (CRUD + grupos)

> El controlador sigue el patrón típico de **Resource Controller**, más dos métodos extra para grupos.

### 4.1 `index()`
**Objetivo:** mostrar lista de clientes, variando por rol.

- Super Admin: lista completa
- Admin (role 2): redirección a `usuarios.index`
- Cliente (role 3): lista filtrada por `cliente_id`

**Vista:** `clientes.index`

---

### 4.2 `create()`
**Objetivo:** mostrar formulario para crear cliente.

- **Permiso:** solo Super Admin
- **Vista:** `clientes.create`
- Títulos UI:
  - `"Nuevo Usuario"`
  - `"Crear un nuevo usuario"`

---

### 4.3 `store(StoreClientesRequest $request)`
**Objetivo:** crear un cliente.

- **Permiso:** solo Super Admin
- **Acción principal:** `Clientes::create([...])`
- `status` usa `request->status ?? 1`

**Auditoría (log):**
- Se registra acción `crear` en sección `clientes`
- Guarda:
  - `datosNuevos` (campos relevantes del cliente)
  - `datosAdicionales`: `empresa`, `ubicacion`

**Salida:**
- OK: `redirect()->route('clientes.index')->with('success', 'Usuario creado exitosamente.')`
- Error: `redirect()->back()->with('error', ...)->withInput()`

---

### 4.4 `show(Clientes $cliente)`
**Objetivo:** ver detalles del cliente.

- Registra acción `ver` en logs
- **Vista:** `clientes.show`
- Texto UI:
  - `"Detalles del Usuario"`
  - `"Información detallada del usuario"`

---

### 4.5 `edit(Clientes $cliente)`
**Objetivo:** mostrar formulario de edición.

- **Permiso:** solo Super Admin
- **Vista:** `clientes.edit`
- Pasa variable `cliente`

---

### 4.6 `update(UpdateClientesRequest $request, Clientes $cliente)`
**Objetivo:** actualizar datos del cliente.

- **Permiso:** solo Super Admin
- Guarda datos anteriores para log (campos relevantes)
- Actualiza:
  - `nombre`, `empresa`, `ubicacion`, `telefono`
  - `status` si viene en request (si no, conserva el actual)

**Auditoría (log):**
- Acción `editar`
- Guarda:
  - `datosAnteriores`
  - `datosNuevos` (después de `refresh()`)
  - `campos_modificados` usando `getCamposModificados(...)`

**Salida:**
- OK: `redirect()->route('clientes.index')->with('success', 'Usuario actualizado exitosamente.')`
- Error: `redirect()->back()->with('error', ...)->withInput()`

---

### 4.7 `destroy(Clientes $cliente)`
**Objetivo:** eliminar cliente.

- **Permiso:** solo Super Admin
- Guarda datos anteriores para log
- Ejecuta: `$cliente->delete()`
  - Esto puede ser **soft delete** o **hard delete** dependiendo de si el modelo `Clientes` usa `SoftDeletes`.

**Auditoría (log):**
- Acción `eliminar`
- Guarda `datosAnteriores` y datos básicos (nombre/id)

**Salida:**
- OK: `redirect()->route('clientes.index')->with('success', "Usuario 'X' eliminado exitosamente.")`
- Error: `redirect()->back()->with('error', ...)`

---

## 5) Gestión de grupos del cliente 

Además del CRUD, este módulo permite **asignar grupos** a un cliente.

### 5.1 Requisito de modelo/relación
El código usa:

- `$cliente->grupos` (para leer grupos asignados)
- `$cliente->grupos()->sync($gruposIds)` (para guardar)

Esto implica una relación `belongsToMany` en el modelo `Clientes`, y probablemente una tabla pivote (ej. `cliente_grupo` o similar).

---

### 5.2 `grupos(Clientes $cliente)`
**Objetivo:** mostrar pantalla para asignar grupos a un cliente.

- **Permiso:** solo Super Admin
- Obtiene **solo grupos padre (raíz)**:
  - `\App\Models\Grupos::whereNull('grupo_id')->get()`
- Los transforma a un arreglo simple `{id, nombre}` (ideal para selects)
- Obtiene grupos ya asignados:
  - `$cliente->grupos->pluck('id')->toArray()`

**Vista:** `clientes.grupos` con:
- `gruposDisponibles`
- `gruposAsignados`

---

### 5.3 `storeGrupos(Request $request, Clientes $cliente)`
**Objetivo:** guardar grupos asignados al cliente.

- **Permiso:** solo Super Admin
- Recibe `grupos` desde el request (array de ids)
- Validación práctica implementada:
  - Se filtra para aceptar únicamente IDs que:
    - existan
    - y sean **grupos padre** (`whereNull('grupo_id')`)
- Guarda con:
  - `$cliente->grupos()->sync($gruposIds)`
    - **Agrega nuevos**
    - **Elimina los no seleccionados**

**Auditoría (log):**
- Acción `asignar_grupos`
- Guarda en datos adicionales:
  - `grupos_asignados` (nombres)
  - `grupos_ids` (ids)

**Salida:**
- OK: `redirect()->route('clientes.grupos', $cliente)->with('success', 'Grupos asignados exitosamente.')`
- Error: `redirect()->back()->with('error', ...)->withInput()`

---

## 6) Rutas recomendadas (referencia)

El controlador se alinea a rutas tipo resource:

```php
Route::resource('clientes', ClientesController::class);
```

Para los grupos:

```php
Route::get('clientes/{cliente}/grupos', [ClientesController::class, 'grupos'])->name('clientes.grupos');
Route::post('clientes/{cliente}/grupos', [ClientesController::class, 'storeGrupos'])->name('clientes.grupos.store');
```

Y desde `index()` se usa:

```php
route('usuarios.index', ['id' => $user->cliente_id])
```

> Esto implica que existe otra ruta/módulo `usuarios` (diferente a “clientes”), probablemente para usuarios internos por cliente.

---

## 7) Checklist de mantenimiento

### 7.1 Si se agregan campos nuevos a clientes
1) Migración: columna + índice (si se busca)
2) `Clientes` model: `$fillable` y `$casts`
3) `StoreClientesRequest` y `UpdateClientesRequest`: reglas
4) Formulario `clientes.create`/`clientes.edit`: input
5) `clientes.index`/`clientes.show`: render

### 7.2 Si hay problemas al eliminar clientes
- Revisa las relaciones con FK (predios, estaciones, etc.)
- Define estrategia:
  - bloquear delete si tiene dependencias, o
  - usar `SoftDeletes`, o
  - cascada controlada (solo si es seguro)
  - considerar eliminacion logica

### 7.3 Si “no se ven” clientes
- Verifica:
  - `role_id` del usuario
  - `cliente_id` asignado en `users`
  - que el usuario tenga sesión activa (si no, aborta 403)

---

## 8) Mensajes en UI vs Entidad real (aclaración final)

Aunque el UI dice “Usuarios”, este módulo administra **Clientes**.
Si quieres alinear el naming:

- Cambiar labels en las vistas (`section_name`, `section_description`)
- Cambiar textos de `with('success', ...)` para decir “Cliente ...”
- Mantener logs con `seccion: 'clientes'` (recomendado)
