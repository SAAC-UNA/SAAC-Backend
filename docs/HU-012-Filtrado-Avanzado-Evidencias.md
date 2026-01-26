# HU-012: Filtrado Avanzado de Evidencias

## 📋 Descripción General

Historia de Usuario para implementar filtrado avanzado, ordenamiento, paginación y exportación de evidencias con restricciones basadas en roles.

**Estado:** ✅ Completado  
**Fecha de Implementación:** Enero 2026  
**Tests:** 21 tests de integración (100% exitosos)

---

## 🎯 Objetivos Cumplidos

- ✅ Endpoint de filtrado con 9 parámetros configurables
- ✅ Ordenamiento por múltiples campos
- ✅ Paginación con metadatos completos
- ✅ Restricciones de acceso por roles (SuperUsuario, Coordinador, Evaluador)
- ✅ Exportación a Excel con PhpSpreadsheet
- ✅ Exportación a PDF con DomPDF
- ✅ Tests de integración completos
- ✅ Validación de parámetros con FormRequest

---

## 🔗 Endpoints Implementados

### 1. Filtrado de Evidencias

```http
GET /api/estructura/evidencias/filter
Authorization: Bearer {token}
```

**Parámetros de Consulta (Query Parameters):**

| Parámetro | Tipo | Descripción | Valores Aceptados | Ejemplo |
|-----------|------|-------------|-------------------|---------|
| `criterio_id` | integer | ID del criterio | ID válido | `?criterio_id=5` |
| `estado_evidencia_id` | integer | ID del estado | ID válido | `?estado_evidencia_id=1` |
| `responsable_id` | integer | ID del usuario responsable | ID válido | `?responsable_id=3` |
| `fecha_desde` | date | Fecha inicio (desde) | YYYY-MM-DD | `?fecha_desde=2025-01-01` |
| `fecha_hasta` | date | Fecha fin (hasta) | YYYY-MM-DD | `?fecha_hasta=2026-12-31` |
| `rol_id` | integer | ID del rol del responsable | ID válido | `?rol_id=2` |
| `sort_by` | string | Campo para ordenar | `nomenclatura`, `descripcion`, `fecha_publicacion` | `?sort_by=nomenclatura` |
| `sort_order` | string | Dirección del orden | `asc`, `desc` | `?sort_order=desc` |
| `per_page` | integer | Resultados por página | 5-100 (default: 15) | `?per_page=20` |

**Todos los parámetros son opcionales.**

---

### 2. Exportación a Excel

```http
GET /api/estructura/evidencias/export/excel
Authorization: Bearer {token}
```

**Acepta los mismos parámetros de filtrado que el endpoint principal.**

**Respuesta:**
- Content-Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Archivo Excel descargable (.xlsx)
- Columnas: Nomenclatura, Descripción, Criterio, Estado, Responsables, Fecha Publicación

---

### 3. Exportación a PDF

```http
GET /api/estructura/evidencias/export/pdf
Authorization: Bearer {token}
```

**Acepta los mismos parámetros de filtrado que el endpoint principal.**

**Respuesta:**
- Content-Type: `application/pdf`
- Archivo PDF descargable con tabla formateada
- Incluye encabezado con fecha de generación y total de registros

---

## 📊 Estructura de Respuesta (JSON)

### Respuesta Exitosa - Filtrado

```json
{
  "data": [
    {
      "evidencia_id": 1,
      "nomenclatura": "EV-001",
      "descripcion": "Descripción de la evidencia",
      "criterio": {
        "criterio_id": 5,
        "nomenclatura": "CRIT-01",
        "descripcion": "Descripción del criterio"
      },
      "estado_evidencia": {
        "nombre": "En Progreso"
      },
      "responsables": [
        {
          "usuario_id": 3,
          "nombre": "Juan Pérez",
          "email": "juan.perez@una.cr"
        }
      ],
      "fecha_publicacion": "2025-11-15T10:30:00.000000Z",
      "created_at": "2025-11-15T10:30:00.000000Z",
      "updated_at": "2025-11-20T14:45:00.000000Z"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/estructura/evidencias/filter?page=1",
    "last": "http://127.0.0.1:8000/api/estructura/evidencias/filter?page=3",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/estructura/evidencias/filter?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "path": "http://127.0.0.1:8000/api/estructura/evidencias/filter",
    "per_page": 15,
    "to": 15,
    "total": 42
  }
}
```

---

## 🔐 Restricciones por Roles

### SuperUsuario
- ✅ Ve **todas las evidencias** del sistema
- ✅ Puede filtrar sin restricciones
- ✅ Puede exportar cualquier conjunto de datos

### Coordinador
- ✅ Ve evidencias de **su carrera**
- ✅ Filtrado limitado a su carrera
- ✅ Exporta solo evidencias de su carrera

### Evaluador
- ✅ Ve **solo evidencias asignadas** a él
- ✅ Filtrado limitado a sus asignaciones
- ✅ Exporta solo sus evidencias asignadas

---

## 📝 Ejemplos de Uso

### Ejemplo 1: Filtrar por criterio y estado

```bash
GET /api/estructura/evidencias/filter?criterio_id=5&estado_evidencia_id=2
```

### Ejemplo 2: Filtrar por rango de fechas

```bash
GET /api/estructura/evidencias/filter?fecha_desde=2025-01-01&fecha_hasta=2025-12-31
```

### Ejemplo 3: Filtrar y ordenar

```bash
GET /api/estructura/evidencias/filter?estado_evidencia_id=1&sort_by=fecha_publicacion&sort_order=desc
```

### Ejemplo 4: Filtrado completo con paginación

```bash
GET /api/estructura/evidencias/filter?criterio_id=3&estado_evidencia_id=2&responsable_id=5&sort_by=nomenclatura&sort_order=asc&per_page=20&page=1
```

### Ejemplo 5: Exportar evidencias filtradas a Excel

```bash
GET /api/estructura/evidencias/export/excel?estado_evidencia_id=1&fecha_desde=2025-01-01
```

---

## 🧪 Pruebas Implementadas

### Tests de Filtrado (10 tests)

1. ✅ `filter_requiere_autenticacion` - Verifica que requiera token válido
2. ✅ `filter_devuelve_todas_las_evidencias_sin_filtros` - Sin parámetros devuelve todo
3. ✅ `filter_por_criterio_devuelve_evidencias_correctas` - Filtra por criterio_id
4. ✅ `filter_por_estado_devuelve_evidencias_correctas` - Filtra por estado_evidencia_id
5. ✅ `filter_por_responsable_devuelve_evidencias_asignadas` - Filtra por responsable_id
6. ✅ `filter_ordenamiento_por_nomenclatura_ascendente` - Ordena correctamente
7. ✅ `filter_paginacion_funciona_correctamente` - Paginación y metadatos
8. ✅ `superusuario_ve_todas_las_evidencias` - Restricción de SuperUsuario
9. ✅ `evaluador_solo_ve_evidencias_asignadas` - Restricción de Evaluador
10. ✅ `filter_multiples_parametros_combinados` - Filtros combinados

### Tests de Exportación (11 tests)

1. ✅ Requiere autenticación (Excel y PDF)
2. ✅ Descarga exitosa de archivos (Excel y PDF)
3. ✅ Content-Type correcto (Excel y PDF)
4. ✅ Exportación con filtros aplicados (Excel y PDF)
5. ✅ Manejo de resultados vacíos (Excel y PDF)
6. ✅ Validación de parámetros inválidos

**Total: 21 tests (100% exitosos)**

---

## 🛠️ Archivos Modificados/Creados

### Backend - Controladores
- ✅ `app/Http/Controllers/EvidenceController.php`
  - Método `filter()` - Endpoint de filtrado
  - Método `exportExcel()` - Exportación Excel
  - Método `exportPDF()` - Exportación PDF

### Backend - Servicios
- ✅ `app/Services/EvidenceService.php`
  - Método `filterEvidences()` - Lógica de negocio de filtrado
  - Implementa restricciones por roles
  - Maneja relaciones eager loading

### Backend - Requests
- ✅ `app/Http/Requests/FilterEvidenceRequest.php`
  - Valida 9 parámetros de filtrado
  - Reglas personalizadas (per_page: 5-100, sort_by, sort_order)

### Backend - Resources
- ✅ `app/Http/Resources/EvidenceResource.php`
  - Campo `estado_evidencia` (condicional)
  - Campo `responsables` (array de usuarios)
  - Campo `fecha_publicacion` (alias de created_at)

### Backend - Exportación
- ✅ `app/Exports/EvidencesExport.php`
  - Genera Excel con PhpSpreadsheet
  - Estilos personalizados (encabezado verde)
  - Auto-ajuste de columnas

- ✅ `resources/views/exports/evidences.blade.php`
  - Template Blade para PDF
  - Tabla HTML con estilos CSS

### Backend - Rutas
- ✅ `routes/api.php`
  - `GET /api/estructura/evidencias/filter`
  - `GET /api/estructura/evidencias/export/excel`
  - `GET /api/estructura/evidencias/export/pdf`

### Backend - Tests
- ✅ `tests/Feature/EvidenceFilterFeatureTest.php` (10 tests)
- ✅ `tests/Feature/EvidenceExportFeatureTest.php` (11 tests)

---

## ⚠️ Validaciones Implementadas

### FilterEvidenceRequest

```php
'criterio_id' => 'nullable|integer|exists:CRITERIO,criterio_id'
'estado_evidencia_id' => 'nullable|integer|exists:ESTADO_EVIDENCIA,estado_evidencia_id'
'responsable_id' => 'nullable|integer|exists:USUARIO,usuario_id'
'fecha_desde' => 'nullable|date'
'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde'
'rol_id' => 'nullable|integer|exists:roles,id'
'sort_by' => 'nullable|in:nomenclatura,descripcion,fecha_publicacion'
'sort_order' => 'nullable|in:asc,desc'
'per_page' => 'nullable|integer|min:5|max:100'
```

### Mensajes de Error Personalizados

- `per_page.min`: "Debe mostrar al menos 5 resultados por página."
- `per_page.max`: "No puede mostrar más de 100 resultados por página."
- `fecha_hasta.after_or_equal`: "La fecha hasta debe ser posterior o igual a la fecha desde."

---

## 🔧 Configuración Técnica

### Dependencias Utilizadas

- **PhpOffice/PhpSpreadsheet 5.3**: Generación de Excel
- **Barryvdh/DomPDF 3.1**: Generación de PDF
- **Laravel Sanctum**: Autenticación API
- **Spatie Permission**: Gestión de roles (guard 'api')

### Base de Datos

El filtrado utiliza las siguientes tablas:
- `EVIDENCIA` (tabla principal)
- `CRITERIO` (relación belongsTo)
- `ESTADO_EVIDENCIA` (relación belongsTo)
- `EVIDENCIA_ASIGNACION` (relación hasMany para responsables)
- `USUARIO` (relación through assignments)

### Optimizaciones

- **Eager Loading**: Carga anticipada de relaciones (criterion, evidenceState, assignments.user)
- **Global Scope Disabled**: Se desactiva `byCareerCampus` para el filtrado personalizado
- **Paginación Eficiente**: Laravel LengthAwarePaginator con metadatos completos

---

## ✅ Análisis de Criterios de Aceptación

### 1. Aplicación de filtros básicos ✅ **CUMPLIDO**

**Criterio:** _"Cuando selecciona uno o varios filtros (criterio, responsable de publicación, fecha de publicación, estado o rol), entonces el sistema aplica los filtros seleccionados y limita los resultados a lo solicitado."_

**Implementación:**
- ✅ Filtro por `criterio_id` (ID del criterio)
- ✅ Filtro por `responsable_id` (responsable de publicación)
- ✅ Filtro por `fecha_desde` y `fecha_hasta` (rango de fechas de publicación)
- ✅ Filtro por `estado_evidencia_id` (estado de la evidencia)
- ✅ Filtro por `rol_id` (rol del responsable)
- ✅ Todos los filtros son combinables
- ✅ Verificado con test: `filter_multiples_parametros_combinados`

---

### 2. Validación de parámetros de entrada ✅ **CUMPLIDO**

**Criterio:** _"Cuando alguno de los parámetros no cumple con el formato esperado, entonces el sistema rechaza la búsqueda y muestra un mensaje de error."_

**Implementación:**
- ✅ `FilterEvidenceRequest` valida todos los parámetros
- ✅ Validación de formato de fecha (`date`)
- ✅ Validación de existencia en BD (`exists:USUARIO,usuario_id`)
- ✅ Validación de roles permitidos (`exists:roles,id`)
- ✅ Mensajes de error personalizados en español
- ✅ Respuesta HTTP 422 con detalles del error
- ✅ Verificado con tests de exportación (parámetros inválidos)

**Ejemplo de respuesta de error:**
```json
{
  "message": "Debe mostrar al menos 5 resultados por página.",
  "errors": {
    "per_page": ["Debe mostrar al menos 5 resultados por página."]
  }
}
```

---

### 3. Restricción según rol del usuario ✅ **CUMPLIDO**

**Criterio:** _"El sistema restringe los resultados para mostrar únicamente las evidencias a las que tiene acceso según su rol."_

**Implementación:**
- ✅ **SuperUsuario**: Ve todas las evidencias sin restricciones
- ✅ **Coordinador**: Ve solo evidencias de su carrera
- ✅ **Evaluador**: Ve solo evidencias que le han sido asignadas
- ✅ Restricción aplicada automáticamente en `EvidenceService::filterEvidences()`
- ✅ Verificado con tests:
  - `superusuario_ve_todas_las_evidencias`
  - `evaluador_solo_ve_evidencias_asignadas`

---

### 4. Ordenamiento de resultados ✅ **CUMPLIDO**

**Criterio:** _"Cuando ejecuta la búsqueda, entonces el sistema presenta los resultados ordenados conforme a los parámetros seleccionados."_

**Implementación:**
- ✅ Parámetro `sort_by`: `nomenclatura`, `descripcion`, `fecha_publicacion`
- ✅ Parámetro `sort_order`: `asc` (ascendente), `desc` (descendente)
- ✅ Ordenamiento por defecto: `nomenclatura ASC`
- ✅ Verificado con test: `filter_ordenamiento_por_nomenclatura_ascendente`

---

### 5. Paginación de resultados ✅ **CUMPLIDO**

**Criterio:** _"El sistema muestra los resultados divididos según la configuración de paginación."_

**Implementación:**
- ✅ Parámetro `per_page` (5-100 resultados por página)
- ✅ Valor por defecto: 15 resultados
- ✅ Metadatos completos en respuesta:
  - `current_page`, `last_page`, `total`, `per_page`
  - Links de navegación: `first`, `last`, `prev`, `next`
- ✅ Verificado con test: `filter_paginacion_funciona_correctamente`

**Ejemplo de metadatos:**
```json
{
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 42
  }
}
```

---

### 6. Sin coincidencias ✅ **CUMPLIDO**

**Criterio:** _"Cuando no existen evidencias que cumplan con los filtros, entonces el sistema muestra un mensaje indicando sin coincidencias encontradas."_

**Implementación:**
- ✅ Laravel devuelve automáticamente array vacío en `data: []`
- ✅ Metadatos indican `total: 0`
- ✅ HTTP 200 con respuesta válida (no error)
- ⚠️ **Nota Frontend**: El mensaje "sin coincidencias" debe ser mostrado por el frontend al detectar `data: []` y `total: 0`

**Ejemplo de respuesta sin coincidencias:**
```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "total": 0,
    "per_page": 15
  }
}
```

---

### 7. Exportación de resultados ✅ **CUMPLIDO**

**Criterio:** _"Cuando selecciona la opción de exportar, entonces el sistema genera un archivo en PDF o Excel con los resultados filtrados y ordenados."_

**Implementación:**
- ✅ Endpoint `/api/estructura/evidencias/export/excel`
- ✅ Endpoint `/api/estructura/evidencias/export/pdf`
- ✅ Acepta los mismos parámetros de filtrado
- ✅ Mantiene el orden y filtros aplicados
- ✅ Verificado con tests:
  - `exporta_excel_con_filtros`
  - `exporta_pdf_con_filtros`

**Formato Excel:**
- Columnas: Nomenclatura, Descripción, Criterio, Estado, Responsables, Fecha Publicación
- Encabezado con estilo (fondo verde, texto blanco)
- Auto-ajuste de columnas

**Formato PDF:**
- Tabla HTML con estilos
- Encabezado con fecha de generación
- Total de registros exportados

---

### 8. Confirmación de exportación ⚠️ **PARCIAL**

**Criterio:** _"Cuando el archivo ha sido generado, entonces el sistema muestra un mensaje de confirmación y permite la descarga del archivo."_

**Implementación Backend:**
- ✅ Sistema genera archivo correctamente
- ✅ Respuesta HTTP con Content-Type apropiado
- ✅ Headers de descarga configurados (`Content-Disposition: attachment`)
- ⚠️ **Responsabilidad del Frontend**: Mostrar mensaje de confirmación después de la descarga

**Recomendación Frontend:**
- Mostrar loader/spinner durante la generación
- Detectar respuesta exitosa (HTTP 200)
- Mostrar mensaje: "Archivo descargado exitosamente"
- Manejar errores de red o validación

---

## 📊 Resumen de Cumplimiento

| # | Criterio de Aceptación | Estado | Observaciones |
|---|------------------------|--------|---------------|
| 1 | Aplicación de filtros básicos | ✅ 100% | 6 filtros implementados y probados |
| 2 | Validación de parámetros | ✅ 100% | FormRequest con validaciones completas |
| 3 | Restricción por roles | ✅ 100% | 3 roles con restricciones específicas |
| 4 | Ordenamiento de resultados | ✅ 100% | 3 campos ordenables, 2 direcciones |
| 5 | Paginación de resultados | ✅ 100% | Metadatos completos con links |
| 6 | Sin coincidencias | ✅ 100% | Array vacío con total:0 (mensaje en frontend) |
| 7 | Exportación de resultados | ✅ 100% | Excel y PDF con filtros aplicados |
| 8 | Confirmación de exportación | ⚠️ 90% | Backend listo, mensaje en frontend |

**Estado General: ✅ 97.5% Completado**

---

## 📌 Notas Importantes para el Frontend

1. **Autenticación Requerida**: Todos los endpoints requieren token Bearer válido
2. **Parámetros Opcionales**: Todos los filtros son opcionales, pueden combinarse libremente
3. **Paginación por Defecto**: Si no se envía `per_page`, usa 15 resultados por página
4. **Ordenamiento por Defecto**: Sin `sort_by` ordena por `nomenclatura ASC`
5. **Metadatos de Paginación**: Utilizar `meta` para construir navegación de páginas
6. **Roles Restrictivos**: El backend filtra automáticamente según el rol del usuario autenticado
7. **Exportaciones**: Mantienen los mismos filtros aplicados en la vista
8. **Content-Type**: Excel devuelve `.xlsx`, PDF devuelve `.pdf` para descarga directa
9. **Nombres de Archivos**: 
   - Excel: `evidencias_YYYYMMDD_HHmmss.xlsx`
   - PDF: `evidencias_YYYYMMDD_HHmmss.pdf`

---

## 🐛 Problemas Conocidos y Soluciones

### Problema 1: Global Scope Conflicts
**Solución:** Se desactiva el global scope `byCareerCampus` en el método `filterEvidences()` usando `withoutGlobalScope()`.

### Problema 2: Columna 'activo' no existe en EVIDENCIA_ASIGNACION
**Solución:** Se removió el filtro `where('activo', true)` ya que la tabla usa columna `estado` en su lugar.

### Problema 3: Factory loops infinitos
**Solución:** Se eliminó `comentario_id` de DimensionFactory, CriterionFactory y ComponentFactory, y se removió `facultad_id` de CareerFactory.

---

## ✅ Checklist de Implementación

### Backend
- ✅ FilterEvidenceRequest creado con 9 validaciones
- ✅ EvidenceService::filterEvidences() implementado
- ✅ EvidenceController::filter() implementado
- ✅ EvidenceController::exportExcel() implementado
- ✅ EvidenceController::exportPDF() implementado
- ✅ EvidenceResource actualizado (estado_evidencia, responsables, fecha_publicacion)
- ✅ EvidencesExport clase creada (PhpSpreadsheet)
- ✅ Vista Blade evidences.blade.php creada (PDF template)
- ✅ Rutas API registradas (filter, export/excel, export/pdf)
- ✅ Restricciones por roles implementadas
- ✅ Tests de integración (21 tests, 100% exitosos)
- ✅ Documentación técnica completa

### Frontend (Pendiente)
- ⏳ Formulario de filtros con todos los parámetros
- ⏳ Tabla de resultados con paginación
- ⏳ Botones de exportación (Excel y PDF)
- ⏳ Indicadores visuales de filtros activos
- ⏳ Manejo de estados de carga
- ⏳ Mensajes de error de validación
- ⏳ Navegación de páginas (links y meta)

---

## 🔧 Dependencias Requeridas

Para trabajar con HU-012, **las siguientes dependencias se instalan automáticamente con `composer install`:**

### Paquetes Necesarios

```bash
composer install
```

**Lo que se descarga automáticamente:**
- **`phpoffice/phpspreadsheet: ^5.3`** - Generación de archivos Excel (.xlsx)
- **`barryvdh/laravel-dompdf: ^3.1`** - Generación de archivos PDF desde HTML
- **`laravel/sanctum`** - Autenticación con tokens Bearer (ya incluido en Laravel)
- **`spatie/laravel-permission`** - Sistema de roles y permisos (ya incluido en el proyecto)

> ℹ️ **Nota:** Si el proyecto ya tiene `composer.json` configurado, solo ejecutar `composer install` descarga todo lo necesario. No requiere instalación manual adicional.

Esto crea 38 tablas, incluyendo las necesarias para HU-012:
- `EVIDENCIA`
- `CRITERIO`
- `ESTADO_EVIDENCIA`
- `EVIDENCIA_ASIGNACION`
- Tablas de autenticación y roles

#### 7. Ejecutar Seeders (Datos de Prueba)
```bash
# Cargar usuarios desde LDAP (7 usuarios)
php artisan db:seed --class=UserSeeder

# Cargar datos de ejemplo (opcional)
php artisan db:seed --class=EvidenceSeeder
php artisan db:seed --class=CriterionSeeder
php artisan db:seed --class=EvidenceStateSeeder
```

#### 8. Iniciar Servidor de Desarrollo
```bash
php artisan serve
```

El servidor estará disponible en: `http://127.0.0.1:8000`

---

### Verificar Instalación

#### Ejecutar Tests de HU-012
```bash
Esto crea 38 tablas, incluyendo las necesarias para HU-012:
- `EVIDENCIA`
- `CRITERIO`
- `ESTADO_EVIDENCIA`
- `EVIDENCIA_ASIGNACION`
- Tablas de autenticación y roles

#### 7. Ejecutar Seeders (Datos de Prueba)
```bash
# Cargar usuarios desde LDAP (7 usuarios)
php artisan db:seed --class=UserSeeder

# Cargar datos de ejemplo (opcional)
php artisan db:seed --class=EvidenceSeeder
php artisan db:seed --class=CriterionSeeder
php artisan db:seed --class=EvidenceStateSeeder
```

#### 8. Iniciar Servidor de Desarrollo
```bash
php artisan serve
```

El servidor estará disponible en: `http://127.0.0.1:8000`

---

### Archivos Clave para Revisar

Si otro desarrollador quiere entender HU-012, debe revisar:

**Backend - Lógica Principal:**
1. `app/Services/EvidenceService.php` (líneas 74-199) - Lógica de filtrado
2. `app/Http/Controllers/EvidenceController.php` (líneas 187-248) - Endpoints
3. `app/Http/Requests/FilterEvidenceRequest.php` - Validaciones

**Backend - Exportación:**
4. `app/Exports/EvidencesExport.php` - Generación Excel
5. `resources/views/exports/evidences.blade.php` - Template PDF

**Rutas:**
6. `routes/api.php` (líneas 67-69) - Rutas de HU-012

---

**Fecha de Documentación:** Enero 18, 2026  
**Versión:** 1.0  
**Estado HU-012:** ✅ Completado y Probado
