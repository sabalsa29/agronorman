# 📋 Comandos para Gestión de Datos de Enfermedades

## 🚀 Comandos Disponibles

### 1. `diseases:populate-history` - Poblar Datos Históricos

**Descripción:** Pobla la tabla `enfermedad_horas_acumuladas_condiciones` con datos históricos procesados desde `estacion_dato`.

**Uso básico:**

```bash
php artisan diseases:populate-history
```

**Opciones disponibles:**

-   `--estacion_id=66` - Procesar solo una estación específica
-   `--start_date=2025-08-01` - Fecha de inicio (YYYY-MM-DD)
-   `--end_date=2025-08-03` - Fecha de fin (YYYY-MM-DD)
-   `--enfermedad_id=2` - Procesar solo una enfermedad específica
-   `--tipo_cultivo_id=9` - Procesar solo un tipo de cultivo específico
-   `--dry-run` - Solo mostrar qué se haría sin ejecutar cambios

**Ejemplos de uso:**

```bash
# Poblar datos de los últimos 7 días (por defecto)
php artisan diseases:populate-history

# Poblar datos de una estación específica
php artisan diseases:populate-history --estacion_id=66

# Poblar datos de un período específico
php artisan diseases:populate-history --start_date=2025-08-01 --end_date=2025-08-03

# Probar sin ejecutar cambios
php artisan diseases:populate-history --estacion_id=66 --dry-run

# Poblar datos específicos
php artisan diseases:populate-history --estacion_id=66 --tipo_cultivo_id=9 --enfermedad_id=2
```

---

### 2. `diseases:clean` - Limpiar Datos

**Descripción:** Limpia datos de las tablas de enfermedades (útil para pruebas).

**Uso básico:**

```bash
php artisan diseases:clean
```

**Opciones disponibles:**

-   `--estacion_id=66` - Limpiar solo una estación específica
-   `--start_date=2025-08-01` - Fecha de inicio (YYYY-MM-DD)
-   `--end_date=2025-08-03` - Fecha de fin (YYYY-MM-DD)
-   `--enfermedad_id=2` - Limpiar solo una enfermedad específica
-   `--tipo_cultivo_id=9` - Limpiar solo un tipo de cultivo específico
-   `--dry-run` - Solo mostrar qué se eliminaría sin ejecutar cambios

**Ejemplos de uso:**

```bash
# Ver qué se eliminaría (sin ejecutar)
php artisan diseases:clean --dry-run

# Limpiar datos de una estación específica
php artisan diseases:clean --estacion_id=66

# Limpiar datos de un período específico
php artisan diseases:clean --start_date=2025-08-01 --end_date=2025-08-03

# Limpiar datos específicos
php artisan diseases:clean --estacion_id=66 --tipo_cultivo_id=9
```

---

## 🔄 Flujo de Trabajo Recomendado

### Para Poblar Datos Históricos:

1. **Verificar datos disponibles:**

    ```bash
    php artisan tinker --execute="echo 'Datos disponibles en estacion_dato:'; echo DB::table('estacion_dato')->where('estacion_id', 66)->count();"
    ```

2. **Probar el comando en modo dry-run:**

    ```bash
    php artisan diseases:populate-history --estacion_id=66 --start_date=2025-08-01 --end_date=2025-08-03 --dry-run
    ```

3. **Ejecutar el comando:**

    ```bash
    php artisan diseases:populate-history --estacion_id=66 --start_date=2025-08-01 --end_date=2025-08-03
    ```

4. **Verificar resultados:**
    ```bash
    php artisan tinker --execute="echo 'Registros generados:'; echo DB::table('enfermedad_horas_acumuladas_condiciones')->where('estacion_id', 66)->count();"
    ```

### Para Limpiar Datos de Prueba:

1. **Verificar qué se eliminaría:**

    ```bash
    php artisan diseases:clean --estacion_id=66 --dry-run
    ```

2. **Ejecutar limpieza:**
    ```bash
    php artisan diseases:clean --estacion_id=66
    ```

---

## 📊 Tablas Involucradas

### `enfermedad_horas_acumuladas_condiciones`

-   **Propósito:** Almacena períodos históricos completados donde se cumplieron condiciones de enfermedad
-   **Campos principales:** `fecha`, `minutos`, `tipo_cultivo_id`, `enfermedad_id`, `estacion_id`

### `enfermedad_horas_condiciones`

-   **Propósito:** Almacena el estado actual de acumulación en progreso
-   **Campos principales:** `fecha_ultima_transmision`, `minutos`, `tipo_cultivo_id`, `enfermedad_id`, `estacion_id`

---

## ⚠️ Consideraciones Importantes

1. **Datos de origen:** Los comandos procesan datos de `estacion_dato` que deben tener `humedad_relativa` y `temperatura` válidos.

2. **Parámetros de riesgo:** Se usan los parámetros configurados en `tipo_cultivos_enfermedades` para determinar condiciones de enfermedad.

3. **Duplicados:** El comando `populate-history` evita insertar registros duplicados verificando fechas y parámetros.

4. **Rendimiento:** Para grandes volúmenes de datos, considera procesar por períodos más pequeños.

5. **Backup:** Antes de limpiar datos, considera hacer un backup de las tablas.

---

## 🔧 Funciones Relacionadas

### En `StationController.php`:

-   `processDiseaseAlerts()` - Procesa datos en tiempo real cuando llegan nuevos datos de estación

### En `ProcessDiseaseAlertsJob.php`:

-   Job programado que se ejecuta cada hora para procesar datos acumulados

### En `HomeController.php`:

-   `componentEnfermedades()` - Muestra los datos en el frontend
-   `generarDatosRealesDesdeEstacionDato()` - Lee datos de las tablas para el componente
