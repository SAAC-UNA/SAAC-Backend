-- Script para probar aprobación de criterios por bloques
-- Ejecutar después de tener la estructura básica creada

-- ==============================================================================
-- CASO 1: Criterio con TODAS las evidencias completadas (SE PUEDE APROBAR)
-- ==============================================================================

-- Verificar que existe un criterio (ajusta el ID según tu BD)
SELECT criterio_id, nomenclatura, descripcion 
FROM CRITERIO 
LIMIT 1;

-- Verificar que existe un proceso (ajusta el ID según tu BD)
SELECT proceso_id, nombre 
FROM PROCESO 
LIMIT 1;

-- Ver evidencias de un criterio específico
SELECT e.evidencia_id, e.nomenclatura, e.descripcion, e.activo
FROM EVIDENCIA e
WHERE e.criterio_id = 1;

-- Ver asignaciones de evidencias del criterio en el proceso
SELECT 
    ea.evidencia_asignacion_id,
    ea.evidencia_id,
    e.nomenclatura,
    ea.estado,
    ea.usuario_id,
    ea.fecha_asignacion
FROM EVIDENCIA_ASIGNACION ea
JOIN EVIDENCIA e ON ea.evidencia_id = e.evidencia_id
WHERE e.criterio_id = 1 
  AND ea.proceso_id = 1;

-- ==============================================================================
-- INSERTAR DATOS DE PRUEBA (si no existen)
-- ==============================================================================

-- Asegurarse de que las evidencias del criterio 1 estén completadas
-- (Ajusta los IDs según tu BD)
UPDATE EVIDENCIA_ASIGNACION 
SET estado = 'completado' 
WHERE evidencia_id IN (
    SELECT evidencia_id 
    FROM EVIDENCIA 
    WHERE criterio_id = 1
) AND proceso_id = 1;

-- ==============================================================================
-- CONSULTAS PARA VERIFICAR DESPUÉS DE APROBAR/RECHAZAR
-- ==============================================================================

-- Ver aprobaciones registradas
SELECT 
    ac.aprobacion_criterio_id,
    ac.criterio_id,
    c.nomenclatura AS criterio,
    ac.proceso_id,
    ac.usuario_id,
    ac.estado,
    ac.comentario,
    ac.created_at AS fecha_primera_decision,
    ac.updated_at AS fecha_ultima_modificacion
FROM APROBACION_CRITERIO ac
JOIN CRITERIO c ON ac.criterio_id = c.criterio_id
ORDER BY ac.updated_at DESC;

-- Ver detalle completo de una aprobación
SELECT 
    ac.*,
    c.nomenclatura AS criterio_nombre,
    c.descripcion AS criterio_descripcion,
    u.nombre AS usuario_nombre
FROM APROBACION_CRITERIO ac
JOIN CRITERIO c ON ac.criterio_id = c.criterio_id
JOIN USUARIO u ON ac.usuario_id = u.usuario_id
WHERE ac.aprobacion_criterio_id = 1;
