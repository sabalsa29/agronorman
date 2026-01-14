# Documentación Técnica — Estaciones de Medición (Arquitectura + MQTT)

> **Sección en UI:** *Estaciones de Medición*  
> **Backend:** Laravel (Controlador `EstacionesController`)  
> **Integración IoT:** MQTT (Mosquitto/Broker + `mqtt:ingest`)

---

## Tabla de contenidos

1. [Objetivo y alcance](#objetivo-y-alcance)
2. [Conceptos clave](#conceptos-clave)w
3. [Arquitectura general](#arquitectura-general)
4. [Modelo de datos y relaciones](#modelo-de-datos-y-relaciones)
5. [Flujo funcional (desde lo básico hasta lo más técnico)](#flujo-funcional-desde-lo-básico-hasta-lo-más-técnico)
6. [CRUD — Estaciones de Medición](#crud--estaciones-de-medición)
7. [Integración MQTT (Mosquitto)](#integración-mqtt-mosquitto)
8. [Operación en producción](#operación-en-producción)
9. [Troubleshooting](#troubleshooting)
10. [Checklist de despliegue](#checklist-de-despliegue)

---

## Objetivo y alcance

Esta documentación explica:

- Cómo funciona la sección **Estaciones de Medición** a nivel **UI + controlador + relaciones**.
- Qué **catálogos** alimentan los formularios (fabricantes, tipos, almacenes, variables).
- Cómo se conecta el sistema con **MQTT** para:
  - Recibir mediciones desde estaciones IoT (ingest).
  - Enviar configuraciones a estaciones (publish con retain).

> Nota: la sección *Estaciones de Medición* es un CRUD, pero su valor real está en el **acoplamiento a datos IoT** (mediciones en tiempo real) mediante MQTT.

---

## Conceptos clave

### 1) Estación (física / IoT)

Entidad que representa un dispositivo (agrícola) capaz de:

- Enviar mediciones vía MQTT.
- Recibir configuraciones vía MQTT.

En el sistema, la estación se administra como un registro en la tabla/modelo `Estaciones` (Eloquent).

### 2) Variables de medición

Catálogo de variables (temperatura, humedad, pH, etc.) que se asocian a una estación mediante una relación **muchos-a-muchos** (pivot).  
Esto habilita:

- Control de qué sensores/variables se esperan por estación.
- Filtros y vistas más limpias en UI.

### 3) Estaciones virtuales (concepto del dominio)

En el controlador se carga la relación `virtuales.parcela.cliente`.  
Esto sugiere que una estación puede tener “instancias virtuales” ligadas a una **parcela** (o unidad agronómica) y a un **cliente**.  
Se usa principalmente para:

- Mostrar “dónde está” usada una estación.
- Mostrar “zonas” (nombres) asociadas.

---

## Arquitectura general

### Vista simplificada (web + iot)

```
[Usuario Web]
    |
    | (HTTP)
    v
[Laravel: EstacionesController] -----> [DB: estaciones + pivots + catálogos]
    |
    | (no consume MQTT directamente)
    v
[Operación IoT]
    |
    | (MQTT)
    v
[Broker MQTT] <----> [Estación IoT]
    |
    | (MQTT subscribe: pap/#)
    v
[Laravel Command: mqtt:ingest] -----> [DB: measurements]
```

### Componentes relevantes

- **UI (Blade)**: `resources/views/estaciones/*`
- **Controlador CRUD**: `app/Http/Controllers/EstacionesController.php`
- **Ingest MQTT (daemon)**: `app/Console/Commands/MqttIngestCommand.php`
- **Configuración MQTT (web publish)**: `app/Http/Controllers/ConfiguracionMqttController.php`

---

## Modelo de datos y relaciones

> Estas relaciones se infieren por el uso en el controlador.

### Entidad principal: `Estaciones`

Relaciones utilizadas en `with([...])`:

- `tipo_estacion` → catálogo Tipo de Estación
- `fabricante` → catálogo Fabricantes
- `almacen` → catálogo Almacenes
- `virtuales.parcela.cliente` → uso/ubicación por cliente/parcela
- `variables()` → relación many-to-many con `VariablesMedicion`

### Relación many-to-many: Estación ↔ Variables

El controlador sincroniza el pivot con `sync()` al crear y actualizar.

**Implicación técnica:**

- Debe existir una tabla pivote (ej. `estacion_variable`, `estacion_variables`, o similar).
- El modelo `Estaciones` debe tener un método `variables()` con `belongsToMany(...)`.

---

## Flujo funcional (desde lo básico hasta lo más técnico)

### Nivel 1 — CRUD básico

1. Listar estaciones.
2. Crear estación con datos base (tipo, fabricante, etc.)
3. Editar/actualizar estación.
4. Ver detalle (show).
5. Eliminar.

### Nivel 2 — Catálogos / relaciones

Los formularios requieren cargar catálogos y relaciones:

- Fabricantes activos.
- Tipos de estación.
- Clientes.
- Almacenes.
- Variables de medición (para el pivot).

### Nivel 3 — Enriquecimiento de listado (campos calculados)

En el listado se calculan dos campos “derivados” para UI:

- `zona` = nombres únicos de estaciones virtuales válidas.
- `donde` = clientes únicos donde está activa.

Esto se obtiene desde `virtuales.parcela.cliente`, filtrando sólo clientes con `status` activo.

### Nivel 4 — Integración IoT con MQTT

Aquí ya no es “solo CRUD”.

- Las estaciones envían datos en topics tipo `pap/{UUID}`.
- Un proceso **daemon** (`php artisan mqtt:ingest`) se suscribe a `pap/#` y guarda mediciones en `measurements`.
- El sistema puede enviar configuraciones (retain) por MQTT hacia `pap/{UUID}`.

---

## CRUD — Estaciones de Medición

### 1) Listado — `index()`

**Qué hace:**

- Carga estaciones con relaciones necesarias.
- Filtra estaciones virtuales cuyo cliente esté activo.
- Construye campos para UI: `zona` y `donde`.
- Devuelve vista `estaciones.index`.

**Puntos técnicos:**

- El `map()` ejecuta lógica por estación (potencial N+1 si faltan relaciones; aquí ya se cargan con `with()`).
- `zona` y `donde` se agregan dinámicamente al objeto Eloquent para la vista.

### 2) Formulario de alta — `create()`

Carga catálogos:

- `Fabricante` activos (`status = 1`).
- `TipoEstacion` ordenados por nombre.
- `Cliente` ordenados por nombre.
- `Almacen` ordenados por nombre.
- `VariablesMedicion` ordenadas por nombre.
- `EstatusOptions` desde `Estaciones::getEstatusOptions()`.

Devuelve vista `estaciones.create`.

### 3) Alta — `store(Request $request)`

**Acciones principales:**

1. Crea la estación por **mass assignment** (`Estaciones::create($request->all())`).
2. Sincroniza variables (`variables_medicion_id`) con `sync()`.
3. Redirige a `estaciones.index`.

**Recomendación técnica:**

- Asegurar que el modelo `Estaciones` tenga `$fillable` correcto (evitar asignación masiva de campos no deseados).
- Agregar validaciones con `$request->validate([...])`.

### 4) Formulario de edición — `edit(Estaciones $estacione)`

- Carga la estación con relaciones para mostrar valores actuales.
- Carga catálogos para selects.
- Devuelve vista `estaciones.edit`.

### 5) Detalle — `show(Estaciones $estacione)`

- Carga la estación con relaciones para mostrar todo su contexto.
- Devuelve vista `estaciones.show`.

### 6) Actualización — `update(Request $request, Estaciones $estacione)`

**Acciones principales:**

1. Actualiza por **mass assignment** (`$estacione->update($request->all())`).
2. Sincroniza variables (`variables_medicion_id`) con `sync()`.
3. Redirige a `estaciones.index`.

**Buenas prácticas recomendadas:**

- Validar campos.
- Si hay campos sensibles, excluirlos (o usar `$fillable` + DTO).

### 7) Eliminación — `destroy(Estaciones $estacione)`

- Ejecuta `$estacione->delete()`.
- Redirige al listado.

> Nota: si hay dependencias (mediciones, estaciones virtuales, pivots), definir reglas de borrado (soft delete, cascadas, restricciones).

---

## Integración MQTT (Mosquitto)

> Esta sección se apoya en el documento técnico MQTT del proyecto.

### Topics

- **Recepción de datos**: `pap/#` (wildcard para recibir de todas las estaciones)
  - Específico: `pap/{UUID}` (o `data/{IMEI}` según implementación de la estación).
- **Envío de configuración**: `pap/{UUID}` (mensaje retenido).

### Flujo de recepción (Ingest)

1. La estación publica un JSON en `pap/{UUID}`.
2. El broker recibe el mensaje.
3. `MqttIngestCommand` se suscribe a `pap/#`.
4. El comando:
   - Parsea JSON.
   - Extrae el identificador de estación (`estacion_id` / IMEI).
   - Normaliza y transforma campos (ej. valores que vienen escalados).
   - Parsea fecha en formato `AA/MM/DD,HH:MM:SS±ZZ`.
   - Guarda en tabla `measurements`.

### Variables de entorno (.env)

Parámetros típicos:

```env
MQTT_HOST=...
MQTT_PORT=1883
MQTT_USERNAME=...
MQTT_PASSWORD=...
MQTT_CLIENT_ID=pia_ingestor_XXXXXX
MQTT_TOPIC=pap/#
MQTT_QOS=1
```

### Ejecutar el ingestor

**Modo manual:**

```bash
php artisan mqtt:ingest
```

**Modo daemon (servidor):**

```bash
nohup php artisan mqtt:ingest > storage/logs/mqtt_ingest.log 2>&1 &
```

### Flujo de envío de configuración (Publish)

- Un controlador web (configuración MQTT) publica un JSON con parámetros en `pap/{UUID}`.
- Se recomienda `retain=true` para que la estación reciba la última configuración al reconectar.

Ejemplo payload:

```json
{
  "PCF": 0,
  "PCR": 1,
  "PTP": 0,
  "PTC": 60,
  "PTR": 0,
  "PRS": 0
}
```

---

## Operación en producción

### Recomendaciones

1. **Ejecutar `mqtt:ingest` con supervisor/systemd**
   - Reinicio automático
   - Logs controlados
2. **Monitoreo**
   - Revisar `storage/logs/mqtt_ingest.log` y `storage/logs/laravel.log`
3. **Seguridad**
   - No hardcodear credenciales MQTT
   - Rotar `MQTT_USERNAME/MQTT_PASSWORD`
   - Restringir acceso a broker por IP/Firewall cuando aplique

---

## Troubleshooting

### El ingestor no recibe mensajes

- Validar conectividad al broker (host/port).
- Verificar `MQTT_TOPIC` (por defecto `pap/#`).
- Revisar logs de Laravel y del ingestor.

### Error al enviar configuración

- Validar que el UUID exista.
- Verificar permisos del usuario MQTT (si aplica).
- Confirmar QoS/retain.

---

## Checklist de despliegue

- [ ] `.env` con variables MQTT correctas
- [ ] Broker accesible desde el servidor (network/security group)
- [ ] `php-mqtt/client` instalado vía composer
- [ ] Proceso `mqtt:ingest` corriendo como daemon (supervisor/systemd)
- [ ] Catálogos cargados: Fabricantes, Tipos de estación, Almacenes, Variables
- [ ] Estaciones creadas con identificador consistente (UUID/IMEI) con lo que publica la estación
- [ ] Validaciones y `$fillable` revisados para mass assignment

---
