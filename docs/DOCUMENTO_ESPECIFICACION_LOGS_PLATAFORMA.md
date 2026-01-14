# Documento de Especificación: Sistema de Logs de la Plataforma

## Versión: 1.0
**Fecha:** Diciembre 2024  
**Autor:** Sistema de Desarrollo

---

## Índice

1. [Introducción](#introducción)
2. [Propósito](#propósito)
3. [Alcance](#alcance)
4. [Estructura de la Base de Datos](#estructura-de-la-base-de-datos)
5. [Campos del Log](#campos-del-log)
6. [Secciones y Acciones Registradas](#secciones-y-acciones-registradas)
7. [Información Registrada por Acción](#información-registrada-por-acción)
8. [Acceso y Permisos](#acceso-y-permisos)
9. [Visualización de Logs](#visualización-de-logs)
10. [Ejemplos de Registros](#ejemplos-de-registros)

---

## Introducción

El Sistema de Logs de la Plataforma es un sistema de auditoría que registra todas las acciones realizadas en las diferentes secciones de la plataforma, excluyendo las acciones relacionadas con MQTT (que tiene su propio sistema de logs).

Este sistema permite al Super Administrador tener un registro completo y detallado de quién hizo qué, cuándo y desde dónde, proporcionando trazabilidad completa de las operaciones en la plataforma.

---

## Propósito

El sistema de logs tiene los siguientes propósitos:

1. **Auditoría**: Registrar todas las acciones realizadas en la plataforma para cumplir con requisitos de auditoría y trazabilidad.
2. **Seguridad**: Detectar actividades sospechosas o no autorizadas.
3. **Trazabilidad**: Rastrear cambios en los datos para identificar quién, cuándo y qué modificó.
4. **Resolución de Problemas**: Ayudar a identificar y resolver problemas relacionados con cambios en los datos.
5. **Cumplimiento**: Cumplir con regulaciones que requieren registro de actividades.

---

## Alcance

### Secciones Incluidas

El sistema de logs registra acciones en las siguientes secciones de la plataforma:

- ✅ **Clientes**: Todas las operaciones CRUD
- 🔄 **Grupos**: (Pendiente de implementar)
- 🔄 **Zonas de Manejo**: (Pendiente de implementar)
- 🔄 **Parcelas**: (Pendiente de implementar)
- 🔄 **Estaciones**: (Pendiente de implementar)
- 🔄 **Usuarios**: (Pendiente de implementar)
- 🔄 **Cultivos**: (Pendiente de implementar)
- 🔄 **Otras secciones**: (Se agregarán según necesidad)

### Secciones Excluidas

- ❌ **MQTT**: Tiene su propio sistema de logs (`configuracion_mqtt_logs`)
- ❌ **Autenticación**: Los logs de login/logout se manejan por separado
- ❌ **Sistema**: Acciones internas del sistema no relacionadas con usuarios

---

## Estructura de la Base de Datos

### Tabla: `platform_logs`

```sql
CREATE TABLE platform_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NULL,
    username VARCHAR(100) NOT NULL,
    seccion VARCHAR(50) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    entidad_tipo VARCHAR(100) NOT NULL,
    entidad_id BIGINT UNSIGNED NULL,
    descripcion TEXT NOT NULL,
    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,
    datos_adicionales JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_seccion (seccion),
    INDEX idx_accion (accion),
    INDEX idx_entidad_tipo (entidad_tipo),
    INDEX idx_entidad_id (entidad_id),
    INDEX idx_created_at (created_at)
);
```

---

## Campos del Log

### Campos Principales

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `id` | BIGINT | ID único del registro | `1` |
| `usuario_id` | BIGINT (NULL) | ID del usuario que realizó la acción | `5` |
| `username` | VARCHAR(100) | Nombre del usuario (para logs de usuarios eliminados) | `"Juan Pérez"` |
| `seccion` | VARCHAR(50) | Sección de la plataforma donde ocurrió la acción | `"clientes"` |
| `accion` | VARCHAR(50) | Tipo de acción realizada | `"crear"`, `"editar"`, `"eliminar"`, `"ver"` |
| `entidad_tipo` | VARCHAR(100) | Tipo de entidad afectada (nombre del modelo) | `"Clientes"`, `"Grupos"` |
| `entidad_id` | BIGINT (NULL) | ID del registro afectado | `17` |
| `descripcion` | TEXT | Descripción legible de la acción | `"Cliente 'Rancho San José' creado exitosamente"` |
| `datos_anteriores` | JSON (NULL) | Datos antes de la modificación (solo para editar) | `{"nombre": "Antiguo", "status": 1}` |
| `datos_nuevos` | JSON (NULL) | Datos después de la modificación (solo para crear/editar) | `{"nombre": "Nuevo", "status": 1}` |
| `datos_adicionales` | JSON (NULL) | Información adicional relevante | `{"campos_modificados": ["nombre"], "total_clientes": 42}` |
| `ip_address` | VARCHAR(45) | Dirección IP del cliente | `"192.168.1.100"` |
| `user_agent` | TEXT | User agent del navegador | `"Mozilla/5.0..."` |
| `created_at` | TIMESTAMP | Fecha y hora de la acción | `"2024-12-29 15:30:45"` |
| `updated_at` | TIMESTAMP | Fecha y hora de última actualización | `"2024-12-29 15:30:45"` |

### Relaciones

- **usuario_id** → `users.id` (Foreign Key, SET NULL on delete)
  - Si el usuario es eliminado, el log se mantiene pero `usuario_id` se establece en NULL
  - El campo `username` se mantiene para preservar la información

---

## Secciones y Acciones Registradas

### Sección: Clientes

#### Acciones Registradas

| Acción | Descripción | Cuándo se Registra |
|--------|-------------|-------------------|
| `crear` | Creación de un nuevo cliente | Al ejecutar `store()` exitosamente |
| `editar` | Modificación de un cliente existente | Al ejecutar `update()` exitosamente |
| `eliminar` | Eliminación de un cliente | Al ejecutar `destroy()` exitosamente |
| `ver` | Visualización de detalles de un cliente | Al ejecutar `show()` |
| `ver_lista` | Visualización de la lista de clientes | Al ejecutar `index()` (solo super admin) |

#### Información Registrada por Acción

##### Acción: `crear`

**Campos registrados:**
- `seccion`: `"clientes"`
- `accion`: `"crear"`
- `entidad_tipo`: `"Clientes"`
- `entidad_id`: ID del cliente creado
- `descripcion`: `"Cliente '{nombre}' creado exitosamente"`
- `datos_nuevos`: Objeto JSON con los datos del cliente creado
  ```json
  {
    "nombre": "Rancho San José",
    "empresa": "Agrícola del Norte",
    "ubicacion": "Jalisco, México",
    "telefono": "1234567890",
    "status": 1
  }
  ```
- `datos_adicionales`: Información complementaria
  ```json
  {
    "empresa": "Agrícola del Norte",
    "ubicacion": "Jalisco, México"
  }
  ```

##### Acción: `editar`

**Campos registrados:**
- `seccion`: `"clientes"`
- `accion`: `"editar"`
- `entidad_tipo`: `"Clientes"`
- `entidad_id`: ID del cliente editado
- `descripcion`: `"Cliente '{nombre}' (ID: {id}) actualizado"`
- `datos_anteriores`: Objeto JSON con los datos antes de la modificación
  ```json
  {
    "nombre": "Rancho San José",
    "empresa": "Agrícola del Norte",
    "ubicacion": "Jalisco, México",
    "telefono": "1234567890",
    "status": 1
  }
  ```
- `datos_nuevos`: Objeto JSON con los datos después de la modificación
  ```json
  {
    "nombre": "Rancho San José Actualizado",
    "empresa": "Agrícola del Norte S.A.",
    "ubicacion": "Jalisco, México",
    "telefono": "1234567890",
    "status": 1
  }
  ```
- `datos_adicionales`: Lista de campos modificados
  ```json
  {
    "campos_modificados": ["nombre", "empresa"]
  }
  ```

##### Acción: `eliminar`

**Campos registrados:**
- `seccion`: `"clientes"`
- `accion`: `"eliminar"`
- `entidad_tipo`: `"Clientes"`
- `entidad_id`: ID del cliente eliminado
- `descripcion`: `"Cliente '{nombre}' (ID: {id}) eliminado"`
- `datos_anteriores`: Objeto JSON con los datos del cliente antes de eliminarlo
  ```json
  {
    "nombre": "Rancho San José",
    "empresa": "Agrícola del Norte",
    "ubicacion": "Jalisco, México",
    "telefono": "1234567890",
    "status": 1
  }
  ```
- `datos_adicionales`: Información del cliente eliminado
  ```json
  {
    "nombre": "Rancho San José"
  }
  ```

##### Acción: `ver`

**Campos registrados:**
- `seccion`: `"clientes"`
- `accion`: `"ver"`
- `entidad_tipo`: `"Clientes"`
- `entidad_id`: ID del cliente visualizado
- `descripcion`: `"Visualización de detalles del cliente '{nombre}' (ID: {id})"`
- `datos_adicionales`: Información básica
  ```json
  {
    "nombre": "Rancho San José"
  }
  ```

##### Acción: `ver_lista`

**Campos registrados:**
- `seccion`: `"clientes"`
- `accion`: `"ver_lista"`
- `entidad_tipo`: `"Clientes"`
- `entidad_id`: `NULL`
- `descripcion`: `"Visualización de lista de clientes"`
- `datos_adicionales`: Estadísticas
  ```json
  {
    "total_clientes": 42
  }
  ```

---

## Información Registrada por Acción

### Información Común a Todas las Acciones

Todas las acciones registran automáticamente:

- **Usuario**: ID y nombre del usuario autenticado
- **IP Address**: Dirección IP del cliente (obtenida de `request()->ip()`)
- **User Agent**: User agent del navegador (obtenido de `request()->userAgent()`)
- **Timestamp**: Fecha y hora exacta de la acción (`created_at`)

### Información Específica por Tipo de Acción

#### Acciones de Creación (`crear`)

- ✅ Datos nuevos (todos los campos del registro creado)
- ✅ Información adicional relevante
- ❌ No incluye datos anteriores (no aplica)

#### Acciones de Edición (`editar`, `actualizar`)

- ✅ Datos anteriores (estado antes de la modificación)
- ✅ Datos nuevos (estado después de la modificación)
- ✅ Lista de campos modificados
- ✅ Comparación de cambios

#### Acciones de Eliminación (`eliminar`, `borrar`)

- ✅ Datos anteriores (estado del registro antes de eliminarlo)
- ✅ Información adicional (nombre, identificadores, etc.)
- ❌ No incluye datos nuevos (el registro ya no existe)

#### Acciones de Visualización (`ver`, `ver_lista`)

- ✅ Información básica del registro visualizado
- ✅ Estadísticas (para listas)
- ❌ No incluye datos completos (solo información relevante)

---

## Acceso y Permisos

### Restricciones de Acceso

- **Solo Super Administrador**: Únicamente los usuarios con rol de Super Administrador pueden acceder a los logs de la plataforma.
- **Solo Lectura**: Los logs son de solo lectura. No se pueden editar ni eliminar registros.
- **Sin Exportación**: Actualmente no hay funcionalidad de exportación (se puede agregar en el futuro).

### Verificación de Permisos

El acceso se verifica en:

1. **Controlador**: `PlatformLogController::index()` y `PlatformLogController::show()`
   ```php
   if (!$user || !$user->isSuperAdmin()) {
       abort(403, 'Solo el Super Administrador puede ver los logs de la plataforma.');
   }
   ```

2. **Rutas**: Las rutas están protegidas por middleware `auth`, pero la verificación de super admin se hace en el controlador.

3. **Vista**: El enlace en el sidebar solo aparece para super administradores:
   ```blade
   @if (Auth::check() && Auth::user()->isSuperAdmin())
       <li class="nav-item">
           <a href="{{ route('platform-logs.index') }}">Logs de la Plataforma</a>
       </li>
   @endif
   ```

---

## Visualización de Logs

### Vista Principal: Lista de Logs

**Ruta**: `/platform-logs`  
**Vista**: `resources/views/platform-logs/index.blade.php`

**Características:**
- Tabla con DataTables para búsqueda, ordenamiento y paginación
- Filtros por:
  - Sección
  - Acción
  - Entidad (tipo)
  - Usuario
  - Rango de fechas (desde/hasta)
- Columnas mostradas:
  - Fecha/Hora
  - Usuario
  - Sección
  - Acción
  - Entidad
  - Descripción (limitada a 80 caracteres)
  - IP Address
  - Botón "Ver" para detalles

**Paginación:**
- 50 registros por página (paginación de Laravel)
- DataTables desactivado para usar paginación del servidor

### Vista de Detalles: Log Individual

**Ruta**: `/platform-logs/{id}`  
**Vista**: `resources/views/platform-logs/show.blade.php`

**Características:**
- Información general del log
- Información técnica (IP, User Agent)
- Visualización de datos anteriores (si aplica)
- Visualización de datos nuevos (si aplica)
- Visualización de datos adicionales (si aplica)
- Formato JSON con sintaxis destacada

---

## Ejemplos de Registros

### Ejemplo 1: Crear Cliente

```json
{
  "id": 1,
  "usuario_id": 5,
  "username": "admin@example.com",
  "seccion": "clientes",
  "accion": "crear",
  "entidad_tipo": "Clientes",
  "entidad_id": 17,
  "descripcion": "Cliente 'Rancho San José' creado exitosamente",
  "datos_anteriores": null,
  "datos_nuevos": {
    "nombre": "Rancho San José",
    "empresa": "Agrícola del Norte",
    "ubicacion": "Jalisco, México",
    "telefono": "1234567890",
    "status": 1
  },
  "datos_adicionales": {
    "empresa": "Agrícola del Norte",
    "ubicacion": "Jalisco, México"
  },
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
  "created_at": "2024-12-29 15:30:45"
}
```

### Ejemplo 2: Editar Cliente

```json
{
  "id": 2,
  "usuario_id": 5,
  "username": "admin@example.com",
  "seccion": "clientes",
  "accion": "editar",
  "entidad_tipo": "Clientes",
  "entidad_id": 17,
  "descripcion": "Cliente 'Rancho San José' (ID: 17) actualizado",
  "datos_anteriores": {
    "nombre": "Rancho San José",
    "empresa": "Agrícola del Norte",
    "ubicacion": "Jalisco, México",
    "telefono": "1234567890",
    "status": 1
  },
  "datos_nuevos": {
    "nombre": "Rancho San José Actualizado",
    "empresa": "Agrícola del Norte S.A.",
    "ubicacion": "Jalisco, México",
    "telefono": "1234567890",
    "status": 1
  },
  "datos_adicionales": {
    "campos_modificados": ["nombre", "empresa"]
  },
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
  "created_at": "2024-12-29 15:35:20"
}
```

### Ejemplo 3: Eliminar Cliente

```json
{
  "id": 3,
  "usuario_id": 5,
  "username": "admin@example.com",
  "seccion": "clientes",
  "accion": "eliminar",
  "entidad_tipo": "Clientes",
  "entidad_id": 17,
  "descripcion": "Cliente 'Rancho San José' (ID: 17) eliminado",
  "datos_anteriores": {
    "nombre": "Rancho San José",
    "empresa": "Agrícola del Norte",
    "ubicacion": "Jalisco, México",
    "telefono": "1234567890",
    "status": 1
  },
  "datos_nuevos": null,
  "datos_adicionales": {
    "nombre": "Rancho San José"
  },
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
  "created_at": "2024-12-29 15:40:10"
}
```

### Ejemplo 4: Ver Detalles de Cliente

```json
{
  "id": 4,
  "usuario_id": 5,
  "username": "admin@example.com",
  "seccion": "clientes",
  "accion": "ver",
  "entidad_tipo": "Clientes",
  "entidad_id": 17,
  "descripcion": "Visualización de detalles del cliente 'Rancho San José' (ID: 17)",
  "datos_anteriores": null,
  "datos_nuevos": null,
  "datos_adicionales": {
    "nombre": "Rancho San José"
  },
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
  "created_at": "2024-12-29 15:25:30"
}
```

### Ejemplo 5: Ver Lista de Clientes

```json
{
  "id": 5,
  "usuario_id": 5,
  "username": "admin@example.com",
  "seccion": "clientes",
  "accion": "ver_lista",
  "entidad_tipo": "Clientes",
  "entidad_id": null,
  "descripcion": "Visualización de lista de clientes",
  "datos_anteriores": null,
  "datos_nuevos": null,
  "datos_adicionales": {
    "total_clientes": 42
  },
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
  "created_at": "2024-12-29 15:20:15"
}
```

---

## Campos Excluidos del Log

Por seguridad y privacidad, los siguientes campos **NO se registran** en los logs:

- ❌ `password`: Contraseñas (nunca se registran)
- ❌ `remember_token`: Tokens de sesión
- ❌ `created_at`, `updated_at`, `deleted_at`: Timestamps del modelo (no relevantes para auditoría)
- ❌ Campos sensibles específicos de cada modelo

---

## Implementación Técnica

### Trait: `LogsPlatformActions`

El trait `LogsPlatformActions` proporciona métodos auxiliares para facilitar el logging:

```php
use App\Traits\LogsPlatformActions;

class ClientesController extends Controller
{
    use LogsPlatformActions;
    
    public function store(Request $request)
    {
        $cliente = Clientes::create([...]);
        
        $this->logPlatformAction(
            seccion: 'clientes',
            accion: 'crear',
            entidadTipo: 'Clientes',
            entidadId: $cliente->id,
            descripcion: "Cliente '{$cliente->nombre}' creado exitosamente",
            datosNuevos: $this->getModelDataForLog($cliente, ['nombre', 'empresa', ...])
        );
    }
}
```

### Métodos Disponibles

#### `logPlatformAction()`

Registra una acción en el log de la plataforma.

**Parámetros:**
- `seccion` (string): Sección de la plataforma
- `accion` (string): Tipo de acción
- `entidadTipo` (string): Tipo de entidad (nombre del modelo)
- `entidadId` (int|null): ID del registro afectado
- `descripcion` (string): Descripción legible
- `datosAnteriores` (array|null): Datos antes de la modificación
- `datosNuevos` (array|null): Datos después de la modificación
- `datosAdicionales` (array|null): Información adicional

#### `getModelDataForLog()`

Obtiene datos de un modelo para el log, excluyendo campos sensibles.

**Parámetros:**
- `model`: Instancia del modelo
- `fields` (array|null): Campos específicos a incluir (null = todos)

**Retorna:** Array con los datos del modelo

#### `getCamposModificados()`

Compara datos anteriores y nuevos para identificar campos modificados.

**Parámetros:**
- `datosAnteriores` (array): Datos antes
- `datosNuevos` (array): Datos después

**Retorna:** Array con los nombres de los campos modificados

---

## Mejoras Futuras

### Funcionalidades Pendientes

- [ ] Exportación de logs a CSV/Excel
- [ ] Filtros avanzados (búsqueda por texto en descripción)
- [ ] Estadísticas y reportes de actividad
- [ ] Retención automática de logs (eliminar logs antiguos)
- [ ] Notificaciones de acciones críticas
- [ ] Integración con sistema de alertas

### Secciones Pendientes de Implementar

- [ ] Grupos (CRUD completo)
- [ ] Zonas de Manejo (CRUD completo)
- [ ] Parcelas (CRUD completo)
- [ ] Estaciones (CRUD completo)
- [ ] Usuarios (CRUD completo)
- [ ] Cultivos (CRUD completo)
- [ ] Otras secciones según necesidad

---

## Mantenimiento

### Limpieza de Logs

Los logs se acumulan con el tiempo. Se recomienda:

1. **Retención**: Definir política de retención (ej: 1 año, 2 años)
2. **Archivado**: Mover logs antiguos a almacenamiento frío
3. **Eliminación**: Eliminar logs muy antiguos según política

### Monitoreo

- Monitorear el tamaño de la tabla `platform_logs`
- Verificar índices periódicamente
- Optimizar consultas si es necesario

---

## Conclusión

El Sistema de Logs de la Plataforma proporciona una solución completa de auditoría para rastrear todas las acciones realizadas en la plataforma, excluyendo MQTT que tiene su propio sistema. Este sistema es esencial para mantener la seguridad, cumplir con requisitos de auditoría y proporcionar trazabilidad completa de las operaciones.

---

**Última actualización:** Diciembre 2024  
**Versión del documento:** 1.0
