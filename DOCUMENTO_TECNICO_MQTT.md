# Documento Técnico: Servidor MQTT

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura General](#arquitectura-general)
3. [Configuración del Servidor](#configuración-del-servidor)
4. [Consumo de Mensajes (Ingest)](#consumo-de-mensajes-ingest)
5. [Envío de Configuraciones](#envío-de-configuraciones)
6. [Sistema de Autenticación y Usuarios](#sistema-de-autenticación-y-usuarios)
7. [Modelos de Datos](#modelos-de-datos)
8. [Topics y Estructura de Mensajes](#topics-y-estructura-de-mensajes)
9. [Variables de Entorno](#variables-de-entorno)
10. [Seguridad](#seguridad)
11. [Logs y Auditoría](#logs-y-auditoría)
12. [Comandos Disponibles](#comandos-disponibles)
13. [Dependencias](#dependencias)

---

## Introducción

El sistema implementa un servidor MQTT para la comunicación bidireccional con estaciones IoT. El servidor MQTT permite:

- **Recepción de datos**: Las estaciones envían mediciones de sensores a través de topics MQTT
- **Envío de configuraciones**: El sistema puede enviar parámetros de configuración a las estaciones

El sistema utiliza la librería `php-mqtt/client` para la comunicación MQTT y está integrado con Laravel.

---

## Arquitectura General

### Componentes Principales

1. **MqttIngestCommand** (`app/Console/Commands/MqttIngestCommand.php`)
   - Comando que se suscribe a topics MQTT para recibir datos de las estaciones
   - Procesa los mensajes y los almacena en la tabla `measurements`
   - Se ejecuta como un proceso de larga duración (daemon)

2. **ConfiguracionMqttController** (`app/Http/Controllers/ConfiguracionMqttController.php`)
   - Controlador web para enviar configuraciones a las estaciones
   - Gestiona la autenticación especial para usuarios MQTT
   - Publica mensajes de configuración en topics específicos

3. **Sistema de Usuarios MQTT**
   - Autenticación independiente del sistema principal
   - Gestión de permisos por estación y parámetro
   - Logs de auditoría de todas las acciones

### Flujo de Datos

```
┌─────────────┐                    ┌──────────────┐
│  Estaciones │  ────(MQTT)────>   │  MQTT Broker │
│    IoT      │                    │              │
└─────────────┘                    └──────┬───────┘
                                           │
                    ┌──────────────────────┼──────────────────────┐
                    │                      │                      │
                    ▼                      ▼                      ▼
          ┌─────────────────┐   ┌──────────────────┐   ┌─────────────────┐
          │ MqttIngestCommand│   │ ConfiguracionMqtt│   │  Otras Apps     │
          │   (Consume)      │   │   Controller     │   │                 │
          └────────┬─────────┘   │   (Publica)      │   └─────────────────┘
                   │              └──────────────────┘
                   ▼
          ┌─────────────────┐
          │  Measurements   │
          │     Table       │
          └─────────────────┘
```

---

## Configuración del Servidor

### Variables de Entorno

El sistema utiliza las siguientes variables de entorno (definidas en `.env`):

```env
# Configuración del Broker MQTT
MQTT_HOST=54.160.223.97          # IP o hostname del broker MQTT
MQTT_PORT=1883                   # Puerto del broker (1883 es el puerto estándar)
MQTT_USERNAME=iotuser             # Usuario para autenticación MQTT
MQTT_PASSWORD=Vx7@pLr9#qN2sZ1u   # Contraseña para autenticación MQTT

# Configuración del Ingestor (opcional, con valores por defecto)
MQTT_CLIENT_ID=pia_ingestor_XXXXXX  # ID único del cliente (se genera aleatoriamente si no se especifica)
MQTT_TOPIC=pap/#                    # Topic al que suscribirse (por defecto: pap/#)
MQTT_QOS=1                          # Nivel de Quality of Service (0, 1 o 2)
```

### Valores por Defecto

Si las variables no están definidas, se utilizan los siguientes valores:

- `MQTT_HOST`: `127.0.0.1` (para ingestor) o `54.160.223.97` (para configuración)
- `MQTT_PORT`: `1883`
- `MQTT_TOPIC`: `pap/#`
- `MQTT_QOS`: `1`
- `MQTT_CLIENT_ID`: Se genera aleatoriamente con formato `pia_ingestor_{random}`

---

## Consumo de Mensajes (Ingest)

### Comando: `mqtt:ingest`

El comando `MqttIngestCommand` se encarga de recibir y procesar mensajes MQTT de las estaciones.

#### Ubicación
`app/Console/Commands/MqttIngestCommand.php`

#### Funcionamiento

1. **Conexión al Broker**
   - Se conecta al broker MQTT usando las credenciales configuradas
   - Configura un "Last Will" para notificar cuando el ingestor se desconecte
   - Publica su estado en `system/pia_ingestor/status` como `online`

2. **Suscripción**
   - Se suscribe al topic configurado (por defecto: `pap/#`)
   - Utiliza wildcards (`#`) para recibir mensajes de todas las estaciones
   - QoS configurable (por defecto: 1)

3. **Procesamiento de Mensajes**
   - Cada mensaje recibido se parsea como JSON
   - Se extrae el `estacion_id` (IMEI) del payload o del topic
   - Se transforman los datos según las especificaciones:
     - Temperatura NPK: `temp_npk_lv1 / 10`
     - Humedad NPK: `hum_npk_lv1 / 10`
     - pH NPK: `ph_npk_lv1 / 100`
     - Temperatura Sensor: `temp_sns_lv1 / 100`
     - Humedad Sensor: `hum_sns_lv1 / 100`
     - CO2: `co2_sns_lv1 / 100`
   - Se parsea la fecha en formato `AA/MM/DD,HH:MM:SS±ZZ`
   - Se guarda en la tabla `measurements`

4. **Manejo de Errores**
   - Si la conexión se pierde, reintenta automáticamente cada 5 segundos
   - Los errores de parsing se registran en logs pero no detienen el proceso
   - Los errores de base de datos se registran pero no afectan otros mensajes

#### Ejecución

```bash
# Ejecutar el ingestor
php artisan mqtt:ingest

# Ejecutar como daemon (recomendado en producción)
nohup php artisan mqtt:ingest > storage/logs/mqtt_ingest.log 2>&1 &

# O usando supervisor/systemd
```

#### Estructura de Datos Procesados

El comando transforma el payload MQTT en un DTO con la siguiente estructura:

```php
[
    'imei' => string,              // ID de la estación
    'transaction_id' => int|null,   // ID de transacción
    'measured_at_utc' => Carbon,   // Fecha/hora de la medición
    
    // Sensores NPK
    'temp_npk_c' => float,         // Temperatura NPK en °C
    'hum_npk_pct' => float,        // Humedad NPK en %
    'ph_npk' => float,             // pH NPK
    'cond_us_cm' => int,           // Conductividad en µS/cm
    'nit_mg_kg' => int,           // Nitrógeno en mg/kg
    'pot_mg_kg' => int,           // Potasio en mg/kg
    'phos_mg_kg' => int,          // Fósforo en mg/kg
    
    // Sensor CO2
    'temp_sns_c' => float,        // Temperatura sensor en °C
    'hum_sns_pct' => float,       // Humedad sensor en %
    'co2_ppm' => int,             // CO2 en ppm
    
    // Metadatos
    'voltaje_mv' => int,          // Voltaje en mV
    'contador_mnsj' => int,       // Contador de mensajes
    'tec' => int,                 // Tecnología (8=4G, 0=2G)
    'ARS' => string,              // ARS
    'TON' => int,                 // TON
    'CELLID' => string,           // Cell ID
    'CIT' => int,                 // CIT
    'SWV' => int,                 // Software Version
    'MNC' => string,              // Mobile Network Code
    'MCC' => string,              // Mobile Country Code
    'RAT' => string,              // Radio Access Technology
    'LAC' => string,              // Location Area Code
    'PROJECT' => string,           // Project
    'RSRP' => int,                // Reference Signal Received Power
    'RSRQ' => int,                // Reference Signal Received Quality
    
    'raw_payload' => json,        // Payload original para auditoría
]
```

---

## Envío de Configuraciones

### Controlador: `ConfiguracionMqttController`

El controlador permite enviar configuraciones a las estaciones a través de MQTT.

#### Ubicación
`app/Http/Controllers/ConfiguracionMqttController.php`

#### Funcionalidades

1. **Autenticación Especial**
   - Sistema de login independiente del sistema principal
   - Sesión separada (`configuracion_mqtt_authenticated`)
   - Middleware: `ConfiguracionMqttAuth`

2. **Envío de Configuración**
   - Método: `enviarConfiguracion()`
   - Valida los parámetros según rangos permitidos
   - Verifica permisos del usuario (estación y parámetros)
   - Publica mensaje en topic: `pap/{UUID}`

#### Parámetros de Configuración

Los siguientes parámetros pueden ser enviados a las estaciones:

| Parámetro | Descripción | Rango | Valor por Defecto |
|-----------|-------------|-------|-------------------|
| `PCF` | Parámetro de configuración F | 0-2 | 0 |
| `PCR` | Parámetro de configuración R | 0-1 | 1 |
| `PTP` | Parámetro de tiempo P | 0-7 | 0 |
| `PTC` | Parámetro de tiempo C | 15-60 | 60 |
| `PTR` | Parámetro de tiempo R | 0-23 | 0 |
| `PRS` | Parámetro de configuración S | 0-1 | 0 |

#### Estructura del Mensaje

El mensaje enviado es un JSON con los parámetros:

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

#### Configuración de Publicación

- **Topic**: `pap/{UUID}` donde `{UUID}` es el UUID de la estación
- **QoS**: 1 (garantiza al menos una entrega)
- **Retain**: `true` (el mensaje se retiene en el broker)
- **Client ID**: Generado aleatoriamente con formato `pia_config_{random}`

#### Rutas Web

```php
// Login
GET  /configuracion-mqtt/login
POST /configuracion-mqtt/login

// Pantalla principal (requiere autenticación)
GET  /configuracion-mqtt

// Enviar configuración (requiere autenticación)
POST /configuracion-mqtt/enviar

// Logs (requiere autenticación)
GET  /configuracion-mqtt/logs

// Logout (requiere autenticación)
GET  /configuracion-mqtt/logout
```

---

## Sistema de Autenticación y Usuarios

### Modelo: `ConfiguracionMqttUsuario`

#### Ubicación
`app/Models/ConfiguracionMqttUsuario.php`

#### Estructura de la Tabla

```sql
CREATE TABLE configuracion_mqtt_usuarios (
    id BIGINT PRIMARY KEY,
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255),              -- Hash bcrypt
    activo BOOLEAN DEFAULT true,
    estaciones_permitidas JSON NULL,    -- Array de IDs [1,2,3] o NULL para todas
    parametros_permitidos JSON NULL,    -- {"PCF": true, "PCR": false} o NULL para todos
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Funcionalidades

1. **Verificación de Credenciales**
   ```php
   ConfiguracionMqttUsuario::verificarCredenciales($username, $password)
   ```

2. **Permisos por Estación**
   ```php
   $usuario->tienePermisoEstacion($estacionId)
   ```
   - Si `estaciones_permitidas` es `null`, tiene acceso a todas
   - Si es un array, solo tiene acceso a las estaciones listadas

3. **Permisos por Parámetro**
   ```php
   $usuario->tienePermisoParametro($parametro)
   ```
   - Si `parametros_permitidos` es `null`, tiene acceso a todos
   - Si es un objeto, solo puede modificar los parámetros con `true`

4. **Aplicación de Permisos**
   ```php
   $usuario->aplicarPermisosParametros($parametros)
   ```
   - Aplica valores por defecto a parámetros sin permiso
   - Mantiene valores enviados para parámetros con permiso

#### Gestión de Usuarios

El controlador `ConfiguracionMqttUsuarioController` permite:

- Listar usuarios
- Crear nuevos usuarios
- Editar usuarios existentes
- Eliminar usuarios

Todas las acciones se registran en los logs de auditoría.

---

## Modelos de Datos

### 1. ConfiguracionMqttUsuario

**Tabla**: `configuracion_mqtt_usuarios`

**Campos**:
- `id`: ID único
- `username`: Nombre de usuario (único)
- `password`: Hash de la contraseña
- `activo`: Estado activo/inactivo
- `estaciones_permitidas`: JSON array de IDs o null
- `parametros_permitidos`: JSON object o null
- `created_at`, `updated_at`: Timestamps

### 2. ConfiguracionMqttLog

**Tabla**: `configuracion_mqtt_logs`

**Campos**:
- `id`: ID único
- `usuario_id`: FK a `configuracion_mqtt_usuarios` (nullable)
- `username`: Nombre de usuario (para logs de usuarios eliminados)
- `accion`: Tipo de acción (login, logout, enviar_configuracion, etc.)
- `descripcion`: Descripción de la acción
- `datos_adicionales`: JSON con información adicional
- `ip_address`: IP del cliente
- `user_agent`: User agent del navegador
- `created_at`, `updated_at`: Timestamps

**Índices**:
- `usuario_id`
- `accion`
- `created_at`

### 3. Measurement

**Tabla**: `measurements`

**Campos principales**:
- `imei`: ID de la estación
- `transaction_id`: ID de transacción
- `measured_at_utc`: Fecha/hora de la medición
- Sensores NPK: `temp_npk_c`, `hum_npk_pct`, `ph_npk`, etc.
- Sensores CO2: `temp_sns_c`, `hum_sns_pct`, `co2_ppm`
- Metadatos: `voltaje_mv`, `tec`, `CELLID`, etc.
- `raw_payload`: JSON con el payload original

---

## Topics y Estructura de Mensajes

### Topics Utilizados

#### 1. Recepción de Datos
- **Topic**: `pap/#` (wildcard para todas las estaciones)
- **Formato específico**: `pap/{UUID}` o `data/{IMEI}`
- **Dirección**: Estación → Sistema
- **QoS**: 1
- **Retain**: false

#### 2. Envío de Configuración
- **Topic**: `pap/{UUID}`
- **Dirección**: Sistema → Estación
- **QoS**: 1
- **Retain**: true (mensaje retenido)

#### 3. Estado del Ingestor
- **Topic**: `system/pia_ingestor/status`
- **Mensajes**: `online` / `offline`
- **QoS**: 0
- **Retain**: true

### Estructura de Mensajes

#### Mensaje de Datos (Estación → Sistema)

```json
{
    "estacion_id": "123456789012345",
    "transaccion_id": 12345,
    "fecha": "25/09/09,05:51:58-24",
    
    "temp_npk_lv1": 250,
    "hum_npk_lv1": 650,
    "ph_npk_lv1": 700,
    "cond_npk_lv1": 1200,
    "nit_npk_lv1": 150,
    "pot_npk_lv1": 200,
    "phos_npk_lv1": 100,
    
    "temp_sns_lv1": 2500,
    "hum_sns_lv1": 6000,
    "co2_sns_lv1": 40000,
    
    "voltaje": 3700,
    "contador_mnsj": 1234,
    "tec": 8,
    "ARS": "ABC123",
    "TON": 123456,
    "CELLID": "12345678",
    "CIT": 9876543210,
    "SWV": 100,
    "MNC": "01",
    "MCC": "310",
    "RAT": "LTE",
    "LAC": "1234",
    "PROJECT": "PIA",
    "RSRP": -85,
    "RSRQ": -10
}
```

**Notas**:
- Los valores numéricos vienen en unidades enteras (ej: temperatura * 10)
- La fecha viene en formato `AA/MM/DD,HH:MM:SS±ZZ` donde ZZ es el offset en cuartos de hora

#### Mensaje de Configuración (Sistema → Estación)

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

## Variables de Entorno

### Resumen Completo

```env
# ============================================
# CONFIGURACIÓN MQTT
# ============================================

# Broker MQTT
MQTT_HOST=54.160.223.97
MQTT_PORT=1883
MQTT_USERNAME=iotuser
MQTT_PASSWORD=Vx7@pLr9#qN2sZ1u

# Configuración del Ingestor (opcionales)
MQTT_CLIENT_ID=pia_ingestor_XXXXXX
MQTT_TOPIC=pap/#
MQTT_QOS=1
```

### Valores por Defecto

| Variable | Ingestor | Configuración |
|----------|----------|---------------|
| `MQTT_HOST` | `127.0.0.1` | `54.160.223.97` |
| `MQTT_PORT` | `1883` | `1883` |
| `MQTT_USERNAME` | `env('MQTT_USERNAME')` | `iotuser` |
| `MQTT_PASSWORD` | `env('MQTT_PASSWORD')` | `Vx7@pLr9#qN2sZ1u` |
| `MQTT_TOPIC` | `pap/#` | N/A |
| `MQTT_QOS` | `1` | `1` |
| `MQTT_CLIENT_ID` | `pia_ingestor_{random}` | `pia_config_{random}` |

---

## Seguridad

### Autenticación MQTT

1. **Credenciales del Broker**
   - Usuario y contraseña configurados en variables de entorno
   - No deben estar hardcodeadas en el código
   - Cambiar credenciales por defecto en producción

2. **Autenticación de Usuarios Web**
   - Sistema de autenticación independiente
   - Contraseñas hasheadas con bcrypt
   - Sesión separada del sistema principal

### Permisos

1. **Permisos por Estación**
   - Los usuarios pueden tener acceso restringido a ciertas estaciones
   - `null` = acceso a todas las estaciones
   - Array de IDs = solo esas estaciones

2. **Permisos por Parámetro**
   - Los usuarios pueden tener acceso restringido a ciertos parámetros
   - `null` = acceso a todos los parámetros
   - Objeto con flags = solo parámetros con `true`

### Validación

1. **Validación de Parámetros**
   - Rangos validados antes de enviar
   - Valores por defecto aplicados si no hay permiso

2. **Validación de Estaciones**
   - Verificación de existencia en base de datos
   - Verificación de permisos del usuario

### Logs de Seguridad

- Todos los intentos de login se registran (exitosos y fallidos)
- Todas las acciones se registran con IP y User Agent
- Los logs incluyen información detallada de cada operación

---

## Logs y Auditoría

### Sistema de Logs

El sistema registra todas las acciones relacionadas con MQTT en la tabla `configuracion_mqtt_logs`.

### Tipos de Acciones Registradas

1. **login**: Login exitoso
2. **login_fallido**: Intento de login fallido
3. **logout**: Cierre de sesión
4. **acceso_pantalla**: Acceso a la pantalla principal
5. **acceso_lista_usuarios**: Acceso a la lista de usuarios
6. **enviar_configuracion**: Envío de configuración a una estación
7. **crear_usuario**: Creación de nuevo usuario
8. **editar_usuario**: Edición de usuario existente
9. **eliminar_usuario**: Eliminación de usuario

### Información Registrada

Cada log incluye:
- `usuario_id`: ID del usuario (nullable para acciones sin usuario)
- `username`: Nombre de usuario
- `accion`: Tipo de acción
- `descripcion`: Descripción detallada
- `datos_adicionales`: JSON con información adicional (topic, parámetros, etc.)
- `ip_address`: IP del cliente
- `user_agent`: User agent del navegador
- `created_at`: Timestamp de la acción

### Visualización de Logs

Los logs se pueden visualizar en:
- **Ruta**: `/configuracion-mqtt/logs`
- **Vista**: `resources/views/configuracion-mqtt/logs.blade.php`
- Requiere autenticación MQTT

### Logs del Sistema (Laravel)

Además de los logs de auditoría, el sistema registra en los logs de Laravel:

- Conexiones MQTT exitosas
- Errores de conexión
- Errores de procesamiento de mensajes
- Errores de guardado en base de datos

**Ubicación**: `storage/logs/laravel.log`

---

## Comandos Disponibles

### 1. `mqtt:ingest`

Comando para iniciar el ingestor de mensajes MQTT.

```bash
php artisan mqtt:ingest
```

**Descripción**: Se suscribe a `pap/#` y guarda mediciones en la base de datos.

**Características**:
- Proceso de larga duración (daemon)
- Reintentos automáticos en caso de desconexión
- Logs detallados en consola y archivo
- Publica estado en `system/pia_ingestor/status`

**Ejecución en Producción**:

```bash
# Opción 1: nohup
nohup php artisan mqtt:ingest > storage/logs/mqtt_ingest.log 2>&1 &

# Opción 2: Supervisor
# Ver configuración de supervisor en documentación del sistema

# Opción 3: systemd
# Ver configuración de systemd en documentación del sistema
```

---

## Dependencias

### Librería PHP MQTT

**Paquete**: `php-mqtt/client`

**Versión**: `^1.7`

**Instalación**:
```bash
composer require php-mqtt/client
```

**Documentación**: https://github.com/php-mqtt/client

### Uso en el Código

```php
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// Crear cliente
$client = new MqttClient($host, $port, $clientId);

// Configurar conexión
$settings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60);

// Conectar
$client->connect($settings, true);

// Publicar
$client->publish($topic, $message, $qos, $retain);

// Suscribir
$client->subscribe($topic, $callback, $qos);

// Loop (para mantener conexión activa)
$client->loop(false, true, 1);

// Desconectar
$client->disconnect();
```

---

## Diagramas de Flujo

### Flujo de Recepción de Datos

```
┌─────────────┐
│   Estación  │
│     IoT     │
└──────┬──────┘
       │
       │ Publica mensaje en pap/{UUID}
       ▼
┌─────────────┐
│ MQTT Broker │
└──────┬──────┘
       │
       │ MqttIngestCommand suscrito a pap/#
       ▼
┌─────────────────────┐
│ MqttIngestCommand   │
│  - Parsea JSON       │
│  - Extrae IMEI       │
│  - Transforma datos  │
│  - Parsea fecha      │
└──────┬──────────────┘
       │
       │ Guarda en measurements
       ▼
┌─────────────┐
│ Measurements│
│    Table    │
└─────────────┘
```

### Flujo de Envío de Configuración

```
┌─────────────┐
│   Usuario   │
│     Web     │
└──────┬──────┘
       │
       │ POST /configuracion-mqtt/enviar
       ▼
┌─────────────────────────┐
│ ConfiguracionMqtt       │
│ Controller              │
│  - Valida parámetros    │
│  - Verifica permisos    │
│  - Aplica valores def.  │
└──────┬──────────────────┘
       │
       │ Publica en pap/{UUID}
       ▼
┌─────────────┐
│ MQTT Broker │
└──────┬──────┘
       │
       │ Mensaje retenido (retain=true)
       ▼
┌─────────────┐
│   Estación  │
│     IoT     │
└─────────────┘
```

---

## Troubleshooting

### Problemas Comunes

#### 1. El ingestor no recibe mensajes

**Verificar**:
- Conexión al broker: `telnet MQTT_HOST MQTT_PORT`
- Credenciales correctas en `.env`
- Topic correcto: verificar que las estaciones publican en `pap/#`
- QoS compatible

**Solución**:
```bash
# Verificar logs
tail -f storage/logs/laravel.log

# Verificar conexión
php artisan mqtt:ingest
```

#### 2. Error al enviar configuración

**Verificar**:
- Usuario autenticado en sesión MQTT
- Permisos del usuario (estación y parámetros)
- UUID de la estación existe
- Broker MQTT accesible

**Solución**:
- Revisar logs en `/configuracion-mqtt/logs`
- Verificar permisos del usuario
- Verificar conectividad al broker

#### 3. Mensajes no se guardan en measurements

**Verificar**:
- Estructura del JSON recibido
- Presencia de `estacion_id` en el payload
- Formato de fecha correcto
- Errores en logs de Laravel

**Solución**:
```bash
# Ver logs del ingestor
tail -f storage/logs/laravel.log | grep MQTT

# Verificar estructura del mensaje
# El ingestor muestra el contenido en consola
```

---

## Mejores Prácticas

1. **Seguridad**
   - Cambiar credenciales por defecto
   - Usar contraseñas fuertes
   - Restringir permisos según necesidad
   - Revisar logs regularmente

2. **Producción**
   - Ejecutar `mqtt:ingest` como servicio (supervisor/systemd)
   - Configurar rotación de logs
   - Monitorear conexiones MQTT
   - Implementar alertas para desconexiones

3. **Mantenimiento**
   - Revisar logs de auditoría periódicamente
   - Limpiar logs antiguos si es necesario
   - Verificar que las estaciones están enviando datos
   - Monitorear el uso de recursos del ingestor

4. **Desarrollo**
   - Usar topics de prueba separados
   - No exponer credenciales en código
   - Validar todos los inputs
   - Manejar errores apropiadamente

---

## Referencias

- **Librería MQTT**: https://github.com/php-mqtt/client
- **Protocolo MQTT**: https://mqtt.org/
- **Laravel Documentation**: https://laravel.com/docs

---

## Changelog

### Versión Actual
- Implementación completa de ingestor MQTT
- Sistema de configuración con permisos
- Logs de auditoría
- Gestión de usuarios MQTT

---

**Última actualización**: 2025-01-XX
**Mantenido por**: Equipo de Desarrollo PIA
