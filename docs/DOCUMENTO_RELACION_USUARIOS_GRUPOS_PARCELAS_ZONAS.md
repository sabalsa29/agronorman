# Documentación técnica y de uso — Grupos, Parcelas, Usuarios, Accesos y Logs

## Índice
1. [Resumen](#resumen)
2. [Objetivos de la implementación](#objetivos-de-la-implementación)
3. [Grupo génesis (root) — Seeder](#grupo-génesis-root--seeder)
4. [Cambios funcionales en la plataforma](#cambios-funcionales-en-la-plataforma)
5. [Modelo de datos (tablas y propósito)](#modelo-de-datos-tablas-y-propósito)
   - [Tabla `user_grupo`](#tabla-user_grupo)
   - [Tabla `grupo_parcela`](#tabla-grupo_parcela)
   - [Tabla `grupo_zona_manejo`](#tabla-grupo_zona_manejo)
6. [Parcelas — Create/Update con multi-select de grupos](#parcelas--createupdate-con-multi-select-de-grupos)
7. [Usuarios — CRUD universal + asignación de productor + accesos](#usuarios--crud-universal--asignación-de-productor--accesos)
8. [Asignación manual (Grupo → Predios/Zonas) con `asignaciones_cache`](#asignación-manual-grupo--predioszonas-con-asignaciones_cache)
   - [Estructura de `asignaciones_cache`](#estructura-de-asignaciones_cache)
   - [Reglas de negocio](#reglas-de-negocio)
9. [Persistencia de asignaciones manuales (métodos de modelo)](#persistencia-de-asignaciones-manuales-métodos-de-modelo)
   - [`GrupoParcela::asignarPrediosAUsuario`](#grupoparcelaasignarprediosausuario)
   - [`GrupoZonaManejo::asignarZonasAUsuario`](#grupozonamanejoasignarzonasausuario)
10. [Controladores involucrados](#controladores-involucrados)
    - [`UserController`](#usercontroller)
    - [`UserGrupoController`](#usergrupocontroller)
11. [Logs de plataforma (`platform_logs`)](#logs-de-plataforma-platform_logs)
12. [Consideraciones técnicas y recomendaciones](#consideraciones-técnicas-y-recomendaciones)

---

## Resumen

- **Estandarizar la jerarquía de grupos** asegurando que exista siempre un grupo “génesis” (root).
- Permitir la relación **Grupos ↔ Parcelas** desde UI y persistencia.
- Reorganizar el concepto de **“usuarios”** y **“productores”** (antes “usuarios” ahora “productores” como empresas agrícolas) y mover la administración de **usuarios del sistema** a una sección universal.
- Mejorar el CRUD de usuarios agregando:
  - Select para elegir **un solo productor** al cual pertenecerá el usuario.
  - Multi-select para asignar **grupos jerárquicos** a los que tendrá acceso.
  - Una sección para asignar **parcelas o zonas específicas** manualmente (acceso granular).
- Agregar tablas nuevas para almacenar relaciones:
  - Usuario ↔ Grupos
  - Grupos ↔ Parcelas
  - Usuario ↔ Zonas (y opcionalmente ↔ Grupo)
- Integrar **logs** (auditoría) al crear, editar y eliminar entidades clave (parcelas, usuarios, etc.).

---

## Objetivos de la implementación
1. Garantizar un **grupo raíz** único (génesis) para estabilidad jerárquica.
2. Permitir que una parcela pertenezca a **uno o múltiples grupos**.
3. Centralizar la administración de **usuarios** (usuarios universales) y relacionarlos a un **productor**.
4. Permitir accesos:
   - **Jerárquicos** (por grupos, incluye descendientes).
   - **Granulares** (por parcelas o zonas puntuales) incluso si pertenecen a otro productor.
5. Auditar operaciones relevantes en **platform_logs**.

---

## Grupo génesis (root) — Seeder

### Propósito
Asegurar que siempre exista un grupo raíz único con:
- `nombre = 'Norman'`
- `grupo_id = null`
- `is_root = true`

### Seeder
```php
public function run(): void
{
    DB::table('grupos')->updateOrInsert(
        ['nombre' => 'Norman', 'grupo_id' => null],
        [
            'status' => true,
            'updated_at' => now(),
            'created_at' => now(),
            'deleted_at' => null,
            'is_root' => true
        ]
    );
}
```

### Regla
Debe existir **uno y solo uno** grupo root.

---

## Cambios funcionales en la plataforma

### 1) Productores
- Se quitó el identificador anterior de “usuarios” que se usaba como empresa.
- Ahora **Productores** son las **empresas agrícolas** (tabla `clientes`).

### 2) Usuarios
- Los usuarios del sistema se movieron a una sección aparte, permitiendo crear usuarios de forma **universal**.
- En el formulario de usuarios:
  - Se selecciona **un productor** (cliente) para el usuario.
  - Se seleccionan **grupos** (acceso jerárquico).
  - Se seleccionan **parcelas / zonas específicas** (acceso granular manual).

### 3) Parcelas
- En create y edit de parcelas se agregó un **multi-select** de grupos.
- La parcela puede pertenecer a **múltiples grupos**.

---

## Modelo de datos (tablas y propósito)

## Tabla `user_grupo`
Relación para accesos jerárquicos: **Usuario ↔ Grupo**.

Migración:
```php
Schema::create('user_grupo', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('grupo_id');
    $table->boolean('is_default')->default(false);
    $table->timestamps();

    $table->unique(['user_id', 'grupo_id'], 'uq_user_grupo');

    // Índices
    $table->index('user_id', 'idx_user_grupo_user');
    $table->index('grupo_id', 'idx_user_grupo_grupo');

   
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');
});
```

---

## Tabla `grupo_parcela`
Relación principal: **Grupo ↔ Parcela**.
Además, se reutiliza para acceso manual: **Usuario ↔ Parcela (con grupo asociado)** mediante `user_id`.

Migración:
```php
Schema::create('grupo_parcela', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('grupo_id');
    $table->unsignedBigInteger('parcela_id');

    // Índices
    $table->index('user_id', 'idx_grupo_parcela_user');
    $table->index('grupo_id', 'idx_grupo_parcela_grupo');
    $table->index('parcela_id', 'idx_grupo_parcela_parcela');

    // FKs
    $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');
    $table->foreign('parcela_id')->references('id')->on('parcelas')->onDelete('cascade');

    $table->timestamps();
});
```

---

## Tabla `grupo_zona_manejo`
Relación para accesos granulares: **Usuario ↔ Zona de manejo**.
El `grupo_id` es **nullable**.

Migración:
```php
Schema::create('grupo_zona_manejo', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('grupo_id')->nullable();
    $table->unsignedBigInteger('zona_manejo_id');

    // Índices
    $table->index('user_id', 'idx_grupo_zona_manejo_user');
    $table->index('grupo_id', 'idx_grupo_zona_manejo_grupo');
    $table->index('zona_manejo_id', 'idx_grupo_zona_manejo_zona');

    // FKs
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('grupo_id')->references('id')->on('grupos')->nullOnDelete();
    $table->foreign('zona_manejo_id')->references('id')->on('zona_manejos')->onDelete('cascade');

    $table->timestamps();
});
```

---

## Parcelas — Create/Update con multi-select de grupos

### Validaciones clave
- `lat` debe estar entre `-90` y `90`
- `lon` debe estar entre `-180` y `180`
- `grupo_id` debe ser un array con al menos 1 grupo

### Store (crear parcela)
- Se crea la parcela.
- Se crea la relación con cada grupo seleccionado mediante `GrupoParcela::firstOrCreate`.

Fragmento (store):
```php
$request->validate([
    'cliente_id'  => ['required','exists:clientes,id'],
    'nombre'      => ['required','string','max:255'],
    'superficie'  => ['required','numeric','min:0'],
    'lat'         => ['required','numeric','between:-90,90'],
    'lon'         => ['required','numeric','between:-180,180'],
    'status'      => ['required','in:0,1'],
    'grupo_id'    => ['required', 'array'],
    'grupo_id.*'  => ['exists:grupos,id'],
]);

$grupoIds = array_values(array_filter(Arr::wrap($request->input('grupo_id'))));

$parcela = new Parcelas();
$parcela->cliente_id = (int) $request->cliente_id;
$parcela->nombre     = $request->nombre;
$parcela->superficie = (float) $request->superficie;

// Normaliza coma -> punto (ej: "20,67")
$lat = str_replace(',', '.', (string) $request->lat);
$lon = str_replace(',', '.', (string) $request->lon);

$parcela->lat = (float) $lat;
$parcela->lon = (float) $lon;

$parcela->status = (int) $request->status;
$parcela->save();

foreach ($grupoIds as $grupoId) {
    GrupoParcela::firstOrCreate(
        [
            'grupo_id'   => (int) $grupoId,
            'parcela_id' => (int) $parcela->id,
        ],
        [
            'user_id' => 0,
        ]
    );
}
```

### Update (editar parcela)
- Actualiza datos básicos.
- Elimina relaciones previas y recrea relaciones grupo↔parcela.

Fragmento (update):
```php
$parcela->nombre = $request->nombre;
$parcela->superficie = $request->superficie;

$lat = str_replace(',', '.', (string) $request->lat);
$lon = str_replace(',', '.', (string) $request->lon);

$parcela->lat = (float) $lat;
$parcela->lon = (float) $lon;

$parcela->status = $request->status;
$parcela->save();

GrupoParcela::where('parcela_id', $parcela->id)->delete();

$grupoIds = array_values(array_filter(Arr::wrap($request->input('grupo_id'))));

foreach ($grupoIds as $grupoId) {
    GrupoParcela::firstOrCreate(
        [
            'grupo_id'   => (int) $grupoId,
            'parcela_id' => (int) $parcela->id,
        ],
        [
            'user_id' => 0,
        ]
    );
}
```

---

## Usuarios — CRUD universal + asignación de productor + accesos

### Datos capturados en formulario
1) Productor (`cliente_id`) — **un solo productor**
2) Datos base: `nombre`, `email`, `password`
3) Acceso jerárquico: `grupo_id[]` (multi-select)
4) Acceso granular manual: `asignaciones_cache` (árbol en memoria)

---

## Asignación manual (Grupo → Predios/Zonas) con `asignaciones_cache`

### Propósito
Permitir asignación **granular**:
- Por predio completo (si `zonas` viene vacío)
- Por zonas específicas (si `zonas` tiene ids)

Esto permite dar acceso incluso si la parcela/zona pertenece a otro productor.

---

## Estructura de `asignaciones_cache`

Ejemplo:
```json
{
  "2": {
    "id": "2",
    "nombre": "ICAMEX",
    "predios": {
      "6": {
        "id": "6",
        "nombre": "Invernadero - Estación  2",
        "zonas": {
          "5": {"id":"5","nombre":"Invernadero - Estación 2 — Jitomate"},
          "19":{"id":"19","nombre":"Invernadero - Estación 2 — Higo"}
        }
      }
    }
  }
}
```

---

## Reglas de negocio
- **Obligatorio:** grupo y predio.
- **Opcional:** zonas.
- Si `predio.zonas` vacío ⇒ se asigna **predio completo** (se guarda en `grupo_parcela`).
- Si `predio.zonas` con datos ⇒ se asignan **zonas** (se guarda en `grupo_zona_manejo`).

---

## Persistencia de asignaciones manuales (métodos de modelo)

## `GrupoParcela::asignarPrediosAUsuario`
```php
public static function asignarPrediosAUsuario($userId, $prediosIds)
{
    foreach ($prediosIds as $predioId) {
        // Obtener un grupo asociado al predio
        $grupoParcela = self::where('parcela_id', $predioId)->first();

        if ($grupoParcela) {
            self::updateOrInsert([
                'user_id' => $userId,
                'grupo_id' => $grupoParcela->grupo_id,
                'parcela_id' => $predioId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
```

**Notas técnicas:**
- Si una parcela está relacionada a **múltiples grupos**, `first()` tomará el primero que encuentre.
- Si no existe relación grupo↔parcela para ese predio, no inserta.

---

## `GrupoZonaManejo::asignarZonasAUsuario`
```php
public static function asignarZonasAUsuario($userId, $zonasIds)
{
    foreach ($zonasIds as $zonaId) {
        if ($zonaId) {
            self::updateOrInsert([
                'user_id' => $userId,
                'grupo_id' => null,
                'zona_manejo_id' => $zonaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
```

**Notas técnicas:**
- Se guarda `grupo_id = null`.
- Si se requiere asociar zona al grupo específico, debe persistirse explícitamente.

---

## Controladores involucrados

## `UserController`

### `index()`
- Lista usuarios.
- Si no es superadmin: filtra por `cliente_id` del usuario autenticado.

### `create()`
- Prepara:
  - Lista de productores (clientes).
  - Lista de grupos disponibles con `ruta_completa` (accessor).

### `store()`
- Crea usuario.
- Guarda relaciones:
  - `user_grupo` (grupos jerárquicos).
  - `grupo_parcela` y `grupo_zona_manejo` derivadas de `asignaciones_cache`.

Fragmentos relevantes:
```php
$usuario = Usuarios::create([
    'nombre' => $validatedData['nombre'],
    'email' => $validatedData['email'],
    'password' => bcrypt($validatedData['password']),
    'role_id' => 3,
    'cliente_id' => $validatedData['cliente_id'] ?? null,
    'status' => 1,
]);

if($request->grupo_id) {
    foreach ($request->grupo_id as $grupoId) {
        $nuevoUserGrupo = new \App\Models\UserGrupo();
        $nuevoUserGrupo->user_id = $usuario->id;
        $nuevoUserGrupo->grupo_id = $grupoId;
        $nuevoUserGrupo->save();
    }
}

if ($request->filled('asignaciones_cache')) {
    $asignacionesCache = json_decode($request->input('asignaciones_cache'), true);

    $prediosIds = collect($asignacionesCache)
        ->pluck('predios')
        ->filter()
        ->flatMap(function ($predios) {
            return collect($predios)
                ->filter(fn ($predio) => empty($predio['zonas']))
                ->pluck('id');
        })
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
        ->toArray();

    $zonasIds = collect($asignacionesCache)
        ->pluck('predios')
        ->filter()
        ->flatMap(function ($predios) {
            return collect($predios)
                ->pluck('zonas')
                ->filter()
                ->flatMap(fn ($zonas) => collect($zonas)->keys());
        })
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
        ->toArray();

    if(!empty($prediosIds)) {
        \App\Models\GrupoParcela::asignarPrediosAUsuario($usuario->id, $prediosIds);
    }
    if(!empty($zonasIds)) {
        \App\Models\GrupoZonaManejo::asignarZonasAUsuario($usuario->id, $zonasIds);
    }
}
```

### `edit()`
- Prepara:
  - grupos disponibles
  - grupos asignados al usuario (ids)
  - reconstrucción del árbol `asignaciones_cache` desde DB:
    - `grupo_parcela` (predios manuales)
    - `grupo_zona_manejo` (zonas manuales)

---

## `UserGrupoController`

### `index()`
Muestra asignaciones de usuarios a grupos.

### `assign()`
Muestra formulario de asignación de usuario a grupos.

### `store()`
- Valida `grupo_id[]` y `usuario_id`
- Elimina asignaciones previas y crea las nuevas

### `prediosByGrupo(Grupos $grupo)`
Retorna JSON de predios asociados al grupo vía `grupo_parcela`.

```php
public function prediosByGrupo(Grupos $grupo)
{
    $predios = Parcelas::query()
        ->whereIn('id', function ($q) use ($grupo) {
            $q->select('parcela_id')
              ->from('grupo_parcela')
              ->where('grupo_id', $grupo->id);
        })
        ->select('id', 'nombre')
        ->orderBy('nombre')
        ->get();

    return response()->json($predios);
}
```

---

## Logs de plataforma (`platform_logs`)

### Ejemplo de log
```php
$this->logPlatformAction(
    seccion: 'clientes',
    accion: 'crear',
    entidadTipo: 'Clientes',
    descripcion: "Cliente '{$cliente->nombre}' creado exitosamente",
    entidadId: $cliente->id,
    datosNuevos: $this->getModelDataForLog($cliente, ['nombre', 'empresa', 'ubicacion', 'telefono', 'status']),
    datosAdicionales: [
        'empresa' => $cliente->empresa,
        'ubicacion' => $cliente->ubicacion,
    ]
);
```

### Modelo (campos)
```php
protected $table = 'platform_logs';

protected $fillable = [
    'usuario_id',
    'username',
    'seccion',
    'accion',
    'entidad_tipo',
    'entidad_id',
    'descripcion',
    'datos_anteriores',
    'datos_nuevos',
    'datos_adicionales',
    'ip_address',
    'user_agent',
];

protected $casts = [
    'datos_anteriores' => 'array',
    'datos_nuevos' => 'array',
    'datos_adicionales' => 'array',
];
```

---

## Consideraciones técnicas y recomendaciones

1) **Índices y uniques**
- Considerar activar uniques según reglas finales:
  - `user_grupo (user_id, grupo_id)` ✅ ya existe
  - `grupo_parcela (grupo_id, parcela_id)` (actualmente comentado)
  - `grupo_zona_manejo (user_id, zona_manejo_id [, grupo_id])` (actualmente comentado)

2) **Transacciones**
- Recomendado envolver en `DB::transaction()` operaciones que hagan:
  - update entidad + delete relaciones + insert relaciones

3) **Consistencia**
- Predios manuales infieren `grupo_id` desde `grupo_parcela`.
- Zonas manuales guardan `grupo_id = null`.

Si se requiere persistir el grupo elegido en el árbol, se recomienda enviar y guardar explícitamente `grupo_id` por predio/zona.

4) **Coordenadas**
- Validar rangos y normalizar coma → punto para evitar `Out of range` y errores de casting.
