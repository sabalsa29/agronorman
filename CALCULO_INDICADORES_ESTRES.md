# Sistema de Cálculo de Indicadores de Estrés

## 📋 Descripción

Este sistema calcula indicadores de estrés para cultivos basándose en datos meteorológicos históricos y pronósticos. Analiza variables como temperatura, humedad relativa y CO2 durante períodos diurnos y nocturnos, clasificando las condiciones en 5 escalas de estrés.

## 🏗️ Arquitectura

### Tablas de Base de Datos

1. **`indicadores`** - Definición de indicadores por variable y momento del día
2. **`tipo_cultivo_estres`** - Parámetros de estrés por tipo de cultivo y variable
3. **`indicador_calculado`** - Resultados calculados por fecha, indicador y zona de manejo

### Modelos

-   `Indicador` - Gestión de indicadores
-   `TipoCultivoEstres` - Parámetros de estrés por tipo de cultivo
-   `IndicadorCalculado` - Resultados de cálculos

### Job Principal

-   `CalcularIndicadoresEstresJob` - Job que ejecuta los cálculos

## 🚀 Instalación y Configuración

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

### 2. Ejecutar Seeders

```bash
# Primero ejecutar seeders de dependencias
php artisan db:seed --class=VariablesMedicionSeeder
php artisan db:seed --class=TipoCultivosSeeder

# Luego ejecutar el seeder de indicadores
php artisan db:seed --class=IndicadoresSeeder
```

### 3. Configurar Scheduler

El job está configurado para ejecutarse automáticamente todos los días a las 2:00 AM en `routes/console.php`:

```php
Schedule::job(new CalcularIndicadoresEstresJob())->dailyAt('02:00');
```

## 📊 Escalas de Estrés

El sistema clasifica las condiciones en 5 escalas:

1. **Muy Bajo** (escala1) - Condiciones óptimas
2. **Bajo** (escala2) - Condiciones aceptables
3. **Óptimo** (escala3) - Condiciones ideales
4. **Alto** (escala4) - Condiciones de estrés
5. **Muy Alto** (escala5) - Condiciones críticas

## 🔧 Uso

### Ejecución Manual

```bash
# Ejecutar para fecha por defecto (ayer)
php artisan indicadores:calcular-estres

# Ejecutar para fecha específica
php artisan indicadores:calcular-estres --fecha=2024-01-15

# Ejecutar con más días de pronóstico
php artisan indicadores:calcular-estres --dias=5

# Combinar opciones
php artisan indicadores:calcular-estres --fecha=2024-01-15 --dias=3
```

### Ejecución Programada

El job se ejecuta automáticamente todos los días a las 2:00 AM. Para verificar el scheduler:

```bash
php artisan schedule:list
```

## 📈 Cálculos Realizados

### Períodos de Análisis

-   **Diurno**: Entre amanecer y atardecer
-   **Nocturno**: Antes del amanecer y después del atardecer

### Variables Analizadas

-   **Temperatura atmosférica** (°C)
-   **Humedad relativa** (%)
-   **CO2 atmosférico** (ppm)

### Fórmulas de Cálculo

Para cada escala:

```
Porcentaje = (Registros en escala / Total de registros) × 100
Horas = (Porcentaje / 100) × Minutos totales del período / 60
```

## 📋 Configuración de Parámetros

### Editar Parámetros de Estrés

Los parámetros se configuran en la tabla `tipo_cultivo_estres`:

```sql
-- Ejemplo para temperatura diurna de un cultivo
UPDATE tipo_cultivo_estres
SET muy_bajo = 10, bajo_min = 10, bajo_max = 15,
    optimo_min = 15, optimo_max = 25,
    alto_min = 25, alto_max = 30, muy_alto = 30
WHERE tipo_cultivo_id = 1 AND variable_id = 1 AND tipo = 'DIURNO';
```

### Agregar Nuevas Variables

1. Agregar la variable en `variables_medicion`
2. Crear indicadores diurnos y nocturnos
3. Configurar parámetros de estrés por tipo de cultivo

## 📊 Consulta de Resultados

### Ver Indicadores Calculados

```sql
SELECT
    ic.fecha,
    i.nombre as indicador,
    zm.nombre as zona_manejo,
    ic.escala1, ic.escala2, ic.escala3, ic.escala4, ic.escala5,
    ic.horas1, ic.horas2, ic.horas3, ic.horas4, ic.horas5
FROM indicador_calculado ic
JOIN indicadores i ON ic.indicador_id = i.id
JOIN zona_manejos zm ON ic.zonamanejo_id = zm.id
WHERE ic.fecha = '2024-01-15'
ORDER BY zm.nombre, i.nombre;
```

### Análisis por Zona de Manejo

```sql
SELECT
    zm.nombre as zona_manejo,
    COUNT(*) as total_indicadores,
    AVG(ic.escala3) as promedio_optimo,
    SUM(ic.horas3) as horas_optimas
FROM indicador_calculado ic
JOIN zona_manejos zm ON ic.zonamanejo_id = zm.id
WHERE ic.fecha = '2024-01-15'
GROUP BY zm.id, zm.nombre;
```

## 🔍 Monitoreo y Logs

### Logs del Job

Los logs se guardan en `storage/logs/laravel.log` con el prefijo `[CalcularIndicadoresEstresJob]`.

### Verificar Estado

```bash
# Ver logs recientes
tail -f storage/logs/laravel.log | grep CalcularIndicadoresEstresJob

# Ver jobs en cola
php artisan queue:work --once
```

## 🚨 Solución de Problemas

### Error: "No se encontraron parámetros de estrés"

1. Verificar que se ejecutó `IndicadoresSeeder`
2. Verificar que existen variables de medición
3. Verificar que existen tipos de cultivo

### Error: "No hay datos de predicción"

1. Verificar que existen registros en `forecast`
2. Verificar que las fechas coinciden
3. Verificar que las parcelas tienen datos

### Error: "Zona de manejo no tiene estaciones"

1. Verificar relación zona_manejos ↔ estaciones
2. Verificar que las estaciones están activas
3. Verificar que hay datos en `estacion_dato`

## 📝 Notas Técnicas

-   **Zona Horaria**: El sistema usa `America/Mexico_City`
-   **Datos Históricos**: Se usan datos de `estacion_dato`
-   **Pronósticos**: Se usan datos de `forecast_hourlies`
-   **Rendimiento**: El job procesa zonas de manejo en paralelo
-   **Tolerancia a Fallos**: Si una zona falla, continúa con las demás

## 🔄 Actualizaciones Futuras

-   [ ] Agregar más variables (radiación solar, viento, etc.)
-   [ ] Implementar alertas automáticas
-   [ ] Crear dashboard de monitoreo
-   [ ] Agregar exportación de reportes
-   [ ] Implementar cache para mejorar rendimiento
