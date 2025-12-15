-- ============================================================================
-- SCRIPT DE PRUEBA: Aprobación de Criterios por Bloques
-- ============================================================================
-- Este script inserta datos de prueba para el sistema de aprobación de criterios

-- ============================================================================
-- 1. VERIFICAR DATOS EXISTENTES
-- ============================================================================

-- Ver procesos disponibles
SELECT proceso_id, nombre, fecha_inicio, fecha_fin 
FROM PROCESO 
ORDER BY proceso_id 
LIMIT 5;

-- Ver criterios disponibles
SELECT criterio_id, nomenclatura, descripcion, activo 
FROM CRITERIO 
ORDER BY criterio_id 
LIMIT 5;

-- Ver evidencias del primer criterio
SELECT 
    e.evidencia_id,
    e.criterio_id,
    e.nomenclatura,
    e.descripcion,
    e.activo
FROM EVIDENCIA e
WHERE e.criterio_id = 1
ORDER BY e.evidencia_id;

-- ============================================================================
-- 2. CREAR ASIGNACIONES DE EVIDENCIAS COMPLETADAS (ESCENARIO EXITOSO)
-- ============================================================================

-- Asignar TODAS las evidencias del Criterio 1 al usuario 1 en el proceso 1
-- y marcarlas como COMPLETADAS para que se pueda aprobar

-- Primero, eliminar asignaciones previas de prueba (si existen)
DELETE FROM EVIDENCIA_ASIGNACION 
WHERE proceso_id = 1 
  AND evidencia_id IN (SELECT evidencia_id FROM EVIDENCIA WHERE criterio_id = 1);

-- Insertar asignaciones COMPLETADAS para todas las evidencias del criterio 1
INSERT INTO EVIDENCIA_ASIGNACION 
    (proceso_id, evidencia_id, usuario_id, estado, fecha_asignacion, fecha_limite, comentario, created_at, updated_at)
SELECT 
    1 AS proceso_id,                           -- Proceso 1
    e.evidencia_id,                            -- ID de cada evidencia
    1 AS usuario_id,                           -- Usuario 1
    'completado' AS estado,                    -- ESTADO COMPLETADO (clave!)
    NOW() AS fecha_asignacion,
    DATE_ADD(NOW(), INTERVAL 30 DAY) AS fecha_limite,
    'Evidencia completada para pruebas' AS comentario,
    NOW() AS created_at,
    NOW() AS updated_at
FROM EVIDENCIA e
WHERE e.criterio_id = 1                        -- Solo evidencias del criterio 1
  AND e.activo = 1;                            -- Solo activas

-- ============================================================================
-- 3. VERIFICAR QUE LAS ASIGNACIONES SE CREARON CORRECTAMENTE
-- ============================================================================

SELECT 
    ea.evidencia_asignacion_id,
    ea.proceso_id,
    ea.evidencia_id,
    e.nomenclatura AS evidencia,
    ea.usuario_id,
    ea.estado,                                  -- Debe ser "completado"
    ea.fecha_asignacion,
    ea.comentario
FROM EVIDENCIA_ASIGNACION ea
JOIN EVIDENCIA e ON ea.evidencia_id = e.evidencia_id
WHERE e.criterio_id = 1 
  AND ea.proceso_id = 1
ORDER BY ea.evidencia_id;

-- ============================================================================
-- 4. CREAR ESCENARIO CON EVIDENCIAS INCOMPLETAS (ESCENARIO DE ERROR)
-- ============================================================================

-- Para probar el rechazo, crear asignaciones PENDIENTES en el proceso 2

-- Asignar evidencias del Criterio 2 pero dejar algunas PENDIENTES
INSERT INTO EVIDENCIA_ASIGNACION 
    (proceso_id, evidencia_id, usuario_id, estado, fecha_asignacion, fecha_limite, comentario, created_at, updated_at)
SELECT 
    2 AS proceso_id,
    e.evidencia_id,
    1 AS usuario_id,
    CASE 
        WHEN e.evidencia_id % 2 = 0 THEN 'completado'  -- Pares completadas
        ELSE 'pendiente'                                -- Impares pendientes
    END AS estado,
    NOW() AS fecha_asignacion,
    DATE_ADD(NOW(), INTERVAL 30 DAY) AS fecha_limite,
    'Evidencia de prueba mixta' AS comentario,
    NOW() AS created_at,
    NOW() AS updated_at
FROM EVIDENCIA e
WHERE e.criterio_id = 2
  AND e.activo = 1
LIMIT 4;  -- Solo 4 evidencias para tener mix

-- ============================================================================
-- 5. CONSULTAS DE VERIFICACIÓN FINAL
-- ============================================================================

-- Ver resumen de evidencias por criterio y estado
SELECT 
    c.criterio_id,
    c.nomenclatura AS criterio,
    COUNT(DISTINCT e.evidencia_id) AS total_evidencias,
    COUNT(DISTINCT CASE WHEN ea.estado = 'completado' THEN ea.evidencia_id END) AS completadas,
    COUNT(DISTINCT CASE WHEN ea.estado = 'pendiente' THEN ea.evidencia_id END) AS pendientes
FROM CRITERIO c
JOIN EVIDENCIA e ON c.criterio_id = e.criterio_id
LEFT JOIN EVIDENCIA_ASIGNACION ea ON e.evidencia_id = ea.evidencia_id AND ea.proceso_id = 1
WHERE c.criterio_id IN (1, 2)
  AND e.activo = 1
GROUP BY c.criterio_id, c.nomenclatura
ORDER BY c.criterio_id;

-- ============================================================================
-- LISTO PARA PROBAR!
-- ============================================================================

-- Ahora puedes usar estos endpoints:

-- ✅ APROBAR CRITERIO 1 (debe funcionar porque todas están completadas)
-- POST http://localhost:8000/api/criterios/1/aprobar
-- Body: { "proceso_id": 1, "comentario": "Todas completas" }

-- ❌ APROBAR CRITERIO 2 (debe fallar porque hay pendientes)
-- POST http://localhost:8000/api/criterios/2/aprobar
-- Body: { "proceso_id": 2, "comentario": "Intentando aprobar" }
