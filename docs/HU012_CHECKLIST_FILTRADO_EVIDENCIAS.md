# ✅ CHECKLIST HU-012: Filtrado Avanzado de Evidencias

**Historia de Usuario**: Como usuario, dependiendo del rol, quiero buscar evidencias usando filtros por criterio, responsable de publicación, fecha de publicación de la evidencia, estado y rol, para localizarlas precisa y rápidamente.

**Rama**: `HU-012-Filtrado-avanzado-de-criterios-evidencias-y-componentes`

---

## 📋 ANÁLISIS INICIAL

### ✅ Paso 1: Analizar Modelo y Relaciones Existentes
- [ ] Revisar modelo `Evidence` en `app/Models/Evidence.php`
- [ ] Verificar relaciones: `criterion()`, `evidenceState()`, `assignments()`, `activeAssignments()`
- [ ] Revisar modelo `EvidenceAssignment` y sus relaciones con `User` y `Role`
- [ ] Identificar campos disponibles para filtrado:
  - `criterio_id` (relación con Criterion)
  - `estado_evidencia_id` (relación con EvidenceState)
  - `created_at` (fecha de publicación)
  - Responsable a través de `assignments()->user()`
  - Roles a través de `assignments()->role()`

### ✅ Paso 2: Revisar Endpoints Actuales
- [ ] Verificar endpoint GET `/api/estructura/evidencias` en `routes/api.php`
- [ ] Analizar `EvidenceController::index()` actual (solo devuelve todas las evidencias)
- [ ] Verificar `EvidenceService` y métodos disponibles
- [ ] Identificar qué modificar vs qué crear nuevo

---

## 🔧 BACKEND - IMPLEMENTACIÓN

### ✅ Paso 3: Crear Request de Validación para Filtros
**Archivo**: `app/Http/Requests/FilterEvidenceRequest.php`

- [ ] Crear nuevo Request: `php artisan make:request FilterEvidenceRequest`
- [ ] Definir reglas de validación:
  ```php
  - 'criterio_id' => 'nullable|integer|exists:CRITERIO,criterio_id'
  - 'responsable_id' => 'nullable|integer|exists:USUARIO,usuario_id'
  - 'fecha_desde' => 'nullable|date'
  - 'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde'
  - 'estado_evidencia_id' => 'nullable|integer|exists:ESTADO_EVIDENCIA,estado_evidencia_id'
  - 'rol_id' => 'nullable|integer|exists:roles,id'
  - 'sort_by' => 'nullable|in:fecha,nomenclatura,descripcion,estado'
  - 'sort_order' => 'nullable|in:asc,desc'
  - 'per_page' => 'nullable|integer|min:5|max:100'
  ```
- [ ] Agregar mensajes de error personalizados en español

### ✅ Paso 4: Modificar EvidenceService
**Archivo**: `app/Services/EvidenceService.php`

- [ ] Agregar método `filterEvidences(array $filters, array $pagination)`
- [ ] Implementar Query Builder con filtros:
  ```php
  - Filtro por criterio_id
  - Filtro por estado_evidencia_id
  - Filtro por rango de fechas (created_at)
  - Filtro por responsable (JOIN con EVIDENCIA_ASIGNACION y USUARIO)
  - Filtro por rol (JOIN con EVIDENCIA_ASIGNACION y roles)
  ```
- [ ] Implementar restricción por rol del usuario autenticado:
  - SuperUsuario: ve todas las evidencias
  - Administrador/Coordinador: ve evidencias de sus carreras
  - Evaluador/Profesor: ve solo evidencias asignadas a ellos
- [ ] Agregar ordenamiento dinámico (sort_by, sort_order)
- [ ] Implementar paginación con `paginate($perPage)`
- [ ] Retornar metadata: total, current_page, last_page, per_page

### ✅ Paso 5: Crear Método en EvidenceController
**Archivo**: `app/Http/Controllers/EvidenceController.php`

- [ ] Agregar método `filter(FilterEvidenceRequest $request)`
- [ ] Inyectar usuario autenticado con `auth()->user()`
- [ ] Llamar a `EvidenceService::filterEvidences()`
- [ ] Retornar `EvidenceResource::collection()` con paginación
- [ ] Agregar manejo de excepciones

### ✅ Paso 6: Crear/Modificar EvidenceResource
**Archivo**: `app/Http/Resources/EvidenceResource.php`

- [ ] Verificar que incluya todos los campos necesarios:
  ```php
  - evidencia_id, nomenclatura, descripcion
  - criterio (con nomenclatura y descripcion)
  - estado_evidencia (nombre del estado)
  - fecha_publicacion (created_at formateado)
  - responsables (usuarios asignados)
  - roles (roles asignados)
  ```
- [ ] Asegurar que carga relaciones con `->load()` para evitar N+1 queries

### ✅ Paso 7: Crear Ruta API
**Archivo**: `routes/api.php`

- [ ] Agregar ruta POST `/api/estructura/evidencias/filter`
- [ ] Aplicar middleware `auth:sanctum`
- [ ] Aplicar middleware de permisos si corresponde
- [ ] Documentar en comentarios los parámetros esperados

---

## 📤 EXPORTACIÓN PDF Y EXCEL

### ✅ Paso 8: Instalar Dependencias
- [ ] Instalar Laravel Excel: `composer require maatwebsite/excel`
- [ ] Instalar DomPDF: `composer require barryvdh/laravel-dompdf`
- [ ] Publicar configuraciones:
  ```bash
  php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
  php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
  ```

### ✅ Paso 9: Crear Export Class para Excel
**Archivo**: `app/Exports/EvidencesExport.php`

- [ ] Crear clase: `php artisan make:export EvidencesExport`
- [ ] Implementar interface `FromCollection`, `WithHeadings`, `WithMapping`
- [ ] Definir columnas: Nomenclatura, Descripción, Criterio, Estado, Responsable, Fecha Publicación
- [ ] Aplicar formato de fecha y estilos si es necesario
- [ ] Aceptar filtros en constructor

### ✅ Paso 10: Crear Método de Exportación PDF
**Archivo**: `app/Services/EvidenceExportService.php` (nuevo)

- [ ] Crear servicio de exportación
- [ ] Método `exportToPDF(array $filters, User $user)`:
  - Obtener evidencias filtradas
  - Cargar vista Blade con datos
  - Generar PDF con DomPDF
  - Retornar como download
- [ ] Crear vista `resources/views/exports/evidences-pdf.blade.php`:
  - Tabla con evidencias filtradas
  - Encabezado con filtros aplicados
  - Fecha de generación
  - Usuario que generó el reporte

### ✅ Paso 11: Agregar Métodos de Exportación en Controller
**Archivo**: `app/Http/Controllers/EvidenceController.php`

- [ ] Método `exportExcel(FilterEvidenceRequest $request)`:
  - Validar filtros
  - Generar nombre de archivo con timestamp
  - Retornar `Excel::download(new EvidencesExport($filters), $filename)`
  
- [ ] Método `exportPDF(FilterEvidenceRequest $request)`:
  - Validar filtros
  - Llamar a `EvidenceExportService::exportToPDF()`
  - Retornar PDF como download

### ✅ Paso 12: Crear Rutas de Exportación
**Archivo**: `routes/api.php`

- [ ] POST `/api/estructura/evidencias/export/excel` → `exportExcel()`
- [ ] POST `/api/estructura/evidencias/export/pdf` → `exportPDF()`
- [ ] Aplicar middleware `auth:sanctum`
- [ ] Aplicar rate limiting si es necesario

---

## 🧪 TESTING

### ✅ Paso 13: Crear Tests Unitarios
**Archivo**: `tests/Unit/EvidenceServiceTest.php`

- [ ] Test: Filtro por criterio devuelve solo evidencias de ese criterio
- [ ] Test: Filtro por estado devuelve solo evidencias con ese estado
- [ ] Test: Filtro por rango de fechas funciona correctamente
- [ ] Test: Filtro por responsable funciona con JOIN correcto
- [ ] Test: Filtro por rol funciona con JOIN correcto
- [ ] Test: Ordenamiento ascendente/descendente funciona
- [ ] Test: Paginación devuelve número correcto de resultados

### ✅ Paso 14: Crear Tests de Feature
**Archivo**: `tests/Feature/EvidenceFilterTest.php`

- [ ] Test: Endpoint `/filter` requiere autenticación (401 sin token)
- [ ] Test: Validación rechaza fechas inválidas (422)
- [ ] Test: Validación rechaza criterio_id inexistente (422)
- [ ] Test: SuperUsuario puede filtrar todas las evidencias (200)
- [ ] Test: Coordinador solo ve evidencias de sus carreras (200)
- [ ] Test: Evaluador solo ve evidencias asignadas a él (200)
- [ ] Test: Filtro sin coincidencias retorna array vacío con mensaje
- [ ] Test: Exportación Excel retorna archivo .xlsx (200)
- [ ] Test: Exportación PDF retorna archivo .pdf (200)

### ✅ Paso 15: Ejecutar Tests
- [ ] Ejecutar: `php artisan test --filter EvidenceService`
- [ ] Ejecutar: `php artisan test --filter EvidenceFilter`
- [ ] Verificar cobertura > 80%
- [ ] Corregir errores encontrados

---

## 📝 DOCUMENTACIÓN

### ✅ Paso 16: Documentar API
**Archivo**: `docs/API_FILTRADO_EVIDENCIAS.md` (nuevo)

- [ ] Documentar endpoint POST `/api/estructura/evidencias/filter`
- [ ] Incluir todos los parámetros con ejemplos
- [ ] Documentar respuestas: 200, 401, 422, 500
- [ ] Incluir ejemplos de uso con curl y JavaScript
- [ ] Documentar endpoints de exportación

### ✅ Paso 17: Actualizar README del Backend
**Archivo**: `README.md`

- [ ] Agregar sección "Filtrado Avanzado de Evidencias"
- [ ] Mencionar dependencias instaladas (Laravel Excel, DomPDF)
- [ ] Incluir ejemplos de uso

---

## 🔐 SEGURIDAD Y PERMISOS

### ✅ Paso 18: Implementar Restricciones por Rol
- [ ] Verificar que `EvidenceService::filterEvidences()` aplica scope por rol
- [ ] SuperUsuario: acceso total
- [ ] Administrador/Coordinador: solo evidencias de carreras asignadas
- [ ] Evaluador/Profesor: solo evidencias asignadas a ellos
- [ ] Agregar test para cada rol

### ✅ Paso 19: Aplicar Rate Limiting
**Archivo**: `app/Http/Kernel.php` o `bootstrap/app.php`

- [ ] Aplicar throttle a endpoints de exportación (máx 10 por minuto)
- [ ] Evitar abuso de generación de archivos

---

## ✅ VALIDACIÓN FINAL

### ✅ Paso 20: Pruebas Manuales con Postman
- [ ] Crear colección Postman "HU-012 Filtrado Evidencias"
- [ ] Probar todos los filtros individualmente
- [ ] Probar combinación de múltiples filtros
- [ ] Probar ordenamiento asc/desc
- [ ] Probar paginación (páginas 1, 2, última)
- [ ] Probar exportación Excel (descargar y abrir)
- [ ] Probar exportación PDF (descargar y visualizar)
- [ ] Probar con diferentes roles (SuperUsuario, Coordinador, Evaluador)
- [ ] Probar mensaje "sin coincidencias"

### ✅ Paso 21: Optimización de Performance
- [ ] Verificar queries con `DB::enableQueryLog()`
- [ ] Asegurar que no hay N+1 queries (usar `->with()`)
- [ ] Agregar índices en BD si es necesario:
  ```sql
  CREATE INDEX idx_evidencia_criterio ON EVIDENCIA(criterio_id);
  CREATE INDEX idx_evidencia_estado ON EVIDENCIA(estado_evidencia_id);
  CREATE INDEX idx_evidencia_created ON EVIDENCIA(created_at);
  ```
- [ ] Probar con dataset grande (>1000 evidencias)

### ✅ Paso 22: Commit y Documentación de Cambios
- [ ] Commit de código backend: `feat(HU-012): Implementar filtrado avanzado de evidencias`
- [ ] Commit de tests: `test(HU-012): Agregar tests para filtrado de evidencias`
- [ ] Commit de exportación: `feat(HU-012): Agregar exportación PDF y Excel de evidencias`
- [ ] Commit de documentación: `docs(HU-012): Documentar API de filtrado de evidencias`

---

## 📊 RESUMEN DE ARCHIVOS A CREAR/MODIFICAR

### Nuevos Archivos (9)
1. `app/Http/Requests/FilterEvidenceRequest.php`
2. `app/Exports/EvidencesExport.php`
3. `app/Services/EvidenceExportService.php`
4. `resources/views/exports/evidences-pdf.blade.php`
5. `docs/API_FILTRADO_EVIDENCIAS.md`
6. `tests/Unit/EvidenceServiceTest.php`
7. `tests/Feature/EvidenceFilterTest.php`
8. `docs/HU012_CHECKLIST_FILTRADO_EVIDENCIAS.md` (este archivo)
9. `database/migrations/XXXX_add_indexes_to_evidencia_table.php` (opcional)

### Archivos a Modificar (4)
1. `app/Services/EvidenceService.php` - Agregar método filterEvidences()
2. `app/Http/Controllers/EvidenceController.php` - Agregar métodos filter(), exportExcel(), exportPDF()
3. `app/Http/Resources/EvidenceResource.php` - Verificar campos completos
4. `routes/api.php` - Agregar 3 nuevas rutas

---

## 🎯 CRITERIOS DE ACEPTACIÓN

- [ ] ✅ Los filtros por criterio, responsable, fecha, estado y rol funcionan correctamente
- [ ] ✅ Los filtros se pueden combinar (múltiples filtros simultáneos)
- [ ] ✅ El ordenamiento ascendente/descendente funciona
- [ ] ✅ La paginación funciona correctamente (5-100 resultados por página)
- [ ] ✅ Las validaciones rechazan datos inválidos con mensajes claros
- [ ] ✅ La restricción por rol funciona (cada usuario ve solo lo permitido)
- [ ] ✅ Mensaje "sin coincidencias" se muestra cuando no hay resultados
- [ ] ✅ Exportación a Excel genera archivo descargable con datos correctos
- [ ] ✅ Exportación a PDF genera archivo descargable con formato adecuado
- [ ] ✅ Los tests unitarios y de feature pasan al 100%
- [ ] ✅ La API está documentada con ejemplos
- [ ] ✅ No hay problemas de performance (N+1 queries resueltos)

---

**Fecha de creación**: 2026-01-09  
**Rama**: HU-012-Filtrado-avanzado-de-criterios-evidencias-y-componentes  
**Estado**: ⏳ Pendiente de implementación
