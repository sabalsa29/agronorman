# Manual de Usuario: Sistema de Grupos Jerárquicos

## Índice
1. [Introducción](#introducción)
2. [¿Qué son los Grupos?](#qué-son-los-grupos)
3. [Conceptos Básicos](#conceptos-básicos)
4. [Casos de Uso](#casos-de-uso)
5. [Cómo Funciona el Sistema](#cómo-funciona-el-sistema)
6. [Control de Acceso por Grupos](#control-de-acceso-por-grupos)
7. [Relación Cliente-Grupos](#relación-cliente-grupos)
8. [Dashboard de Grupos](#dashboard-de-grupos)
9. [Vista de Zonas de Manejo con Filtros Jerárquicos](#vista-de-zonas-de-manejo-con-filtros-jerárquicos)
10. [Guía de Uso](#guía-de-uso)
11. [Ejemplos Prácticos](#ejemplos-prácticos)
12. [Buenas Prácticas](#buenas-prácticas)
13. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## Introducción

El Sistema de Grupos Jerárquicos es una herramienta poderosa que permite organizar y estructurar su información de manera flexible y escalable. Este sistema le permite crear estructuras organizacionales complejas sin limitaciones de niveles, adaptándose perfectamente a diferentes tipos de organizaciones, desde empresas privadas hasta entidades gubernamentales.

---

## ¿Qué son los Grupos?

Los **Grupos** son contenedores organizacionales que permiten agrupar y categorizar sus zonas de manejo de manera jerárquica. Un grupo puede:

- **Ser independiente** (grupo raíz): No tiene un grupo padre y representa el nivel más alto de su organización.
- **Tener un grupo padre**: Forma parte de una estructura más grande.
- **Tener múltiples subgrupos**: Puede contener otros grupos dentro de él.
- **Contener zonas de manejo**: Las zonas de manejo pueden asignarse a cualquier grupo de la jerarquía.

### Características Principales

✅ **Anidación ilimitada**: Puede crear tantos niveles como necesite  
✅ **Flexibilidad total**: Adapta la estructura a su organización  
✅ **Visualización clara**: Ve la jerarquía completa en cada selección  
✅ **Búsqueda rápida**: Encuentra grupos fácilmente con búsqueda integrada  
✅ **Integración completa**: Se conecta directamente con zonas de manejo  

---

## Conceptos Básicos

### Grupo Raíz
Un grupo que no tiene padre. Es el punto de partida de su estructura jerárquica. Ejemplo: "Estado de México" o "Rancho Bello".

### Grupo Padre
Un grupo que contiene otros grupos (subgrupos). Ejemplo: "Región Toluca" es padre de "Municipio de Toluca".

### Subgrupo
Un grupo que pertenece a otro grupo. Ejemplo: "Municipio de Toluca" es subgrupo de "Región Toluca".

### Ruta Completa
La representación visual de toda la jerarquía desde la raíz hasta el grupo actual. Ejemplo: "Estado de México > Región Toluca > Municipio de Toluca".

### Zona de Manejo
Una unidad operativa que puede asignarse a cualquier grupo de la jerarquía.

---

## Casos de Uso

### Caso 1: Empresa Privada (Agricultura)

**Ejemplo: Rancho Bello**

Una empresa agrícola privada necesita organizar sus operaciones en múltiples niveles:

- **Nivel 1 (Raíz)**: Rancho Bello
- **Nivel 2**: Huertas (Rancho Bello Uno, Rancho Bello Dos, etc.)
- **Nivel 3**: Predios (101, 102, 103, etc.)
- **Nivel 4**: Zonas de Manejo (101-Uno Naranja, 101-Dos Lima, etc.)

**Estructura resultante:**
```
Rancho Bello
  ├── Rancho Bello Uno
  │   ├── Predio 101
  │   │   ├── Zona 101-Uno Naranja
  │   │   └── Zona 101-Dos Lima
  │   └── Predio 102
  │       └── Zona 102-Toronja
  └── Rancho Bello Dos
      └── Predio 201
          └── Zona 201-Limón
```

### Caso 2: Entidad Gubernamental   -- ' ´

**Ejemplo: Estado de México**

Una entidad gubernamental necesita organizar sus programas agrícolas por región administrativa:

- **Nivel 1 (Raíz)**: Estado de México
- **Nivel 2**: Regiones (Toluca, Valle de Bravo, etc.)
- **Nivel 3**: Municipios (Toluca, Metepec, etc.)
- **Nivel 4**: Predios (identificados por códigos)
- **Nivel 5**: Zonas de Manejo

**Estructura resultante:**
```
Estado de México - usuario que puede ver todo hace abajo 
  ├── Región Toluca - usuario que puede ver solo lo que esta en su rama
  │   ├── Municipio de Toluca - usuario que puede ver solo lo que esta en su rama
  │   │   ├── Predio 001 - usuario que puede ver solo lo que esta en su rama
  │   │   │   └── Zona Manejo A  - usuario que puede ver solo su zona de manejo
  │   │   └── Predio 002
  │   │       └── Zona Manejo B
  │   └── Municipio de Metepec
  │       └── Predio 003
  └── Región Valle de Bravo
      └── Municipio de Valle de Bravo
          └── Predio 004
```

**Nota:** El sistema ahora implementa **control de acceso basado en jerarquía de grupos**. Cada usuario puede ser asignado a un grupo específico, y automáticamente tendrá acceso a ver y gestionar ese grupo y todos sus descendientes en la jerarquía. Ver más detalles en la sección [Control de Acceso por Grupos](#control-de-acceso-por-grupos).

### Caso 3: Organización Simple

**Ejemplo: Pequeña Empresa**

Una empresa pequeña puede usar solo dos niveles:

- **Nivel 1 (Raíz)**: Nombre de la empresa
- **Nivel 2**: Zonas de Manejo directamente

**Estructura resultante:**
```
Mi Empresa Agrícola
  ├── Zona Norte
  ├── Zona Sur
  └── Zona Este
```

---

## Cómo Funciona el Sistema

### Principio de Jerarquía

El sistema funciona como un árbol invertido:

1. **La raíz está arriba**: Los grupos principales (sin padre) están en el nivel superior.
2. **Los niveles descienden**: Cada nivel puede tener múltiples subgrupos.
3. **Sin límite de profundidad**: Puede crear tantos niveles como necesite.
4. **Cada grupo es independiente**: Puede tener su propio nombre, estatus y subgrupos.

### Prevención de Errores

El sistema incluye protecciones automáticas:

- **No permite ciclos**: No puede asignar un grupo como padre de su propio padre o abuelo.
- **No permite auto-referencias**: Un grupo no puede ser su propio padre.
- **Validación automática**: El sistema verifica que las relaciones sean válidas antes de guardar.

### Visualización de Rutas

Cuando selecciona un grupo padre, el sistema muestra la **ruta completa** desde la raíz hasta ese grupo. Esto le ayuda a:

- Entender la posición del grupo en la jerarquía
- Evitar duplicados o confusiones
- Mantener consistencia en la organización

---

## Control de Acceso por Grupos

El sistema implementa un **sistema de permisos basado en jerarquía de grupos** que permite controlar qué información puede ver y gestionar cada usuario según su posición en la estructura organizacional.

### ¿Cómo Funciona?

Cada usuario puede ser asignado a un grupo específico. Una vez asignado, el usuario automáticamente tiene acceso a:

- ✅ **Su grupo asignado**
- ✅ **Todos los subgrupos** (grupos hijos)
- ✅ **Todos los descendientes** en todos los niveles (subgrupos de subgrupos, etc.)
- ✅ **Todas las zonas de manejo** que pertenecen a su grupo y sus descendientes

### Ejemplo de Permisos

Basado en la estructura del ejemplo anterior:

| Usuario Asignado a | Puede Ver |
|-------------------|-----------|
| **Estado de México** | Todo: Región Toluca, Región Valle de Bravo, todos los municipios, predios y zonas de manejo |
| **Región Toluca** | Solo su rama: Municipio de Toluca, Municipio de Metepec, Predio 001, Predio 002, Predio 003, y todas sus zonas de manejo |
| **Municipio de Toluca** | Solo su rama: Predio 001, Predio 002, y sus zonas de manejo (Zona Manejo A, Zona Manejo B) |
| **Predio 001** | Solo su rama: Zona Manejo A |
| **Zona Manejo A** | Solo su zona de manejo |

### Reglas Especiales

1. **Super Administrador**: Los usuarios con rol de Super Administrador pueden ver y gestionar **todos los grupos y zonas de manejo**, independientemente de su asignación de grupo.

2. **Usuario sin Grupo**: Si un usuario no tiene un grupo asignado, solo puede ver las zonas de manejo a las que está asignado directamente (sistema de permisos anterior).

3. **Herencia Jerárquica**: El acceso se hereda hacia abajo en la jerarquía. Un usuario asignado a un grupo padre puede ver todo lo que está debajo, pero no puede ver grupos hermanos o ramas paralelas.

### Asignar Grupo a un Usuario

Para asignar un grupo a un usuario:

1. Acceda al módulo de **Usuarios** del cliente (`/clientes/{id}/usuarios`)
2. Edite el usuario deseado o cree uno nuevo
3. En el campo **Grupo**, seleccione el grupo apropiado
4. **Restricción por Cliente**: 
   - Si es **Super Administrador**: Verá todos los grupos disponibles
   - Si **NO es Super Admin**: Solo verá grupos que pertenecen al cliente del usuario
   - Si el cliente tiene grupos asignados, verá esos grupos padre y todos sus descendientes
   - Si el cliente no tiene grupos asignados, no verá ningún grupo disponible
5. Guarde los cambios

**Nota:** Solo los usuarios con permisos de administración pueden asignar grupos a otros usuarios. El sistema valida automáticamente que el grupo seleccionado pertenezca al cliente antes de guardar.

### Impacto en el Sistema

El control de acceso por grupos afecta:

- 📋 **Listado de Grupos**: Solo se muestran los grupos a los que el usuario tiene acceso
- 📋 **Listado de Zonas de Manejo**: Solo se muestran las zonas de manejo de los grupos permitidos
- 📊 **Reportes y Dashboards**: Los datos se filtran automáticamente según el grupo del usuario
- ✏️ **Creación/Edición**: Los usuarios solo pueden crear o editar grupos y zonas dentro de su rama permitida
- 🔍 **Búsquedas**: Las búsquedas solo retornan resultados dentro del alcance del usuario

### Ventajas del Sistema

✅ **Seguridad**: Cada usuario solo ve la información relevante para su área de responsabilidad  
✅ **Escalabilidad**: Funciona con estructuras de cualquier tamaño y profundidad  
✅ **Simplicidad**: No requiere configurar permisos individuales para cada elemento  
✅ **Mantenibilidad**: Cambiar la asignación de grupo actualiza automáticamente todos los permisos  
✅ **Flexibilidad**: Compatible con el sistema anterior de asignación directa de zonas de manejo

---

## Relación Cliente-Grupos

### ¿Qué es la Relación Cliente-Grupos?

La relación Cliente-Grupos permite asignar grupos padre (raíz) a un cliente específico. Esta relación establece qué grupos están disponibles para los usuarios de ese cliente, proporcionando una capa adicional de organización y control de acceso.

### ¿Por qué es Importante?

En el sistema, un **Cliente** representa un proyecto o empresa. Al asignar grupos padre a un cliente, se establece una relación clara entre el proyecto y la estructura organizacional de grupos, lo que permite:

1. **Gestión Centralizada**: El cliente define qué grupos están disponibles para sus usuarios
2. **Restricción Automática**: Los usuarios del cliente solo pueden ser asignados a grupos que pertenecen a su cliente
3. **Organización por Proyecto**: Cada cliente puede tener sus propios grupos independientes
4. **Control de Acceso**: Previene que usuarios de un cliente accedan a grupos de otros clientes

### ¿Cómo Funciona?

#### Asignación de Grupos a Clientes

1. **Acceso**: Solo el Super Administrador puede gestionar grupos de clientes
2. **Ubicación**: En la lista de clientes (`/clientes`), cada cliente tiene un icono de grupos (👥)
3. **Selección**: Solo se pueden asignar **grupos padre (raíz)** al cliente
4. **Múltiples Grupos**: Un cliente puede tener múltiples grupos padre asignados

#### Restricción en Asignación de Usuarios

Cuando se crea o edita un usuario de un cliente:

- **Super Administrador**: Puede asignar cualquier grupo disponible
- **Otros usuarios**: Solo pueden ver y asignar grupos que pertenecen al cliente del usuario
- **Validación Automática**: El sistema valida que el grupo seleccionado pertenezca al cliente antes de guardar

#### Jerarquía de Acceso

Al asignar un grupo padre a un cliente:
- El cliente tiene acceso a **ese grupo padre y todos sus subgrupos descendientes**
- Los usuarios del cliente pueden ser asignados a cualquier grupo dentro de esa jerarquía
- Los usuarios solo ven grupos de su cliente en los formularios de creación/edición

### Ejemplo Práctico

**Escenario**: Cliente "Agricultura del Norte" con grupo padre "Rancho San José"

1. **Asignación Inicial**:
   - Super Admin asigna el grupo "Rancho San José" al cliente "Agricultura del Norte"

2. **Estructura de Grupos**:
   ```
   Rancho San José (grupo padre asignado al cliente)
   ├── Unidad 1
   │   └── Sección 1
   └── Unidad 2
       └── Sección 2
   ```

3. **Creación de Usuario**:
   - Al crear un usuario para "Agricultura del Norte", el dropdown de grupos mostrará:
     - Rancho San José
     - Unidad 1
     - Unidad 2
     - Sección 1
     - Sección 2
   - **NO** mostrará grupos de otros clientes

4. **Resultado**:
   - Todos los usuarios de "Agricultura del Norte" solo pueden ser asignados a grupos dentro de "Rancho San José"
   - Esto garantiza que no accedan accidentalmente a grupos de otros clientes

### Beneficios de la Relación Cliente-Grupos

✅ **Seguridad**: Previene acceso cruzado entre clientes  
✅ **Organización**: Cada cliente tiene su propio conjunto de grupos  
✅ **Simplicidad**: No es necesario asignar grupo por grupo a cada usuario  
✅ **Escalabilidad**: Fácil agregar nuevos grupos al cliente sin modificar usuarios existentes  
✅ **Auditoría**: Registro claro de qué grupos pertenecen a cada cliente  

### Gestión de Grupos de Clientes

#### Cómo Asignar Grupos a un Cliente

1. Navegue a `/clientes`
2. Encuentre el cliente deseado
3. Haga clic en el icono de grupos (👥) en la columna "Actions"
4. Seleccione uno o más grupos padre del dropdown
5. Haga clic en "Guardar Grupos"

**Nota**: Solo se pueden seleccionar grupos padre (raíz). Al asignar un grupo padre, el cliente automáticamente tiene acceso a toda su jerarquía descendiente.

#### Ver Grupos Asignados

En la pantalla de gestión de grupos del cliente, verá:
- Lista de todos los grupos padre disponibles
- Grupos ya asignados al cliente (pre-seleccionados)
- Posibilidad de agregar o quitar grupos

---

## Dashboard de Grupos

El sistema incluye un **Dashboard de Grupos** que proporciona una vista jerárquica completa de la estructura organizacional, mostrando grupos, usuarios asignados y zonas de manejo en un formato de árbol visual.

### Características del Dashboard

✅ **Vista Jerárquica Completa**: Muestra toda la estructura de grupos en formato de árbol  
✅ **Información Consolidada**: Visualiza grupos, usuarios y zonas de manejo en un solo lugar  
✅ **Navegación Visual**: Usa caracteres ASCII para representar la jerarquía (`├──`, `└──`, `│`)  
✅ **Control de Acceso**: Solo muestra los grupos a los que el usuario tiene acceso  
✅ **Información de Estado**: Muestra el estatus (activo/inactivo) de cada grupo  

### ¿Cómo Acceder?

**Desde el Menú:**
1. Navegue a **Estaciones de medición** en el menú lateral
2. Haga clic en **Grupos**
3. En la página de listado de grupos, haga clic en el botón **"Ver Dashboard"**

**URL Directa:**
```
/grupos/dashboard
```

### ¿Qué Muestra el Dashboard?

El dashboard muestra para cada grupo:

- **Nombre del grupo**
- **Estatus** (Activo/Inactivo)
- **Usuarios asignados** al grupo (con nombre y email)
- **Zonas de manejo** asignadas al grupo
- **Subgrupos** anidados con toda su información

**Ejemplo de visualización:**
```
├── Rancho San José [Activo]
│   ├── Usuarios: Juan Pérez (juan@example.com)
│   ├── Zonas: Zona Norte, Zona Sur
│   └── Subgrupos:
│       ├── Unidad 1 [Activo]
│       │   ├── Usuarios: María García (maria@example.com)
│       │   ├── Zonas: Zona 1A, Zona 1B
│       │   └── Subgrupos:
│       │       └── Subgrupo A [Activo]
│       │           └── Zonas: Zona A1
│       └── Unidad 2 [Activo]
│           └── Zonas: Zona 2A
```

### Ventajas del Dashboard

✅ **Visión General**: Ve toda la estructura organizacional de un vistazo  
✅ **Gestión Eficiente**: Identifica rápidamente usuarios y zonas por grupo  
✅ **Análisis Rápido**: Detecta grupos sin usuarios o zonas asignadas  
✅ **Documentación Visual**: Útil para documentar la estructura organizacional  

---

## Vista de Zonas de Manejo con Filtros Jerárquicos

El sistema incluye una vista simplificada de zonas de manejo que permite acceder rápidamente a las zonas disponibles sin necesidad de múltiples filtros. Esta vista implementa un **sistema de filtros de dos niveles** que respeta la jerarquía de grupos.

### Características Principales

✅ **Filtro de Grupo Padre (Fijo)**: Muestra el grupo raíz al que pertenece el usuario, siempre visible y no modificable  
✅ **Filtro de Subgrupos (Dinámico)**: Permite filtrar por subgrupos específicos con visualización jerárquica  
✅ **Visualización Jerárquica**: Los subgrupos se muestran con formato de árbol para entender la estructura  
✅ **Acceso Rápido**: Un solo clic en una zona carga toda la información del dashboard  
✅ **Filtrado Inteligente**: Muestra solo las zonas accesibles según los permisos del usuario

### ¿Cómo Acceder?

**Desde el Menú:**
1. Navegue a **Estaciones de medición** en el menú lateral
2. Haga clic en **Mis Zonas de Manejo**

**URL Directa:**
```
/grupos/zonas-manejo
```

**Nota:** Esta opción aparece en el menú solo si el usuario tiene permisos para ver grupos (`estaciones.grupos`).

### ¿Cómo Funciona?

La vista de "Mis Zonas de Manejo" (`/grupos/zonas-manejo`) presenta dos filtros:

#### 1. Filtro de Grupo Padre (Fijo)

- **Ubicación**: Campo deshabilitado en la parte superior izquierda
- **Contenido**: Muestra el nombre del grupo raíz al que pertenece el usuario
- **Comportamiento**: 
  - Siempre visible
  - No se puede modificar (campo deshabilitado)
  - Fondo gris para indicar que es informativo
- **Propósito**: Proporcionar contexto sobre el grupo principal al que pertenece el usuario

**Ejemplo:**
```
Grupo Padre: [Rancho San José] (deshabilitado)
```

#### 2. Filtro de Subgrupos (Dinámico)

- **Ubicación**: Campo de selección a la derecha del grupo padre
- **Contenido**: Lista desplegable con todos los subgrupos del grupo raíz
- **Formato Visual**: Los subgrupos se muestran con caracteres especiales que indican la jerarquía:
  - `├──` para subgrupos intermedios
  - `└──` para el último subgrupo en un nivel
  - `│` para indicar continuidad vertical
  - Espacios para mostrar niveles anidados

**Ejemplo de visualización:**
```
Filtrar por Subgrupo:
├── Unidad 1 (5 zonas)
│   ├── Subgrupo A (2 zonas)
│   └── Subgrupo B (3 zonas)
├── Unidad 2 (8 zonas)
│   ├── Subgrupo C (4 zonas)
│   │   └── Sub-subgrupo (1 zona)
│   └── Subgrupo D (3 zonas)
└── Unidad 3 (3 zonas)
```

### Comportamiento del Filtrado

#### Sin Filtro de Subgrupo Seleccionado

Cuando no se selecciona ningún subgrupo (opción "Todos los subgrupos"):

- Se muestran **todas las zonas de manejo** que pertenecen al grupo raíz y a todos sus subgrupos
- Incluye zonas asignadas directamente al grupo raíz
- Incluye zonas asignadas a cualquier subgrupo en cualquier nivel

**Ejemplo:**
- Grupo Raíz: "Rancho San José"
- Subgrupos: "Unidad 1", "Unidad 2", "Unidad 3"
- Resultado: Se muestran todas las zonas de "Rancho San José", "Unidad 1", "Unidad 2", "Unidad 3" y cualquier subgrupo anidado

#### Con Filtro de Subgrupo Seleccionado

Cuando se selecciona un subgrupo específico:

- Se muestran **solo las zonas de manejo** que pertenecen a ese subgrupo y a sus descendientes
- No se muestran zonas de otros subgrupos hermanos
- No se muestran zonas asignadas directamente al grupo raíz (a menos que el subgrupo seleccionado sea el grupo raíz)

**Ejemplo:**
- Grupo Raíz: "Rancho San José"
- Subgrupo Seleccionado: "Unidad 1"
- Subgrupos de "Unidad 1": "Subgrupo A", "Subgrupo B"
- Resultado: Se muestran solo las zonas de "Unidad 1", "Subgrupo A" y "Subgrupo B"

### Acceso a las Zonas

Cada zona de manejo se muestra como una tarjeta clickeable que incluye:

- **Nombre de la zona**
- **Cliente** asociado
- **Parcela** asociada
- **Tipo de cultivo**
- **Grupo** al que pertenece (si está asignada)

Al hacer clic en una zona, el sistema automáticamente:

1. Carga el dashboard completo de la zona
2. Pre-llena todos los parámetros necesarios:
   - `cliente_id`
   - `parcela_id`
   - `zona_manejo_id`
   - `tipo_cultivo_id`
   - `etapa_fenologica_id`
   - `periodo` (por defecto: 1)
3. Redirige a la vista del dashboard con toda la información

### Casos Especiales

#### Usuario Super Administrador

- **Grupo Padre**: Si hay múltiples grupos raíz, puede seleccionar cuál ver usando el parámetro `grupo_raiz_id` en la URL
- **Subgrupos**: Ve todos los subgrupos de todos los grupos raíz del sistema
- **Zonas**: Ve todas las zonas del sistema cuando no hay filtro, o solo las del grupo/subgrupo seleccionado

#### Usuario con Grupo Asignado

- **Grupo Padre**: Siempre muestra su grupo raíz (el grupo más alto en la jerarquía al que pertenece)
- **Subgrupos**: Ve solo los subgrupos de su grupo raíz y descendientes
- **Zonas**: Ve solo las zonas de su grupo y descendientes

#### Usuario sin Grupo Asignado

- **Grupo Padre**: Se determina automáticamente desde las zonas a las que tiene acceso directo
- **Subgrupos**: Ve solo los subgrupos de los grupos asociados a sus zonas
- **Zonas**: Ve solo las zonas a las que tiene acceso directo

### Ventajas del Sistema de Filtros

✅ **Simplicidad**: No requiere múltiples filtros como en otras vistas  
✅ **Contexto Visual**: Siempre sabe en qué grupo raíz está trabajando  
✅ **Navegación Rápida**: Filtra por subgrupos con un solo clic  
✅ **Jerarquía Clara**: El formato visual muestra la estructura completa  
✅ **Acceso Directo**: Un clic lleva directamente al dashboard de la zona  
✅ **Filtrado Inteligente**: Respeta automáticamente los permisos del usuario

### Ejemplo de Uso

**Escenario:** Un usuario asignado al grupo "Rancho San José" necesita ver las zonas de "Unidad 1".

**Proceso:**

1. Accede a "Mis Zonas de Manejo" desde el menú (Estaciones de medición > Mis Zonas de Manejo)
2. Ve el campo "Grupo Padre" mostrando "Rancho San José" (deshabilitado)
3. En el filtro "Filtrar por Subgrupo", selecciona "Unidad 1"
4. La página se recarga automáticamente mostrando solo las zonas de "Unidad 1" y sus subgrupos
5. Hace clic en una zona para ver su dashboard completo

**Resultado:** Vista filtrada y acceso rápido a la información específica que necesita.

### Detalles Técnicos

#### Parámetros de URL

La vista acepta los siguientes parámetros opcionales en la URL:

- `grupo_raiz_id`: Para super administradores, permite seleccionar qué grupo raíz ver
- `subgrupo_id`: Filtra las zonas por un subgrupo específico

**Ejemplo:**
```
/grupos/zonas-manejo?grupo_raiz_id=1&subgrupo_id=5
```

#### Filtrado de Zonas

El sistema aplica el siguiente algoritmo de filtrado:

1. **Identificación del Grupo Raíz**: Determina el grupo raíz del usuario basándose en:
   - Su grupo asignado (si tiene uno)
   - Las zonas a las que tiene acceso directo (si no tiene grupo)
   - Todos los grupos raíz (si es super administrador)

2. **Construcción del Árbol de Subgrupos**: Recursivamente construye la jerarquía de subgrupos con:
   - Conteo de zonas por subgrupo
   - Formato visual jerárquico
   - Filtrado por permisos del usuario

3. **Aplicación de Filtros**: 
   - Si no hay subgrupo seleccionado: muestra todas las zonas del grupo raíz y descendientes
   - Si hay subgrupo seleccionado: muestra solo las zonas del subgrupo y sus descendientes

#### Rendimiento

- El sistema carga eficientemente las relaciones necesarias usando `with()` para evitar consultas N+1
- Los subgrupos se construyen recursivamente solo cuando es necesario
- El filtrado se realiza a nivel de base de datos para optimizar el rendimiento

---

## Guía de Uso

### Crear un Grupo Raíz

Un grupo raíz es el punto de partida de su estructura organizacional.

**Pasos:**

1. Acceda al menú de **Grupos**
2. Haga clic en **Crear Nuevo Grupo**
3. Ingrese el **Nombre** del grupo (ejemplo: "Estado de México")
4. Deje el campo **Grupo Padre** vacío o seleccione "(Sin grupo padre - Grupo raíz)"
5. Active o desactive el **Estatus** según corresponda
6. Haga clic en **Agregar**

**Resultado:** Se crea un grupo independiente que puede servir como base para subgrupos.

### Crear un Subgrupo

Un subgrupo pertenece a un grupo padre y hereda su contexto organizacional.

**Pasos:**

1. Acceda al menú de **Grupos**
2. Haga clic en **Crear Nuevo Grupo**
3. Ingrese el **Nombre** del subgrupo (ejemplo: "Región Toluca")
4. En el campo **Grupo Padre**, use la búsqueda para encontrar y seleccionar el grupo padre (ejemplo: "Estado de México")
5. Active o desactive el **Estatus** según corresponda
6. Haga clic en **Agregar**

**Resultado:** Se crea un subgrupo que aparece en la estructura bajo su grupo padre.

**Nota:** El campo de búsqueda le permite escribir para encontrar rápidamente el grupo padre deseado, incluso en estructuras grandes.

### Editar un Grupo Existente

Puede modificar cualquier grupo en cualquier momento.

**Pasos:**

1. Acceda al menú de **Grupos**
2. Encuentre el grupo que desea editar en la lista
3. Haga clic en el ícono de **Editar** (lápiz)
4. Modifique los campos que necesite:
   - **Nombre**: Puede cambiar el nombre del grupo
   - **Grupo Padre**: Puede cambiar el padre o convertirlo en grupo raíz
   - **Estatus**: Puede activar o desactivar el grupo
5. Haga clic en **Actualizar**

**Restricciones al editar:**
- No puede asignar como padre a un grupo que es su hijo o descendiente
- No puede asignar el mismo grupo como su propio padre
- El sistema le mostrará solo los grupos válidos para seleccionar

### Eliminar un Grupo

**Pasos:**

1. Acceda al menú de **Grupos**
2. Encuentre el grupo que desea eliminar
3. Haga clic en el ícono de **Eliminar** (papelera)
4. Confirme la eliminación en el diálogo que aparece

**Efectos de la eliminación:**
- El grupo se elimina de la base de datos
- Los subgrupos del grupo eliminado **NO se eliminan**, pero quedan como grupos raíz (sin padre)
- Las zonas de manejo asignadas al grupo eliminado **NO se eliminan**, pero quedan sin grupo asignado

**Recomendación:** Antes de eliminar un grupo, considere reorganizar sus subgrupos y zonas de manejo.

### Asignar Grupos a un Cliente

Para asignar grupos padre a un cliente:

1. Navegue a la lista de clientes (`/clientes`)
2. Encuentre el cliente deseado
3. Haga clic en el icono de grupos (👥 `icon-collaboration`) en la columna "Actions"
4. En el formulario, seleccione uno o más grupos padre del dropdown (Select2 múltiple)
5. Haga clic en "Guardar Grupos"

**Importante**: 
- Solo puede asignar grupos padre (raíz) a clientes
- Al asignar un grupo padre, el cliente automáticamente tiene acceso a toda su jerarquía
- Un cliente puede tener múltiples grupos padre asignados
- Solo el Super Administrador puede gestionar grupos de clientes
- Esta asignación restringe qué grupos pueden ver los usuarios del cliente al crear/editar usuarios

### Asignar Zonas de Manejo a Grupos

Las zonas de manejo se asignan a grupos desde el módulo de **Zonas de Manejo**.

**Pasos:**

1. Acceda al módulo de **Zonas de Manejo** (Clientes > [Cliente] > Parcelas > [Parcela] > Zona de Manejo)
2. Cree una nueva zona o edite una existente
3. En el campo **Grupo**, seleccione el grupo al que pertenece la zona
   - El sistema solo mostrará los grupos a los que tiene acceso
   - Puede usar la búsqueda para encontrar grupos rápidamente
4. Complete los demás campos requeridos
5. Guarde los cambios

**Beneficios:**
- Organización clara de sus zonas de manejo
- Filtrado y búsqueda más eficiente
- Reportes y análisis por grupo
- Control de acceso basado en grupos
- Acceso rápido desde "Mis Zonas de Manejo"

### Ver Dashboard de Grupos

Para ver la estructura jerárquica completa de grupos:

**Pasos:**

1. Acceda al módulo de **Grupos** (Estaciones de medición > Grupos)
2. Haga clic en el botón **"Ver Dashboard"** en la parte superior de la lista
3. Explore la estructura jerárquica visual

**Alternativa:** Acceda directamente a `/grupos/dashboard`

### Acceder a Mis Zonas de Manejo

Para acceder rápidamente a las zonas de manejo con filtros jerárquicos:

**Pasos:**

1. Acceda al módulo de **Estaciones de medición** en el menú lateral
2. Haga clic en **Mis Zonas de Manejo**
3. Use los filtros para navegar por los subgrupos
4. Haga clic en cualquier zona para ver su dashboard completo

---

## Ejemplos Prácticos

### Ejemplo 1: Configurar una Nueva Operación Agrícola

**Escenario:** Usted tiene una nueva operación llamada "Agrícola del Norte" con tres huertas principales.

**Proceso:**

1. **Crear grupo raíz:**
   - Nombre: "Agrícola del Norte"
   - Grupo Padre: (vacío)

2. **Crear subgrupos (huertas):**
   - Nombre: "Huerta Norte"
     - Grupo Padre: "Agrícola del Norte"
   - Nombre: "Huerta Centro"
     - Grupo Padre: "Agrícola del Norte"
   - Nombre: "Huerta Sur"
     - Grupo Padre: "Agrícola del Norte"

3. **Crear predios dentro de cada huerta:**
   - Nombre: "Predio A1"
     - Grupo Padre: "Agrícola del Norte > Huerta Norte"
   - Nombre: "Predio A2"
     - Grupo Padre: "Agrícola del Norte > Huerta Norte"
   - (Repetir para cada huerta)

4. **Asignar zonas de manejo:**
   - Al crear cada zona de manejo, seleccionar el grupo correspondiente (ejemplo: "Agrícola del Norte > Huerta Norte > Predio A1")

**Resultado:** Una estructura organizada y escalable que puede crecer fácilmente.

### Ejemplo 2: Reorganizar una Estructura Existente

**Escenario:** Tiene grupos creados pero necesita reorganizarlos porque cambió su estructura organizacional.

**Proceso:**

1. **Identificar los grupos a reorganizar**
2. **Editar cada grupo:**
   - Cambiar el "Grupo Padre" según la nueva estructura
   - El sistema automáticamente actualiza las rutas completas
3. **Verificar las zonas de manejo:**
   - Asegurarse de que las zonas estén asignadas a los grupos correctos
   - Reasignar si es necesario

**Resultado:** Una estructura reorganizada sin perder información.

### Ejemplo 3: Expandir una Estructura Existente

**Escenario:** Su operación creció y necesita agregar nuevos niveles o grupos.

**Proceso:**

1. **Identificar dónde agregar:**
   - ¿Necesita un nuevo grupo raíz? (crear sin padre)
   - ¿Necesita un subgrupo? (crear con padre existente)
   - ¿Necesita un nuevo nivel? (crear subgrupo de un subgrupo)

2. **Crear los nuevos grupos:**
   - Seguir el mismo proceso de creación
   - El sistema automáticamente los integra en la jerarquía

3. **Asignar zonas de manejo:**
   - Asignar nuevas zonas a los nuevos grupos
   - Reasignar zonas existentes si es necesario

**Resultado:** Expansión sin afectar la estructura existente.

---

## Buenas Prácticas

### 1. Planificación Antes de Crear

Antes de comenzar a crear grupos, planifique su estructura:

- ✅ Dibuje un diagrama de su organización
- ✅ Identifique los niveles principales
- ✅ Defina nombres consistentes
- ✅ Considere el crecimiento futuro

### 2. Nomenclatura Consistente

Use nombres claros y consistentes:

- ✅ **Bueno**: "Región Toluca", "Municipio Metepec", "Predio 001"
- ❌ **Evitar**: "Toluca", "Metepec", "Predio1" (inconsistente)

### 3. Estructura Lógica

Organice de lo general a lo específico:

- ✅ **Correcto**: Estado > Región > Municipio > Predio > Zona
- ❌ **Evitar**: Predio > Estado > Zona > Región (sin lógica)

### 4. No Crear Demasiados Niveles Innecesarios

Cree solo los niveles que realmente necesita:

- ✅ **Adecuado**: 3-5 niveles para la mayoría de casos
- ⚠️ **Considerar**: Más de 6 niveles puede ser difícil de navegar

### 5. Usar Estatus para Control

Use el campo de estatus para:

- ✅ Desactivar grupos temporalmente sin eliminarlos
- ✅ Mantener historial de grupos que ya no se usan
- ✅ Facilitar la reactivación si es necesario

### 6. Revisar Regularmente

Mantenga su estructura actualizada:

- ✅ Revise periódicamente la organización
- ✅ Elimine grupos obsoletos
- ✅ Reorganice cuando sea necesario
- ✅ Documente cambios importantes

### 7. Asignar Zonas Correctamente

- ✅ Asigne cada zona de manejo a su grupo correspondiente
- ✅ Revise periódicamente las asignaciones
- ✅ Mantenga consistencia en las asignaciones

---

## Preguntas Frecuentes

### ¿Puedo tener múltiples grupos raíz?

**Sí.** Puede crear tantos grupos raíz como necesite. Cada uno representa una estructura independiente. Por ejemplo, puede tener "Rancho Bello" y "Agrícola del Sur" como dos grupos raíz separados.

### ¿Qué pasa si elimino un grupo que tiene subgrupos?

Los subgrupos **NO se eliminan**. Quedan como grupos raíz (sin padre). Deberá reorganizarlos manualmente si desea mantener la estructura.

### ¿Puedo cambiar un grupo raíz a subgrupo?

**Sí.** Al editar el grupo, simplemente seleccione un grupo padre en el campo correspondiente. El grupo se convertirá en subgrupo automáticamente.

### ¿Puedo convertir un subgrupo en grupo raíz?

**Sí.** Al editar el subgrupo, deje el campo "Grupo Padre" vacío o seleccione "(Sin grupo padre - Grupo raíz)". El grupo se convertirá en grupo raíz.

### ¿Hay un límite en el número de niveles?

**No.** Puede crear tantos niveles como necesite. Sin embargo, recomendamos mantener la estructura lo más simple posible para facilitar la navegación.

### ¿Qué pasa con las zonas de manejo si cambio el grupo padre de un grupo?

Las zonas de manejo **permanecen asignadas al mismo grupo**. Solo cambia la posición del grupo en la jerarquía, no afecta las zonas asignadas.

### ¿Puedo tener el mismo nombre para grupos diferentes?

**Sí**, técnicamente puede, pero **no se recomienda** porque puede causar confusión. Es mejor usar nombres únicos o incluir información adicional (ejemplo: "Toluca - Región" y "Toluca - Municipio").

### ¿Cómo encuentro un grupo específico en una estructura grande?

Use la función de **búsqueda** en el campo de selección. Puede escribir parte del nombre y el sistema filtrará automáticamente los grupos que coincidan.

### ¿Puedo ver toda la estructura jerárquica en un solo lugar?

**Sí.** Puede usar el **Dashboard de Grupos** (`/grupos/dashboard`) que muestra toda la estructura jerárquica en formato de árbol visual, incluyendo grupos, usuarios asignados y zonas de manejo. También puede ver las rutas completas en los campos de selección (ejemplo: "Estado de México > Región Toluca > Municipio de Toluca").

### ¿Cómo funciona el control de acceso por grupos?

Cada usuario puede ser asignado a un grupo. Una vez asignado, el usuario automáticamente puede ver y gestionar ese grupo y todos sus descendientes en la jerarquía. Los Super Administradores pueden ver todos los grupos independientemente de su asignación.

### ¿Qué pasa si un usuario no tiene grupo asignado?

Si un usuario no tiene un grupo asignado, el sistema utiliza el método anterior de permisos: el usuario solo puede ver las zonas de manejo a las que está asignado directamente a través del módulo de permisos de zonas de manejo.

### ¿Puedo cambiar el grupo de un usuario?

Sí, puede cambiar el grupo de un usuario en cualquier momento editando el usuario. El cambio se aplica inmediatamente y afecta todos los permisos del usuario.

**Importante**: Solo puede asignar grupos que pertenecen al cliente del usuario. Si el cliente tiene grupos asignados, solo verá esos grupos y sus descendientes en el dropdown. Si es Super Administrador, verá todos los grupos disponibles.

### ¿Un usuario puede ver grupos hermanos o ramas paralelas?

No. Un usuario solo puede ver su grupo asignado y todo lo que está debajo en la jerarquía. No puede ver grupos que están en el mismo nivel o en ramas diferentes, a menos que sea Super Administrador.

### ¿Qué significa "Ruta Completa"?

La ruta completa es la representación visual de toda la jerarquía desde el grupo raíz hasta el grupo actual, separada por ">". Le ayuda a entender la posición exacta de cada grupo en la estructura.

### ¿Cómo funciona la vista de "Mis Zonas de Manejo"?

La vista de "Mis Zonas de Manejo" muestra un filtro fijo del grupo padre (siempre visible, no modificable) y un filtro dinámico de subgrupos. Al seleccionar un subgrupo, se filtran las zonas para mostrar solo las de ese subgrupo y sus descendientes. Cada zona es clickeable y lleva directamente al dashboard completo.

### ¿Puedo cambiar el grupo padre en la vista de zonas de manejo?

No. El grupo padre es fijo y no modificable porque representa el grupo raíz al que pertenece el usuario. Si necesita ver zonas de otro grupo raíz, debe cambiar su asignación de grupo (si tiene permisos) o contactar a un administrador.

### ¿Qué significan los símbolos en el filtro de subgrupos?

Los símbolos (`├──`, `└──`, `│`) son caracteres ASCII que representan la estructura jerárquica de los subgrupos:
- `├──` indica un subgrupo que tiene hermanos debajo (rama intermedia)
- `└──` indica el último subgrupo en un nivel (rama final)
- `│` indica continuidad vertical en la jerarquía (línea vertical)
- Los espacios muestran los niveles de anidación (indentación)

Estos mismos símbolos se usan en el Dashboard de Grupos para mantener consistencia visual en todo el sistema.

### ¿Dónde puedo encontrar "Mis Zonas de Manejo" en el menú?

"Mis Zonas de Manejo" se encuentra en el menú lateral bajo **Estaciones de medición > Mis Zonas de Manejo**. Esta opción solo aparece si el usuario tiene permisos para ver grupos (`estaciones.grupos`).

### ¿Cómo funciona el Dashboard de Grupos?

El Dashboard de Grupos (`/grupos/dashboard`) muestra una vista jerárquica completa de todos los grupos a los que tiene acceso, incluyendo:
- Estructura de árbol con caracteres ASCII
- Usuarios asignados a cada grupo
- Zonas de manejo asignadas a cada grupo
- Estado (activo/inactivo) de cada grupo

Puede acceder desde el botón "Ver Dashboard" en la página de listado de grupos.

### ¿Qué es la relación Cliente-Grupos?

La relación Cliente-Grupos permite asignar grupos padre a un cliente específico. Esto establece qué grupos están disponibles para los usuarios de ese cliente. Al asignar un grupo padre a un cliente, todos los usuarios de ese cliente solo pueden ser asignados a grupos dentro de la jerarquía del grupo padre asignado.

### ¿Por qué solo puedo asignar grupos padre a clientes?

Los grupos padre representan el nivel más alto de la jerarquía. Al asignar un grupo padre, el cliente automáticamente tiene acceso a toda su estructura descendiente (subgrupos, sub-subgrupos, etc.). Esto simplifica la gestión y garantiza que el cliente tenga acceso completo a su estructura organizacional.

### ¿Qué pasa si un cliente no tiene grupos asignados?

Si un cliente no tiene grupos asignados, los usuarios de ese cliente no verán ningún grupo disponible en los formularios de creación/edición (a menos que sean Super Administradores). En este caso, los usuarios solo pueden acceder a zonas de manejo asignadas directamente a ellos.

### ¿Puedo asignar múltiples grupos padre a un cliente?

Sí, un cliente puede tener múltiples grupos padre asignados. Esto es útil cuando un cliente tiene múltiples proyectos o estructuras organizacionales independientes. Los usuarios del cliente podrán ser asignados a cualquier grupo dentro de cualquiera de los grupos padre asignados.

---

## Conclusión

El Sistema de Grupos Jerárquicos es una herramienta poderosa y flexible que se adapta a las necesidades de su organización. Con una planificación adecuada y siguiendo las buenas prácticas, puede crear estructuras organizacionales eficientes que faciliten la gestión de sus zonas de manejo y operaciones.

Si tiene dudas o necesita asistencia adicional, no dude en contactar al equipo de soporte técnico.

---

**Última actualización:** Diciembre 2024  
**Versión del documento:** 2.2

### Cambios en la Versión 2.2

- ✅ **Nuevo**: Relación Cliente-Grupos para gestión centralizada de grupos por proyecto
- ✅ **Nuevo**: Asignación de grupos padre a clientes desde `/clientes`
- ✅ **Nuevo**: Restricción automática de grupos disponibles según el cliente del usuario
- ✅ **Nuevo**: Validación de grupos al crear/editar usuarios
- ✅ **Mejora**: Filtrado inteligente de grupos en formularios de usuarios
- ✅ **Mejora**: Visualización mejorada de jerarquía en filtros de subgrupos
- ✅ **Mejora**: Formato más claro en dropdown de subgrupos con ruta completa

### Cambios en la Versión 2.1

- ✅ **Nuevo**: Vista simplificada de zonas de manejo con acceso rápido (`/grupos/zonas-manejo`)
- ✅ **Nuevo**: Sistema de filtros de dos niveles (grupo padre fijo + subgrupos dinámicos)
- ✅ **Nuevo**: Visualización jerárquica de subgrupos con formato de árbol (caracteres ASCII)
- ✅ **Nuevo**: Acceso directo al dashboard con un solo clic desde las zonas
- ✅ **Nuevo**: Dashboard de grupos con vista jerárquica completa (`/grupos/dashboard`)
- ✅ **Mejora**: Filtrado inteligente que respeta la jerarquía de grupos
- ✅ **Mejora**: Optimización de consultas para mejor rendimiento
- ✅ **Mejora**: Integración en el menú lateral para acceso rápido

### Cambios en la Versión 2.0

- ✅ **Nuevo**: Sistema de control de acceso basado en jerarquía de grupos
- ✅ **Nuevo**: Asignación de grupos a usuarios
- ✅ **Nuevo**: Filtrado automático de grupos y zonas de manejo según el grupo del usuario
- ✅ **Mejora**: Compatibilidad con el sistema anterior de permisos por zonas de manejo

