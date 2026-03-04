<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop all SPs first (idempotent)
        $this->dropAll();

        // =====================================================================
        // UNIVERSIDAD
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_UNIVERSIDADES()
BEGIN
    SELECT * FROM UNIVERSIDAD ORDER BY nombre;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_UNIVERSIDAD(IN p_id BIGINT)
BEGIN
    SELECT * FROM UNIVERSIDAD WHERE universidad_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_UNIVERSIDAD(IN p_nombre VARCHAR(250), IN p_activo TINYINT)
BEGIN
    INSERT INTO UNIVERSIDAD (nombre, activo, created_at, updated_at)
    VALUES (p_nombre, p_activo, NOW(), NOW());
    SELECT * FROM UNIVERSIDAD WHERE universidad_id = LAST_INSERT_ID() LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_UNIVERSIDAD(IN p_id BIGINT, IN p_nombre VARCHAR(250), IN p_activo TINYINT)
BEGIN
    UPDATE UNIVERSIDAD
    SET nombre = p_nombre, activo = p_activo, updated_at = NOW()
    WHERE universidad_id = p_id;
    SELECT * FROM UNIVERSIDAD WHERE universidad_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_UNIVERSIDAD(IN p_id BIGINT)
BEGIN
    DELETE FROM UNIVERSIDAD WHERE universidad_id = p_id;
END
        ');

        // =====================================================================
        // SEDE (Campus)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_SEDES(IN p_universidad_id BIGINT)
BEGIN
    SELECT s.*, u.nombre AS universidad_nombre, u.activo AS universidad_activo
    FROM SEDE s
    JOIN UNIVERSIDAD u ON u.universidad_id = s.universidad_id
    WHERE (p_universidad_id IS NULL OR s.universidad_id = p_universidad_id)
    ORDER BY s.nombre;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_SEDE(IN p_id BIGINT)
BEGIN
    SELECT s.*, u.nombre AS universidad_nombre, u.activo AS universidad_activo
    FROM SEDE s
    JOIN UNIVERSIDAD u ON u.universidad_id = s.universidad_id
    WHERE s.sede_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_SEDE(IN p_universidad_id BIGINT, IN p_nombre VARCHAR(250), IN p_activo TINYINT)
BEGIN
    INSERT INTO SEDE (universidad_id, nombre, activo, created_at, updated_at)
    VALUES (p_universidad_id, p_nombre, p_activo, NOW(), NOW());
    CALL SP_BUSCAR_SEDE(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_SEDE(IN p_id BIGINT, IN p_nombre VARCHAR(250), IN p_activo TINYINT)
BEGIN
    UPDATE SEDE SET nombre = p_nombre, activo = p_activo, updated_at = NOW()
    WHERE sede_id = p_id;
    CALL SP_BUSCAR_SEDE(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_SEDE(IN p_id BIGINT)
BEGIN
    DELETE FROM SEDE WHERE sede_id = p_id;
END
        ');

        // =====================================================================
        // CARRERA (Career)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_CARRERAS()
BEGIN
    SELECT c.*,
        GROUP_CONCAT(DISTINCT s.sede_id ORDER BY s.sede_id SEPARATOR \',\') AS campus_ids,
        GROUP_CONCAT(DISTINCT s.nombre ORDER BY s.sede_id SEPARATOR \'||||\') AS campus_nombres
    FROM CARRERA c
    LEFT JOIN CARRERA_SEDE cs ON cs.carrera_id = c.carrera_id
    LEFT JOIN SEDE s ON s.sede_id = cs.sede_id
    GROUP BY c.carrera_id
    ORDER BY c.nombre;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_CARRERA(IN p_id BIGINT)
BEGIN
    SELECT c.*,
        GROUP_CONCAT(DISTINCT s.sede_id ORDER BY s.sede_id SEPARATOR \',\') AS campus_ids,
        GROUP_CONCAT(DISTINCT s.nombre ORDER BY s.sede_id SEPARATOR \'||||\') AS campus_nombres
    FROM CARRERA c
    LEFT JOIN CARRERA_SEDE cs ON cs.carrera_id = c.carrera_id
    LEFT JOIN SEDE s ON s.sede_id = cs.sede_id
    WHERE c.carrera_id = p_id
    GROUP BY c.carrera_id
    LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_CARRERA(IN p_nombre VARCHAR(250), IN p_activo TINYINT)
BEGIN
    INSERT INTO CARRERA (nombre, activo, created_at, updated_at)
    VALUES (p_nombre, p_activo, NOW(), NOW());
    CALL SP_BUSCAR_CARRERA(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_CARRERA(IN p_id BIGINT, IN p_nombre VARCHAR(250), IN p_activo TINYINT)
BEGIN
    UPDATE CARRERA SET nombre = p_nombre, activo = p_activo, updated_at = NOW()
    WHERE carrera_id = p_id;
    CALL SP_BUSCAR_CARRERA(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_CARRERA(IN p_id BIGINT)
BEGIN
    DELETE FROM CARRERA WHERE carrera_id = p_id;
END
        ');

        // =====================================================================
        // CARRERA_SEDE (CareerCampus)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_CARRERAS_SEDES(IN p_carrera_id BIGINT, IN p_sede_id BIGINT)
BEGIN
    SELECT cs.*,
        c.nombre AS carrera_nombre,
        s.nombre AS sede_nombre
    FROM CARRERA_SEDE cs
    JOIN CARRERA c ON c.carrera_id = cs.carrera_id
    JOIN SEDE s ON s.sede_id = cs.sede_id
    WHERE (p_carrera_id IS NULL OR cs.carrera_id = p_carrera_id)
      AND (p_sede_id IS NULL OR cs.sede_id = p_sede_id)
    ORDER BY cs.carrera_sede_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_CARRERA_SEDE(IN p_id BIGINT)
BEGIN
    SELECT cs.*,
        c.nombre AS carrera_nombre,
        s.nombre AS sede_nombre
    FROM CARRERA_SEDE cs
    JOIN CARRERA c ON c.carrera_id = cs.carrera_id
    JOIN SEDE s ON s.sede_id = cs.sede_id
    WHERE cs.carrera_sede_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_CARRERA_SEDE(IN p_carrera_id BIGINT, IN p_sede_id BIGINT)
BEGIN
    INSERT INTO CARRERA_SEDE (carrera_id, sede_id, created_at, updated_at)
    VALUES (p_carrera_id, p_sede_id, NOW(), NOW());
    CALL SP_BUSCAR_CARRERA_SEDE(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_CARRERA_SEDE(IN p_id BIGINT)
BEGIN
    DELETE FROM CARRERA_SEDE WHERE carrera_sede_id = p_id;
END
        ');

        // =====================================================================
        // CICLO_ACREDITACION (AccreditationCycle)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_CICLOS_ACREDITACION(IN p_carrera_sede_id BIGINT)
BEGIN
    SELECT ca.*, cs.carrera_id, cs.sede_id,
        c.nombre AS carrera_nombre,
        s.nombre AS sede_nombre
    FROM CICLO_ACREDITACION ca
    JOIN CARRERA_SEDE cs ON cs.carrera_sede_id = ca.carrera_sede_id
    JOIN CARRERA c ON c.carrera_id = cs.carrera_id
    JOIN SEDE s ON s.sede_id = cs.sede_id
    WHERE (p_carrera_sede_id IS NULL OR ca.carrera_sede_id = p_carrera_sede_id)
    ORDER BY ca.ciclo_acreditacion_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_CICLO_ACREDITACION(IN p_id BIGINT)
BEGIN
    SELECT ca.*, cs.carrera_id, cs.sede_id,
        c.nombre AS carrera_nombre,
        s.nombre AS sede_nombre
    FROM CICLO_ACREDITACION ca
    JOIN CARRERA_SEDE cs ON cs.carrera_sede_id = ca.carrera_sede_id
    JOIN CARRERA c ON c.carrera_id = cs.carrera_id
    JOIN SEDE s ON s.sede_id = cs.sede_id
    WHERE ca.ciclo_acreditacion_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_CICLO_ACREDITACION(IN p_carrera_sede_id BIGINT, IN p_nombre VARCHAR(50))
BEGIN
    INSERT INTO CICLO_ACREDITACION (carrera_sede_id, nombre, created_at, updated_at)
    VALUES (p_carrera_sede_id, p_nombre, NOW(), NOW());
    CALL SP_BUSCAR_CICLO_ACREDITACION(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_CICLO_ACREDITACION(IN p_id BIGINT, IN p_carrera_sede_id BIGINT, IN p_nombre VARCHAR(50))
BEGIN
    UPDATE CICLO_ACREDITACION
    SET carrera_sede_id = p_carrera_sede_id, nombre = p_nombre, updated_at = NOW()
    WHERE ciclo_acreditacion_id = p_id;
    CALL SP_BUSCAR_CICLO_ACREDITACION(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_CICLO_ACREDITACION(IN p_id BIGINT)
BEGIN
    DELETE FROM CICLO_ACREDITACION WHERE ciclo_acreditacion_id = p_id;
END
        ');

        // =====================================================================
        // PROCESO (Process)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_PROCESOS(IN p_ciclo_id BIGINT, IN p_tipo VARCHAR(50))
BEGIN
    SELECT p.*, ca.nombre AS ciclo_nombre, ca.carrera_sede_id
    FROM PROCESO p
    JOIN CICLO_ACREDITACION ca ON ca.ciclo_acreditacion_id = p.ciclo_acreditacion_id
    WHERE (p_ciclo_id IS NULL OR p.ciclo_acreditacion_id = p_ciclo_id)
      AND (p_tipo IS NULL OR p.tipo_proceso = p_tipo)
    ORDER BY p.proceso_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_PROCESO(IN p_id BIGINT)
BEGIN
    SELECT p.*, ca.nombre AS ciclo_nombre, ca.carrera_sede_id
    FROM PROCESO p
    JOIN CICLO_ACREDITACION ca ON ca.ciclo_acreditacion_id = p.ciclo_acreditacion_id
    WHERE p.proceso_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_PROCESO(IN p_ciclo_id BIGINT, IN p_tipo VARCHAR(50))
BEGIN
    INSERT INTO PROCESO (ciclo_acreditacion_id, tipo_proceso, created_at, updated_at)
    VALUES (p_ciclo_id, p_tipo, NOW(), NOW());
    CALL SP_BUSCAR_PROCESO(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_PROCESO(IN p_id BIGINT, IN p_ciclo_id BIGINT, IN p_tipo VARCHAR(50))
BEGIN
    UPDATE PROCESO
    SET ciclo_acreditacion_id = p_ciclo_id, tipo_proceso = p_tipo, updated_at = NOW()
    WHERE proceso_id = p_id;
    CALL SP_BUSCAR_PROCESO(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_PROCESO(IN p_id BIGINT)
BEGIN
    DELETE FROM PROCESO WHERE proceso_id = p_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_O_CREAR_PROCESO_MEJORA(IN p_ciclo_id BIGINT)
BEGIN
    DECLARE v_proceso_id BIGINT DEFAULT NULL;
    SELECT proceso_id INTO v_proceso_id
    FROM PROCESO
    WHERE ciclo_acreditacion_id = p_ciclo_id
      AND tipo_proceso = \'Compromiso de mejora\'
    LIMIT 1;
    IF v_proceso_id IS NULL THEN
        INSERT INTO PROCESO (ciclo_acreditacion_id, tipo_proceso, created_at, updated_at)
        VALUES (p_ciclo_id, \'Compromiso de mejora\', NOW(), NOW());
        SET v_proceso_id = LAST_INSERT_ID();
    END IF;
    SELECT v_proceso_id AS proceso_id;
END
        ');

        // =====================================================================
        // AUTOEVALUACION
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_AUTOEVALUACIONES(IN p_proceso_id BIGINT)
BEGIN
    SELECT a.*, p.tipo_proceso, p.ciclo_acreditacion_id
    FROM AUTOEVALUACION a
    JOIN PROCESO p ON p.proceso_id = a.proceso_id
    WHERE (p_proceso_id IS NULL OR a.proceso_id = p_proceso_id)
    ORDER BY a.autoevaluacion_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_AUTOEVALUACION(IN p_id BIGINT)
BEGIN
    SELECT a.*, p.tipo_proceso, p.ciclo_acreditacion_id
    FROM AUTOEVALUACION a
    JOIN PROCESO p ON p.proceso_id = a.proceso_id
    WHERE a.autoevaluacion_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_AUTOEVALUACION(IN p_proceso_id BIGINT, IN p_fecha_inicio DATE, IN p_fecha_fin DATE)
BEGIN
    INSERT INTO AUTOEVALUACION (proceso_id, fecha_inicio, fecha_fin, created_at, updated_at)
    VALUES (p_proceso_id, p_fecha_inicio, p_fecha_fin, NOW(), NOW());
    CALL SP_BUSCAR_AUTOEVALUACION(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_AUTOEVALUACION(IN p_id BIGINT, IN p_proceso_id BIGINT, IN p_fecha_inicio DATE, IN p_fecha_fin DATE)
BEGIN
    UPDATE AUTOEVALUACION
    SET proceso_id = p_proceso_id, fecha_inicio = p_fecha_inicio, fecha_fin = p_fecha_fin, updated_at = NOW()
    WHERE autoevaluacion_id = p_id;
    CALL SP_BUSCAR_AUTOEVALUACION(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_AUTOEVALUACION(IN p_id BIGINT)
BEGIN
    DELETE FROM AUTOEVALUACION WHERE autoevaluacion_id = p_id;
END
        ');

        // =====================================================================
        // DIMENSION
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_DIMENSIONES()
BEGIN
    SELECT * FROM DIMENSION ORDER BY nomenclatura;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_DIMENSION(IN p_id BIGINT)
BEGIN
    SELECT * FROM DIMENSION WHERE dimension_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_DIMENSION(IN p_nombre VARCHAR(100), IN p_nomenclatura VARCHAR(20), IN p_activo TINYINT)
BEGIN
    INSERT INTO DIMENSION (nombre, nomenclatura, activo, created_at, updated_at)
    VALUES (p_nombre, p_nomenclatura, p_activo, NOW(), NOW());
    SELECT * FROM DIMENSION WHERE dimension_id = LAST_INSERT_ID() LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_DIMENSION(IN p_id BIGINT, IN p_nombre VARCHAR(100), IN p_nomenclatura VARCHAR(20), IN p_activo TINYINT)
BEGIN
    UPDATE DIMENSION
    SET nombre = p_nombre, nomenclatura = p_nomenclatura, activo = p_activo, updated_at = NOW()
    WHERE dimension_id = p_id;
    SELECT * FROM DIMENSION WHERE dimension_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_DIMENSION(IN p_id BIGINT)
BEGIN
    DELETE FROM DIMENSION WHERE dimension_id = p_id;
END
        ');

        // =====================================================================
        // COMPONENTE
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_COMPONENTES()
BEGIN
    SELECT co.*, d.nombre AS dimension_nombre, d.nomenclatura AS dimension_nomenclatura, d.activo AS dimension_activo
    FROM COMPONENTE co
    JOIN DIMENSION d ON d.dimension_id = co.dimension_id
    ORDER BY co.nombre;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_COMPONENTE(IN p_id BIGINT)
BEGIN
    SELECT co.*, d.nombre AS dimension_nombre, d.nomenclatura AS dimension_nomenclatura, d.activo AS dimension_activo
    FROM COMPONENTE co
    JOIN DIMENSION d ON d.dimension_id = co.dimension_id
    WHERE co.componente_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_COMPONENTE(IN p_dimension_id BIGINT, IN p_nombre VARCHAR(80), IN p_nomenclatura VARCHAR(20), IN p_activo TINYINT)
BEGIN
    INSERT INTO COMPONENTE (dimension_id, nombre, nomenclatura, activo, created_at, updated_at)
    VALUES (p_dimension_id, p_nombre, p_nomenclatura, p_activo, NOW(), NOW());
    CALL SP_BUSCAR_COMPONENTE(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_COMPONENTE(IN p_id BIGINT, IN p_dimension_id BIGINT, IN p_nombre VARCHAR(80), IN p_nomenclatura VARCHAR(20), IN p_activo TINYINT)
BEGIN
    UPDATE COMPONENTE
    SET dimension_id = p_dimension_id, nombre = p_nombre, nomenclatura = p_nomenclatura, activo = p_activo, updated_at = NOW()
    WHERE componente_id = p_id;
    CALL SP_BUSCAR_COMPONENTE(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_COMPONENTE(IN p_id BIGINT)
BEGIN
    DELETE FROM COMPONENTE WHERE componente_id = p_id;
END
        ');

        // =====================================================================
        // CRITERIO
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_CRITERIOS()
BEGIN
    SELECT cr.*,
        co.nombre AS componente_nombre, co.nomenclatura AS componente_nomenclatura,
        d.dimension_id, d.nombre AS dimension_nombre
    FROM CRITERIO cr
    JOIN COMPONENTE co ON co.componente_id = cr.componente_id
    JOIN DIMENSION d ON d.dimension_id = co.dimension_id
    ORDER BY cr.nomenclatura;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_CRITERIO(IN p_id BIGINT)
BEGIN
    SELECT cr.*,
        co.nombre AS componente_nombre, co.nomenclatura AS componente_nomenclatura,
        d.dimension_id, d.nombre AS dimension_nombre
    FROM CRITERIO cr
    JOIN COMPONENTE co ON co.componente_id = cr.componente_id
    JOIN DIMENSION d ON d.dimension_id = co.dimension_id
    WHERE cr.criterio_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_CRITERIO(IN p_componente_id BIGINT, IN p_descripcion VARCHAR(300), IN p_nomenclatura VARCHAR(20), IN p_activo TINYINT)
BEGIN
    INSERT INTO CRITERIO (componente_id, descripcion, nomenclatura, activo, created_at, updated_at)
    VALUES (p_componente_id, p_descripcion, p_nomenclatura, p_activo, NOW(), NOW());
    CALL SP_BUSCAR_CRITERIO(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_CRITERIO(IN p_id BIGINT, IN p_componente_id BIGINT, IN p_descripcion VARCHAR(300), IN p_nomenclatura VARCHAR(20), IN p_activo TINYINT)
BEGIN
    UPDATE CRITERIO
    SET componente_id = p_componente_id, descripcion = p_descripcion,
        nomenclatura = p_nomenclatura, activo = p_activo, updated_at = NOW()
    WHERE criterio_id = p_id;
    CALL SP_BUSCAR_CRITERIO(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_CRITERIO(IN p_id BIGINT)
BEGIN
    DELETE FROM CRITERIO WHERE criterio_id = p_id;
END
        ');

        // =====================================================================
        // ESTADO_EVIDENCIA
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_ESTADOS_EVIDENCIA()
BEGIN
    SELECT * FROM ESTADO_EVIDENCIA ORDER BY estado_evidencia_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_ESTADO_EVIDENCIA(IN p_id BIGINT)
BEGIN
    SELECT * FROM ESTADO_EVIDENCIA WHERE estado_evidencia_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_ESTADO_EVIDENCIA(IN p_nombre VARCHAR(30))
BEGIN
    INSERT INTO ESTADO_EVIDENCIA (nombre, created_at, updated_at)
    VALUES (p_nombre, NOW(), NOW());
    SELECT * FROM ESTADO_EVIDENCIA WHERE estado_evidencia_id = LAST_INSERT_ID() LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_ESTADO_EVIDENCIA(IN p_id BIGINT, IN p_nombre VARCHAR(30))
BEGIN
    UPDATE ESTADO_EVIDENCIA SET nombre = p_nombre, updated_at = NOW() WHERE estado_evidencia_id = p_id;
    SELECT * FROM ESTADO_EVIDENCIA WHERE estado_evidencia_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_ESTADO_EVIDENCIA(IN p_id BIGINT)
BEGIN
    DELETE FROM ESTADO_EVIDENCIA WHERE estado_evidencia_id = p_id;
END
        ');

        // =====================================================================
        // ESTANDAR (Standard)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_ESTANDARES()
BEGIN
    SELECT es.*, cr.nomenclatura AS criterio_nomenclatura, cr.descripcion AS criterio_descripcion
    FROM ESTANDAR es
    JOIN CRITERIO cr ON cr.criterio_id = es.criterio_id
    ORDER BY es.estandar_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_ESTANDAR(IN p_id BIGINT)
BEGIN
    SELECT es.*, cr.nomenclatura AS criterio_nomenclatura, cr.descripcion AS criterio_descripcion
    FROM ESTANDAR es
    JOIN CRITERIO cr ON cr.criterio_id = es.criterio_id
    WHERE es.estandar_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_ESTANDAR(IN p_criterio_id BIGINT, IN p_descripcion VARCHAR(250), IN p_activo TINYINT)
BEGIN
    INSERT INTO ESTANDAR (criterio_id, descripcion, activo, created_at, updated_at)
    VALUES (p_criterio_id, p_descripcion, p_activo, NOW(), NOW());
    CALL SP_BUSCAR_ESTANDAR(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_ESTANDAR(IN p_id BIGINT, IN p_criterio_id BIGINT, IN p_descripcion VARCHAR(250), IN p_activo TINYINT)
BEGIN
    UPDATE ESTANDAR
    SET criterio_id = p_criterio_id, descripcion = p_descripcion, activo = p_activo, updated_at = NOW()
    WHERE estandar_id = p_id;
    CALL SP_BUSCAR_ESTANDAR(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_ESTANDAR(IN p_id BIGINT)
BEGIN
    DELETE FROM ESTANDAR WHERE estandar_id = p_id;
END
        ');

        // =====================================================================
        // EVIDENCIA
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_EVIDENCIAS()
BEGIN
    SELECT ev.*,
        cr.nomenclatura AS criterio_nomenclatura, cr.descripcion AS criterio_descripcion,
        ee.nombre AS estado_nombre
    FROM EVIDENCIA ev
    JOIN CRITERIO cr ON cr.criterio_id = ev.criterio_id
    JOIN ESTADO_EVIDENCIA ee ON ee.estado_evidencia_id = ev.estado_evidencia_id
    ORDER BY ev.nomenclatura;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_EVIDENCIA(IN p_id BIGINT)
BEGIN
    SELECT ev.*,
        cr.nomenclatura AS criterio_nomenclatura, cr.descripcion AS criterio_descripcion,
        ee.nombre AS estado_nombre
    FROM EVIDENCIA ev
    JOIN CRITERIO cr ON cr.criterio_id = ev.criterio_id
    JOIN ESTADO_EVIDENCIA ee ON ee.estado_evidencia_id = ev.estado_evidencia_id
    WHERE ev.evidencia_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_EVIDENCIA(
    IN p_criterio_id BIGINT,
    IN p_estado_evidencia_id BIGINT,
    IN p_descripcion VARCHAR(80),
    IN p_nomenclatura VARCHAR(20),
    IN p_activo TINYINT
)
BEGIN
    INSERT INTO EVIDENCIA (criterio_id, estado_evidencia_id, descripcion, nomenclatura, activo, created_at, updated_at)
    VALUES (p_criterio_id, p_estado_evidencia_id, p_descripcion, p_nomenclatura, p_activo, NOW(), NOW());
    CALL SP_BUSCAR_EVIDENCIA(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_EVIDENCIA(
    IN p_id BIGINT,
    IN p_criterio_id BIGINT,
    IN p_estado_evidencia_id BIGINT,
    IN p_descripcion VARCHAR(80),
    IN p_nomenclatura VARCHAR(20),
    IN p_activo TINYINT
)
BEGIN
    UPDATE EVIDENCIA
    SET criterio_id = p_criterio_id,
        estado_evidencia_id = p_estado_evidencia_id,
        descripcion = p_descripcion,
        nomenclatura = p_nomenclatura,
        activo = p_activo,
        updated_at = NOW()
    WHERE evidencia_id = p_id;
    CALL SP_BUSCAR_EVIDENCIA(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_EVIDENCIA(IN p_id BIGINT)
BEGIN
    DELETE FROM EVIDENCIA WHERE evidencia_id = p_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_EVIDENCIAS_POR_ESTANDAR(IN p_estandar_id BIGINT)
BEGIN
    SELECT ev.evidencia_id
    FROM ESTANDAR es
    JOIN EVIDENCIA ev ON ev.criterio_id = es.criterio_id
    WHERE es.estandar_id = p_estandar_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_EVIDENCIAS_POR_DIMENSION(IN p_dimension_id BIGINT)
BEGIN
    SELECT ev.evidencia_id
    FROM EVIDENCIA ev
    JOIN CRITERIO cr ON cr.criterio_id = ev.criterio_id
    JOIN COMPONENTE co ON co.componente_id = cr.componente_id
    WHERE co.dimension_id = p_dimension_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_EVIDENCIAS_POR_COMPONENTE(IN p_componente_id BIGINT)
BEGIN
    SELECT ev.evidencia_id
    FROM EVIDENCIA ev
    JOIN CRITERIO cr ON cr.criterio_id = ev.criterio_id
    WHERE cr.componente_id = p_componente_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_EVIDENCIAS_POR_CRITERIO(IN p_criterio_id BIGINT)
BEGIN
    SELECT evidencia_id FROM EVIDENCIA WHERE criterio_id = p_criterio_id;
END
        ');

        // Evidence filtering (HU-012) - paginated
        DB::unprepared('
CREATE PROCEDURE SP_FILTRAR_EVIDENCIAS(
    IN p_criterio_id BIGINT,
    IN p_estado_evidencia_id BIGINT,
    IN p_fecha_desde VARCHAR(20),
    IN p_fecha_hasta VARCHAR(20),
    IN p_sort_by VARCHAR(30),
    IN p_sort_order VARCHAR(4),
    IN p_offset INT,
    IN p_per_page INT
)
BEGIN
    SET @sql = CONCAT(
        \'SELECT ev.*, cr.nomenclatura AS criterio_nomenclatura, cr.descripcion AS criterio_descripcion, \',
        \'ee.nombre AS estado_nombre, \',
        \'(SELECT COUNT(*) FROM ARCHIVO ar WHERE ar.evidencia_id = ev.evidencia_id AND ar.tipo = \\\'archivo\\\') AS archivos_count, \',
        \'(SELECT COUNT(*) FROM ARCHIVO ar WHERE ar.evidencia_id = ev.evidencia_id AND ar.tipo = \\\'enlace\\\') AS enlaces_count \',
        \'FROM EVIDENCIA ev \',
        \'JOIN CRITERIO cr ON cr.criterio_id = ev.criterio_id \',
        \'JOIN ESTADO_EVIDENCIA ee ON ee.estado_evidencia_id = ev.estado_evidencia_id \',
        \'WHERE 1=1 \'
    );
    IF p_criterio_id IS NOT NULL THEN
        SET @sql = CONCAT(@sql, \' AND ev.criterio_id = \', p_criterio_id);
    END IF;
    IF p_estado_evidencia_id IS NOT NULL THEN
        SET @sql = CONCAT(@sql, \' AND ev.estado_evidencia_id = \', p_estado_evidencia_id);
    END IF;
    IF p_fecha_desde IS NOT NULL AND p_fecha_desde != \'\' THEN
        SET @sql = CONCAT(@sql, \' AND ev.created_at >= \\\'\', p_fecha_desde, \'\\\'\');
    END IF;
    IF p_fecha_hasta IS NOT NULL AND p_fecha_hasta != \'\' THEN
        SET @sql = CONCAT(@sql, \' AND ev.created_at <= \\\'\', p_fecha_hasta, \'\\\'\');
    END IF;
    SET @sort_col = CASE COALESCE(p_sort_by, \'nomenclatura\')
        WHEN \'fecha\' THEN \'ev.created_at\'
        WHEN \'nomenclatura\' THEN \'ev.nomenclatura\'
        ELSE \'ev.nomenclatura\'
    END;
    SET @sort_dir = IF(UPPER(COALESCE(p_sort_order, \'asc\')) = \'DESC\', \'DESC\', \'ASC\');
    SET @sql = CONCAT(@sql, \' ORDER BY \', @sort_col, \' \', @sort_dir);
    SET @sql = CONCAT(@sql, \' LIMIT \', p_per_page, \' OFFSET \', p_offset);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CONTAR_FILTRO_EVIDENCIAS(
    IN p_criterio_id BIGINT,
    IN p_estado_evidencia_id BIGINT,
    IN p_fecha_desde VARCHAR(20),
    IN p_fecha_hasta VARCHAR(20)
)
BEGIN
    SELECT COUNT(*) AS total
    FROM EVIDENCIA ev
    WHERE (p_criterio_id IS NULL OR ev.criterio_id = p_criterio_id)
      AND (p_estado_evidencia_id IS NULL OR ev.estado_evidencia_id = p_estado_evidencia_id)
      AND (p_fecha_desde IS NULL OR p_fecha_desde = \'\' OR ev.created_at >= p_fecha_desde)
      AND (p_fecha_hasta IS NULL OR p_fecha_hasta = \'\' OR ev.created_at <= p_fecha_hasta);
END
        ');

        // =====================================================================
        // ARCHIVO (File)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_ARCHIVOS_POR_EVIDENCIA(IN p_evidencia_id BIGINT, IN p_tipo VARCHAR(10))
BEGIN
    SELECT * FROM ARCHIVO
    WHERE evidencia_id = p_evidencia_id
      AND (p_tipo IS NULL OR tipo = p_tipo)
    ORDER BY fecha_subida DESC;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_ARCHIVO(IN p_id BIGINT)
BEGIN
    SELECT * FROM ARCHIVO WHERE archivo_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_ARCHIVO_POR_TOKEN(IN p_token VARCHAR(36))
BEGIN
    SELECT * FROM ARCHIVO WHERE token_publico = p_token LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_ARCHIVO(
    IN p_evidencia_id BIGINT,
    IN p_usuario_id BIGINT,
    IN p_proceso_id BIGINT,
    IN p_fecha_subida DATETIME,
    IN p_tipo VARCHAR(10),
    IN p_path VARCHAR(512),
    IN p_url TEXT,
    IN p_nombre_original VARCHAR(255),
    IN p_is_publico TINYINT,
    IN p_token_publico VARCHAR(36),
    IN p_link_expira_en DATETIME
)
BEGIN
    INSERT INTO ARCHIVO (evidencia_id, usuario_id, proceso_id, fecha_subida, tipo, path, url,
        nombre_original, is_publico, token_publico, link_expira_en, created_at, updated_at)
    VALUES (p_evidencia_id, p_usuario_id, p_proceso_id, p_fecha_subida, p_tipo, p_path, p_url,
        p_nombre_original, p_is_publico, p_token_publico, p_link_expira_en, NOW(), NOW());
    CALL SP_BUSCAR_ARCHIVO(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_ARCHIVO_PUBLICO(
    IN p_id BIGINT,
    IN p_is_publico TINYINT,
    IN p_token_publico VARCHAR(36),
    IN p_link_expira_en DATETIME
)
BEGIN
    UPDATE ARCHIVO
    SET is_publico = p_is_publico, token_publico = p_token_publico,
        link_expira_en = p_link_expira_en, updated_at = NOW()
    WHERE archivo_id = p_id;
    CALL SP_BUSCAR_ARCHIVO(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_ARCHIVO(IN p_id BIGINT)
BEGIN
    DELETE FROM ARCHIVO WHERE archivo_id = p_id;
END
        ');

        // =====================================================================
        // USUARIO (User)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_USUARIO(IN p_id BIGINT)
BEGIN
    SELECT * FROM USUARIO WHERE usuario_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_USUARIO_POR_CEDULA(IN p_cedula VARCHAR(20))
BEGIN
    SELECT * FROM USUARIO WHERE cedula = p_cedula LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_USUARIO_POR_EMAIL(IN p_email VARCHAR(255))
BEGIN
    SELECT * FROM USUARIO WHERE email = p_email LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_USUARIO(
    IN p_cedula VARCHAR(20),
    IN p_nombre VARCHAR(80),
    IN p_email VARCHAR(255),
    IN p_status VARCHAR(20),
    IN p_password VARCHAR(255)
)
BEGIN
    INSERT INTO USUARIO (cedula, nombre, email, status, password, created_at, updated_at)
    VALUES (p_cedula, p_nombre, p_email, p_status, p_password, NOW(), NOW());
    CALL SP_BUSCAR_USUARIO(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_USUARIO(
    IN p_id BIGINT,
    IN p_cedula VARCHAR(20),
    IN p_nombre VARCHAR(80),
    IN p_email VARCHAR(255),
    IN p_status VARCHAR(20)
)
BEGIN
    UPDATE USUARIO
    SET cedula = p_cedula, nombre = p_nombre, email = p_email, status = p_status, updated_at = NOW()
    WHERE usuario_id = p_id;
    CALL SP_BUSCAR_USUARIO(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTIVAR_USUARIO(IN p_id BIGINT)
BEGIN
    UPDATE USUARIO SET status = \'active\', updated_at = NOW() WHERE usuario_id = p_id;
    CALL SP_BUSCAR_USUARIO(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_DESACTIVAR_USUARIO(IN p_id BIGINT)
BEGIN
    UPDATE USUARIO SET status = \'inactive\', updated_at = NOW() WHERE usuario_id = p_id;
    CALL SP_BUSCAR_USUARIO(p_id);
END
        ');

        // =====================================================================
        // TIPO_ACCION / BITACORA (AuditLog)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_TIPOS_ACCION()
BEGIN
    SELECT tipo_accion_id, descripcion FROM TIPO_ACCION ORDER BY descripcion;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_TIPO_ACCION_POR_NOMBRE(IN p_descripcion VARCHAR(100))
BEGIN
    SELECT * FROM TIPO_ACCION WHERE descripcion = p_descripcion LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_REGISTRAR_ACCION(
    IN p_usuario_id BIGINT,
    IN p_tipo_accion_id BIGINT,
    IN p_modulo VARCHAR(100),
    IN p_detalle TEXT,
    IN p_fecha_hora DATETIME
)
BEGIN
    INSERT INTO BITACORA (usuario_id, tipo_accion_id, modulo, detalle, fecha_hora, created_at, updated_at)
    VALUES (p_usuario_id, p_tipo_accion_id, p_modulo, p_detalle, p_fecha_hora, NOW(), NOW());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_BITACORA(
    IN p_usuario_id BIGINT,
    IN p_tipo_accion_id BIGINT,
    IN p_modulo VARCHAR(100),
    IN p_fecha_desde VARCHAR(30),
    IN p_fecha_hasta VARCHAR(30),
    IN p_offset INT,
    IN p_per_page INT
)
BEGIN
    SELECT b.*, u.nombre AS usuario_nombre, u.email AS usuario_email,
        ta.descripcion AS tipo_accion_descripcion
    FROM BITACORA b
    LEFT JOIN USUARIO u ON u.usuario_id = b.usuario_id
    JOIN TIPO_ACCION ta ON ta.tipo_accion_id = b.tipo_accion_id
    WHERE (p_usuario_id IS NULL OR b.usuario_id = p_usuario_id)
      AND (p_tipo_accion_id IS NULL OR b.tipo_accion_id = p_tipo_accion_id)
      AND (p_modulo IS NULL OR p_modulo = \'\' OR b.modulo = p_modulo)
      AND (p_fecha_desde IS NULL OR p_fecha_desde = \'\' OR b.fecha_hora >= p_fecha_desde)
      AND (p_fecha_hasta IS NULL OR p_fecha_hasta = \'\' OR b.fecha_hora <= p_fecha_hasta)
    ORDER BY b.fecha_hora DESC
    LIMIT p_per_page OFFSET p_offset;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CONTAR_BITACORA(
    IN p_usuario_id BIGINT,
    IN p_tipo_accion_id BIGINT,
    IN p_modulo VARCHAR(100),
    IN p_fecha_desde VARCHAR(30),
    IN p_fecha_hasta VARCHAR(30)
)
BEGIN
    SELECT COUNT(*) AS total
    FROM BITACORA b
    WHERE (p_usuario_id IS NULL OR b.usuario_id = p_usuario_id)
      AND (p_tipo_accion_id IS NULL OR b.tipo_accion_id = p_tipo_accion_id)
      AND (p_modulo IS NULL OR p_modulo = \'\' OR b.modulo = p_modulo)
      AND (p_fecha_desde IS NULL OR p_fecha_desde = \'\' OR b.fecha_hora >= p_fecha_desde)
      AND (p_fecha_hasta IS NULL OR p_fecha_hasta = \'\' OR b.fecha_hora <= p_fecha_hasta);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_MODULOS_BITACORA()
BEGIN
    SELECT DISTINCT modulo FROM BITACORA WHERE modulo IS NOT NULL ORDER BY modulo;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_BITACORA_EXPORTACION(IN p_desde VARCHAR(30), IN p_hasta VARCHAR(30))
BEGIN
    SELECT b.*, u.nombre AS usuario_nombre, u.email AS usuario_email,
        ta.descripcion AS tipo_accion_descripcion
    FROM BITACORA b
    LEFT JOIN USUARIO u ON u.usuario_id = b.usuario_id
    JOIN TIPO_ACCION ta ON ta.tipo_accion_id = b.tipo_accion_id
    WHERE b.fecha_hora BETWEEN p_desde AND p_hasta
    ORDER BY b.fecha_hora DESC;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CONTAR_BITACORA_EXPORTACION(IN p_desde VARCHAR(30), IN p_hasta VARCHAR(30))
BEGIN
    SELECT COUNT(*) AS total FROM BITACORA WHERE fecha_hora BETWEEN p_desde AND p_hasta;
END
        ');

        // =====================================================================
        // COMENTARIO (Comment)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_COMENTARIOS(IN p_commentable_type VARCHAR(255), IN p_commentable_id BIGINT)
BEGIN
    SELECT c.*, u.nombre AS usuario_nombre, u.email AS usuario_email
    FROM COMENTARIO c
    JOIN USUARIO u ON u.usuario_id = c.usuario_id
    WHERE c.commentable_type = p_commentable_type
      AND c.commentable_id = p_commentable_id
    ORDER BY c.created_at DESC;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_COMENTARIO(IN p_id BIGINT)
BEGIN
    SELECT c.*, u.nombre AS usuario_nombre, u.email AS usuario_email
    FROM COMENTARIO c
    JOIN USUARIO u ON u.usuario_id = c.usuario_id
    WHERE c.comentario_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_COMENTARIO(
    IN p_usuario_id BIGINT,
    IN p_commentable_type VARCHAR(255),
    IN p_commentable_id BIGINT,
    IN p_texto TEXT
)
BEGIN
    INSERT INTO COMENTARIO (usuario_id, commentable_type, commentable_id, texto, created_at, updated_at)
    VALUES (p_usuario_id, p_commentable_type, p_commentable_id, p_texto, NOW(), NOW());
    CALL SP_BUSCAR_COMENTARIO(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_COMENTARIO(IN p_id BIGINT)
BEGIN
    DELETE FROM COMENTARIO WHERE comentario_id = p_id;
END
        ');

        // =====================================================================
        // EVIDENCIA_ASIGNACION (EvidenceAssignment)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_ASIGNACIONES_EVIDENCIA(IN p_proceso_id BIGINT, IN p_usuario_id BIGINT, IN p_evidencia_id BIGINT)
BEGIN
    SELECT ea.*,
        u.nombre AS usuario_nombre, u.email AS usuario_email,
        ev.nomenclatura AS evidencia_nomenclatura, ev.descripcion AS evidencia_descripcion
    FROM EVIDENCIA_ASIGNACION ea
    JOIN USUARIO u ON u.usuario_id = ea.usuario_id
    JOIN EVIDENCIA ev ON ev.evidencia_id = ea.evidencia_id
    WHERE (p_proceso_id IS NULL OR ea.proceso_id = p_proceso_id)
      AND (p_usuario_id IS NULL OR ea.usuario_id = p_usuario_id)
      AND (p_evidencia_id IS NULL OR ea.evidencia_id = p_evidencia_id)
    ORDER BY ea.fecha_asignacion DESC;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_ASIGNACION_EVIDENCIA(IN p_id BIGINT)
BEGIN
    SELECT ea.*,
        u.nombre AS usuario_nombre, u.email AS usuario_email,
        ev.nomenclatura AS evidencia_nomenclatura, ev.descripcion AS evidencia_descripcion
    FROM EVIDENCIA_ASIGNACION ea
    JOIN USUARIO u ON u.usuario_id = ea.usuario_id
    JOIN EVIDENCIA ev ON ev.evidencia_id = ea.evidencia_id
    WHERE ea.evidencia_asignacion_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_ASIGNACION_EVIDENCIA(
    IN p_proceso_id BIGINT,
    IN p_evidencia_id BIGINT,
    IN p_usuario_id BIGINT,
    IN p_estado VARCHAR(30),
    IN p_fecha_asignacion DATETIME,
    IN p_fecha_limite DATETIME,
    IN p_comentario VARCHAR(500)
)
BEGIN
    INSERT INTO EVIDENCIA_ASIGNACION
        (proceso_id, evidencia_id, usuario_id, estado, fecha_asignacion, fecha_limite, comentario, created_at, updated_at)
    VALUES
        (p_proceso_id, p_evidencia_id, p_usuario_id, p_estado, p_fecha_asignacion, p_fecha_limite, p_comentario, NOW(), NOW());
    CALL SP_BUSCAR_ASIGNACION_EVIDENCIA(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_ASIGNACION_EVIDENCIA(
    IN p_id BIGINT,
    IN p_estado VARCHAR(30),
    IN p_fecha_limite DATETIME,
    IN p_comentario VARCHAR(500)
)
BEGIN
    UPDATE EVIDENCIA_ASIGNACION
    SET estado = COALESCE(p_estado, estado),
        fecha_limite = COALESCE(p_fecha_limite, fecha_limite),
        comentario = COALESCE(p_comentario, comentario),
        updated_at = NOW()
    WHERE evidencia_asignacion_id = p_id;
    CALL SP_BUSCAR_ASIGNACION_EVIDENCIA(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_ASIGNACION_EVIDENCIA(IN p_id BIGINT)
BEGIN
    DELETE FROM EVIDENCIA_ASIGNACION WHERE evidencia_asignacion_id = p_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_VERIFICAR_DUPLICADO_ASIGNACION(IN p_proceso_id BIGINT, IN p_evidencia_id BIGINT, IN p_usuario_id BIGINT)
BEGIN
    SELECT COUNT(*) AS total
    FROM EVIDENCIA_ASIGNACION
    WHERE proceso_id = p_proceso_id
      AND evidencia_id = p_evidencia_id
      AND usuario_id = p_usuario_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_EVIDENCIAS_PROXIMAS(IN p_usuario_id BIGINT, IN p_limit_date DATETIME)
BEGIN
    SELECT ea.*,
        ev.nomenclatura AS evidencia_nomenclatura, ev.descripcion AS evidencia_descripcion,
        cr.nomenclatura AS criterio_nomenclatura
    FROM EVIDENCIA_ASIGNACION ea
    JOIN EVIDENCIA ev ON ev.evidencia_id = ea.evidencia_id
    JOIN CRITERIO cr ON cr.criterio_id = ev.criterio_id
    WHERE ea.usuario_id = p_usuario_id
      AND ea.estado IN (\'Pendiente\', \'En Progreso\')
      AND ea.fecha_limite <= p_limit_date
    ORDER BY ea.fecha_limite ASC;
END
        ');

        // =====================================================================
        // SOLICITUD_AMPLIACION (ExtensionRequest)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_SOLICITUDES_AMPLIACION(
    IN p_estado VARCHAR(30),
    IN p_usuario_id BIGINT,
    IN p_evidencia_asignacion_id BIGINT,
    IN p_fecha_desde VARCHAR(30),
    IN p_fecha_hasta VARCHAR(30),
    IN p_offset INT,
    IN p_per_page INT
)
BEGIN
    SELECT sa.*,
        u.nombre AS usuario_nombre, u.email AS usuario_email,
        ur.nombre AS resolutor_nombre
    FROM SOLICITUD_AMPLIACION sa
    JOIN USUARIO u ON u.usuario_id = sa.usuario_id
    LEFT JOIN USUARIO ur ON ur.usuario_id = sa.usuario_resolutor_id
    WHERE (p_estado IS NULL OR p_estado = \'\' OR sa.estado = p_estado)
      AND (p_usuario_id IS NULL OR sa.usuario_id = p_usuario_id)
      AND (p_evidencia_asignacion_id IS NULL OR sa.evidencia_asignacion_id = p_evidencia_asignacion_id)
      AND (p_fecha_desde IS NULL OR p_fecha_desde = \'\' OR sa.created_at >= p_fecha_desde)
      AND (p_fecha_hasta IS NULL OR p_fecha_hasta = \'\' OR sa.created_at <= p_fecha_hasta)
    ORDER BY sa.created_at DESC
    LIMIT p_per_page OFFSET p_offset;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CONTAR_SOLICITUDES_AMPLIACION(
    IN p_estado VARCHAR(30),
    IN p_usuario_id BIGINT,
    IN p_evidencia_asignacion_id BIGINT,
    IN p_fecha_desde VARCHAR(30),
    IN p_fecha_hasta VARCHAR(30)
)
BEGIN
    SELECT COUNT(*) AS total
    FROM SOLICITUD_AMPLIACION sa
    WHERE (p_estado IS NULL OR p_estado = \'\' OR sa.estado = p_estado)
      AND (p_usuario_id IS NULL OR sa.usuario_id = p_usuario_id)
      AND (p_evidencia_asignacion_id IS NULL OR sa.evidencia_asignacion_id = p_evidencia_asignacion_id)
      AND (p_fecha_desde IS NULL OR p_fecha_desde = \'\' OR sa.created_at >= p_fecha_desde)
      AND (p_fecha_hasta IS NULL OR p_fecha_hasta = \'\' OR sa.created_at <= p_fecha_hasta);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_SOLICITUD_AMPLIACION(IN p_id BIGINT)
BEGIN
    SELECT sa.*,
        u.nombre AS usuario_nombre, u.email AS usuario_email,
        ur.nombre AS resolutor_nombre
    FROM SOLICITUD_AMPLIACION sa
    JOIN USUARIO u ON u.usuario_id = sa.usuario_id
    LEFT JOIN USUARIO ur ON ur.usuario_id = sa.usuario_resolutor_id
    WHERE sa.solicitud_ampliacion_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_SOLICITUD_AMPLIACION(
    IN p_evidencia_asignacion_id BIGINT,
    IN p_usuario_id BIGINT,
    IN p_motivo VARCHAR(1000),
    IN p_fecha_sugerida DATETIME,
    IN p_estado VARCHAR(30)
)
BEGIN
    INSERT INTO SOLICITUD_AMPLIACION
        (evidencia_asignacion_id, usuario_id, motivo, fecha_sugerida, estado, created_at, updated_at)
    VALUES
        (p_evidencia_asignacion_id, p_usuario_id, p_motivo, p_fecha_sugerida, p_estado, NOW(), NOW());
    CALL SP_BUSCAR_SOLICITUD_AMPLIACION(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_VERIFICAR_SOLICITUD_PENDIENTE(IN p_evidencia_asignacion_id BIGINT)
BEGIN
    SELECT COUNT(*) AS total
    FROM SOLICITUD_AMPLIACION
    WHERE evidencia_asignacion_id = p_evidencia_asignacion_id
      AND estado = \'pendiente\';
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_APROBAR_SOLICITUD_AMPLIACION(
    IN p_solicitud_id BIGINT,
    IN p_resolutor_id BIGINT,
    IN p_justificacion VARCHAR(500),
    IN p_fecha_resolucion DATETIME,
    IN p_nueva_fecha_limite DATETIME
)
BEGIN
    DECLARE v_asignacion_id BIGINT;
    SELECT evidencia_asignacion_id INTO v_asignacion_id
    FROM SOLICITUD_AMPLIACION WHERE solicitud_ampliacion_id = p_solicitud_id;

    UPDATE SOLICITUD_AMPLIACION
    SET estado = \'aprobada\',
        fecha_resolucion = p_fecha_resolucion,
        usuario_resolutor_id = p_resolutor_id,
        justificacion = p_justificacion,
        updated_at = NOW()
    WHERE solicitud_ampliacion_id = p_solicitud_id;

    UPDATE EVIDENCIA_ASIGNACION
    SET fecha_limite = p_nueva_fecha_limite, updated_at = NOW()
    WHERE evidencia_asignacion_id = v_asignacion_id;

    CALL SP_BUSCAR_SOLICITUD_AMPLIACION(p_solicitud_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_RECHAZAR_SOLICITUD_AMPLIACION(
    IN p_solicitud_id BIGINT,
    IN p_resolutor_id BIGINT,
    IN p_justificacion VARCHAR(500),
    IN p_fecha_resolucion DATETIME
)
BEGIN
    UPDATE SOLICITUD_AMPLIACION
    SET estado = \'rechazada\',
        fecha_resolucion = p_fecha_resolucion,
        usuario_resolutor_id = p_resolutor_id,
        justificacion = p_justificacion,
        updated_at = NOW()
    WHERE solicitud_ampliacion_id = p_solicitud_id;
    CALL SP_BUSCAR_SOLICITUD_AMPLIACION(p_solicitud_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ELIMINAR_SOLICITUD_AMPLIACION(IN p_id BIGINT)
BEGIN
    DELETE FROM SOLICITUD_AMPLIACION WHERE solicitud_ampliacion_id = p_id;
END
        ');

        // =====================================================================
        // APROBACION_CRITERIO (CriterionApproval)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_APROBACIONES_CRITERIO()
BEGIN
    SELECT ac.*,
        cr.nomenclatura AS criterio_nomenclatura, cr.descripcion AS criterio_descripcion,
        u.nombre AS usuario_nombre
    FROM APROBACION_CRITERIO ac
    JOIN CRITERIO cr ON cr.criterio_id = ac.criterio_id
    JOIN USUARIO u ON u.usuario_id = ac.usuario_id
    ORDER BY ac.created_at DESC;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_APROBACION_CRITERIO(IN p_id BIGINT)
BEGIN
    SELECT ac.*,
        cr.nomenclatura AS criterio_nomenclatura, cr.descripcion AS criterio_descripcion,
        u.nombre AS usuario_nombre
    FROM APROBACION_CRITERIO ac
    JOIN CRITERIO cr ON cr.criterio_id = ac.criterio_id
    JOIN USUARIO u ON u.usuario_id = ac.usuario_id
    WHERE ac.aprobacion_criterio_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_APROBACION_CRITERIO_PROCESO(IN p_criterio_id BIGINT, IN p_proceso_id BIGINT)
BEGIN
    SELECT ac.*,
        cr.nomenclatura AS criterio_nomenclatura, cr.descripcion AS criterio_descripcion,
        u.nombre AS usuario_nombre
    FROM APROBACION_CRITERIO ac
    JOIN CRITERIO cr ON cr.criterio_id = ac.criterio_id
    JOIN USUARIO u ON u.usuario_id = ac.usuario_id
    WHERE ac.criterio_id = p_criterio_id AND ac.proceso_id = p_proceso_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_UPSERT_APROBACION_CRITERIO(
    IN p_criterio_id BIGINT,
    IN p_proceso_id BIGINT,
    IN p_usuario_id BIGINT,
    IN p_estado ENUM(\'aprobado\',\'rechazado\'),
    IN p_comentario VARCHAR(100)
)
BEGIN
    DECLARE v_existing_id BIGINT DEFAULT NULL;
    SELECT aprobacion_criterio_id INTO v_existing_id
    FROM APROBACION_CRITERIO
    WHERE criterio_id = p_criterio_id AND proceso_id = p_proceso_id
    LIMIT 1;

    IF v_existing_id IS NOT NULL THEN
        UPDATE APROBACION_CRITERIO
        SET usuario_id = p_usuario_id, estado = p_estado, comentario = p_comentario, updated_at = NOW()
        WHERE aprobacion_criterio_id = v_existing_id;
        SELECT v_existing_id AS aprobacion_criterio_id, 0 AS is_new;
    ELSE
        INSERT INTO APROBACION_CRITERIO (criterio_id, proceso_id, usuario_id, estado, comentario, created_at, updated_at)
        VALUES (p_criterio_id, p_proceso_id, p_usuario_id, p_estado, p_comentario, NOW(), NOW());
        SELECT LAST_INSERT_ID() AS aprobacion_criterio_id, 1 AS is_new;
    END IF;
END
        ');

        // =====================================================================
        // APROBACION_EVIDENCIA (EvidenceApproval)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_UPSERT_APROBACION_EVIDENCIA(
    IN p_evidencia_id BIGINT,
    IN p_proceso_id BIGINT,
    IN p_criterio_aprobacion_id BIGINT,
    IN p_usuario_id BIGINT,
    IN p_estado ENUM(\'aprobado\',\'rechazado\')
)
BEGIN
    DECLARE v_existing_id BIGINT DEFAULT NULL;
    SELECT aprobacion_evidencia_id INTO v_existing_id
    FROM APROBACION_EVIDENCIA
    WHERE evidencia_id = p_evidencia_id
      AND proceso_id = p_proceso_id
      AND criterio_aprobacion_id = p_criterio_aprobacion_id
    LIMIT 1;

    IF v_existing_id IS NOT NULL THEN
        UPDATE APROBACION_EVIDENCIA
        SET usuario_id = p_usuario_id, estado = p_estado, updated_at = NOW()
        WHERE aprobacion_evidencia_id = v_existing_id;
    ELSE
        INSERT INTO APROBACION_EVIDENCIA
            (evidencia_id, proceso_id, criterio_aprobacion_id, usuario_id, estado, created_at, updated_at)
        VALUES
            (p_evidencia_id, p_proceso_id, p_criterio_aprobacion_id, p_usuario_id, p_estado, NOW(), NOW());
    END IF;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_EVIDENCIAS_POR_CRITERIO_APROBACION(IN p_criterio_id BIGINT)
BEGIN
    SELECT ev.evidencia_id, ev.nomenclatura, ev.descripcion
    FROM EVIDENCIA ev
    WHERE ev.criterio_id = p_criterio_id AND ev.activo = 1;
END
        ');

        // =====================================================================
        // NOTIFICACION
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_CREAR_NOTIFICACION(
    IN p_usuario_id BIGINT,
    IN p_tipo_evento VARCHAR(50),
    IN p_canal VARCHAR(20),
    IN p_titulo VARCHAR(200),
    IN p_mensaje TEXT,
    IN p_enlace VARCHAR(500),
    IN p_metadatos JSON,
    IN p_relacionado_type VARCHAR(255),
    IN p_relacionado_id BIGINT,
    IN p_estado_email VARCHAR(20)
)
BEGIN
    INSERT INTO NOTIFICACION
        (usuario_id, tipo_evento, canal, titulo, mensaje, enlace, metadatos,
         relacionado_type, relacionado_id, estado_email, leida, created_at, updated_at)
    VALUES
        (p_usuario_id, p_tipo_evento, p_canal, p_titulo, p_mensaje, p_enlace, p_metadatos,
         p_relacionado_type, p_relacionado_id, p_estado_email, 0, NOW(), NOW());
    SELECT * FROM NOTIFICACION WHERE notificacion_id = LAST_INSERT_ID() LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_VERIFICAR_NOTIFICACION_DUPLICADA(
    IN p_usuario_id BIGINT,
    IN p_tipo_evento VARCHAR(50),
    IN p_titulo VARCHAR(200)
)
BEGIN
    SELECT COUNT(*) AS total
    FROM NOTIFICACION
    WHERE usuario_id = p_usuario_id
      AND tipo_evento = p_tipo_evento
      AND titulo = p_titulo
      AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_ULTIMA_NOTIFICACION(
    IN p_usuario_id BIGINT,
    IN p_tipo_evento VARCHAR(50)
)
BEGIN
    SELECT * FROM NOTIFICACION
    WHERE usuario_id = p_usuario_id AND tipo_evento = p_tipo_evento
    ORDER BY created_at DESC LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_NOTIFICACIONES(
    IN p_usuario_id BIGINT,
    IN p_leida TINYINT,
    IN p_tipo_evento VARCHAR(50),
    IN p_fecha_desde VARCHAR(30),
    IN p_fecha_hasta VARCHAR(30)
)
BEGIN
    SELECT *
    FROM NOTIFICACION
    WHERE usuario_id = p_usuario_id
      AND (p_leida IS NULL OR leida = p_leida)
      AND (p_tipo_evento IS NULL OR p_tipo_evento = \'\' OR tipo_evento = p_tipo_evento)
      AND (p_fecha_desde IS NULL OR p_fecha_desde = \'\' OR created_at >= p_fecha_desde)
      AND (p_fecha_hasta IS NULL OR p_fecha_hasta = \'\' OR created_at <= p_fecha_hasta)
    ORDER BY created_at DESC;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_MARCAR_NOTIFICACION_LEIDA(IN p_id BIGINT)
BEGIN
    UPDATE NOTIFICACION SET leida = 1, fecha_lectura = NOW(), updated_at = NOW()
    WHERE notificacion_id = p_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_MARCAR_TODAS_NOTIFICACIONES_LEIDAS(IN p_usuario_id BIGINT)
BEGIN
    UPDATE NOTIFICACION
    SET leida = 1, fecha_lectura = NOW(), updated_at = NOW()
    WHERE usuario_id = p_usuario_id AND leida = 0;
    SELECT ROW_COUNT() AS affected;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CONTAR_NO_LEIDAS(IN p_usuario_id BIGINT)
BEGIN
    SELECT COUNT(*) AS total FROM NOTIFICACION WHERE usuario_id = p_usuario_id AND leida = 0;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_LIMPIAR_NOTIFICACIONES_ANTIGUAS(IN p_dias INT)
BEGIN
    DELETE FROM NOTIFICACION
    WHERE leida = 1 AND created_at < DATE_SUB(NOW(), INTERVAL p_dias DAY);
    SELECT ROW_COUNT() AS deleted;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_ESTADO_EMAIL_NOTIFICACION(
    IN p_id BIGINT,
    IN p_estado_email VARCHAR(20),
    IN p_detalle_error TEXT
)
BEGIN
    UPDATE NOTIFICACION
    SET estado_email = p_estado_email, detalle_error = p_detalle_error, updated_at = NOW()
    WHERE notificacion_id = p_id;
END
        ');

        // =====================================================================
        // COMPROMISO_MEJORA (ImprovementCommitment)
        // =====================================================================
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_COMPROMISOS_MEJORA(
    IN p_search VARCHAR(255),
    IN p_estado VARCHAR(30),
    IN p_proceso_id BIGINT,
    IN p_usuario_id BIGINT,
    IN p_offset INT,
    IN p_per_page INT
)
BEGIN
    SELECT cm.*,
        p.tipo_proceso, p.ciclo_acreditacion_id
    FROM COMPROMISO_MEJORA cm
    JOIN PROCESO p ON p.proceso_id = cm.proceso_id
    WHERE (p_search IS NULL OR p_search = \'\' OR cm.descripcion LIKE CONCAT(\'%\', p_search, \'%\'))
      AND (p_estado IS NULL OR p_estado = \'\' OR cm.estado = p_estado)
      AND (p_proceso_id IS NULL OR cm.proceso_id = p_proceso_id)
      AND (p_usuario_id IS NULL OR EXISTS (
            SELECT 1 FROM COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION cmea
            JOIN EVIDENCIA_ASIGNACION ea ON ea.evidencia_asignacion_id = cmea.evidencia_asignacion_id
            WHERE cmea.compromiso_mejora_id = cm.compromiso_mejora_id
              AND ea.usuario_id = p_usuario_id
          ))
    ORDER BY cm.created_at DESC
    LIMIT p_per_page OFFSET p_offset;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CONTAR_COMPROMISOS_MEJORA(
    IN p_search VARCHAR(255),
    IN p_estado VARCHAR(30),
    IN p_proceso_id BIGINT,
    IN p_usuario_id BIGINT
)
BEGIN
    SELECT COUNT(*) AS total
    FROM COMPROMISO_MEJORA cm
    WHERE (p_search IS NULL OR p_search = \'\' OR cm.descripcion LIKE CONCAT(\'%\', p_search, \'%\'))
      AND (p_estado IS NULL OR p_estado = \'\' OR cm.estado = p_estado)
      AND (p_proceso_id IS NULL OR cm.proceso_id = p_proceso_id)
      AND (p_usuario_id IS NULL OR EXISTS (
            SELECT 1 FROM COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION cmea
            JOIN EVIDENCIA_ASIGNACION ea ON ea.evidencia_asignacion_id = cmea.evidencia_asignacion_id
            WHERE cmea.compromiso_mejora_id = cm.compromiso_mejora_id
              AND ea.usuario_id = p_usuario_id
          ));
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_COMPROMISO_MEJORA(IN p_id BIGINT)
BEGIN
    SELECT cm.*, p.tipo_proceso, p.ciclo_acreditacion_id
    FROM COMPROMISO_MEJORA cm
    JOIN PROCESO p ON p.proceso_id = cm.proceso_id
    WHERE cm.compromiso_mejora_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_COMPROMISO_MEJORA_POR_PROCESO(IN p_proceso_id BIGINT)
BEGIN
    SELECT cm.*, p.tipo_proceso, p.ciclo_acreditacion_id
    FROM COMPROMISO_MEJORA cm
    JOIN PROCESO p ON p.proceso_id = cm.proceso_id
    WHERE cm.proceso_id = p_proceso_id LIMIT 1;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_CREAR_COMPROMISO_MEJORA(
    IN p_proceso_id BIGINT,
    IN p_descripcion TEXT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_estado VARCHAR(30),
    IN p_activo TINYINT
)
BEGIN
    INSERT INTO COMPROMISO_MEJORA
        (proceso_id, descripcion, fecha_inicio, fecha_fin, estado, activo, created_at, updated_at)
    VALUES
        (p_proceso_id, p_descripcion, p_fecha_inicio, p_fecha_fin, p_estado, p_activo, NOW(), NOW());
    CALL SP_BUSCAR_COMPROMISO_MEJORA(LAST_INSERT_ID());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_COMPROMISO_MEJORA(
    IN p_id BIGINT,
    IN p_proceso_id BIGINT,
    IN p_descripcion TEXT,
    IN p_fecha_fin DATE,
    IN p_estado VARCHAR(30)
)
BEGIN
    UPDATE COMPROMISO_MEJORA
    SET proceso_id = COALESCE(p_proceso_id, proceso_id),
        descripcion = COALESCE(p_descripcion, descripcion),
        fecha_fin = COALESCE(p_fecha_fin, fecha_fin),
        estado = COALESCE(p_estado, estado),
        updated_at = NOW()
    WHERE compromiso_mejora_id = p_id;
    CALL SP_BUSCAR_COMPROMISO_MEJORA(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_ESTABLECER_ACTIVO_COMPROMISO_MEJORA(IN p_id BIGINT, IN p_activo TINYINT)
BEGIN
    UPDATE COMPROMISO_MEJORA SET activo = p_activo, updated_at = NOW() WHERE compromiso_mejora_id = p_id;
    CALL SP_BUSCAR_COMPROMISO_MEJORA(p_id);
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_IDS_EVIDENCIAS_COMPROMISO(IN p_compromiso_id BIGINT)
BEGIN
    SELECT evidencia_id FROM COMPROMISO_MEJORA_EVIDENCIA WHERE compromiso_mejora_id = p_compromiso_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_VINCULAR_EVIDENCIA_COMPROMISO(IN p_compromiso_id BIGINT, IN p_evidencia_ids TEXT)
BEGIN
    -- Called from PHP by iterating IDs individually
    -- This SP attaches a single evidence
    INSERT IGNORE INTO COMPROMISO_MEJORA_EVIDENCIA (compromiso_mejora_id, evidencia_id, created_at, updated_at)
    VALUES (p_compromiso_id, p_evidencia_ids, NOW(), NOW());
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_DESVINCULAR_EVIDENCIAS_COMPROMISO(IN p_compromiso_id BIGINT)
BEGIN
    DELETE FROM COMPROMISO_MEJORA_EVIDENCIA WHERE compromiso_mejora_id = p_compromiso_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_VINCULAR_ASIGNACION_COMPROMISO(
    IN p_compromiso_id BIGINT,
    IN p_evidencia_asignacion_id BIGINT,
    IN p_comentario TEXT
)
BEGIN
    INSERT INTO COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION
        (compromiso_mejora_id, evidencia_asignacion_id, comentario, created_at, updated_at)
    VALUES (p_compromiso_id, p_evidencia_asignacion_id, p_comentario, NOW(), NOW())
    ON DUPLICATE KEY UPDATE comentario = p_comentario, updated_at = NOW();
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_DESVINCULAR_ASIGNACIONES_COMPROMISO(IN p_compromiso_id BIGINT)
BEGIN
    DELETE FROM COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION WHERE compromiso_mejora_id = p_compromiso_id;
END
        ');

        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_IDS_ASIGNACIONES_COMPROMISO(IN p_compromiso_id BIGINT)
BEGIN
    SELECT evidencia_asignacion_id FROM COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION
    WHERE compromiso_mejora_id = p_compromiso_id;
END
        ');
    }

    public function down(): void
    {
        $this->dropAll();
    }

    private function dropAll(): void
    {
        $procedures = [
            // UNIVERSIDAD
            'SP_OBTENER_UNIVERSIDADES', 'SP_BUSCAR_UNIVERSIDAD', 'SP_CREAR_UNIVERSIDAD',
            'SP_ACTUALIZAR_UNIVERSIDAD', 'SP_ELIMINAR_UNIVERSIDAD',
            // SEDE
            'SP_OBTENER_SEDES', 'SP_BUSCAR_SEDE', 'SP_CREAR_SEDE',
            'SP_ACTUALIZAR_SEDE', 'SP_ELIMINAR_SEDE',
            // CARRERA
            'SP_OBTENER_CARRERAS', 'SP_BUSCAR_CARRERA', 'SP_CREAR_CARRERA',
            'SP_ACTUALIZAR_CARRERA', 'SP_ELIMINAR_CARRERA',
            // CARRERA_SEDE
            'SP_OBTENER_CARRERAS_SEDES', 'SP_BUSCAR_CARRERA_SEDE', 'SP_CREAR_CARRERA_SEDE',
            'SP_ELIMINAR_CARRERA_SEDE',
            // CICLO_ACREDITACION
            'SP_OBTENER_CICLOS_ACREDITACION', 'SP_BUSCAR_CICLO_ACREDITACION',
            'SP_CREAR_CICLO_ACREDITACION', 'SP_ACTUALIZAR_CICLO_ACREDITACION',
            'SP_ELIMINAR_CICLO_ACREDITACION',
            // PROCESO
            'SP_OBTENER_PROCESOS', 'SP_BUSCAR_PROCESO', 'SP_CREAR_PROCESO',
            'SP_ACTUALIZAR_PROCESO', 'SP_ELIMINAR_PROCESO', 'SP_OBTENER_O_CREAR_PROCESO_MEJORA',
            // AUTOEVALUACION
            'SP_OBTENER_AUTOEVALUACIONES', 'SP_BUSCAR_AUTOEVALUACION', 'SP_CREAR_AUTOEVALUACION',
            'SP_ACTUALIZAR_AUTOEVALUACION', 'SP_ELIMINAR_AUTOEVALUACION',
            // DIMENSION
            'SP_OBTENER_DIMENSIONES', 'SP_BUSCAR_DIMENSION', 'SP_CREAR_DIMENSION',
            'SP_ACTUALIZAR_DIMENSION', 'SP_ELIMINAR_DIMENSION',
            // COMPONENTE
            'SP_OBTENER_COMPONENTES', 'SP_BUSCAR_COMPONENTE', 'SP_CREAR_COMPONENTE',
            'SP_ACTUALIZAR_COMPONENTE', 'SP_ELIMINAR_COMPONENTE',
            // CRITERIO
            'SP_OBTENER_CRITERIOS', 'SP_BUSCAR_CRITERIO', 'SP_CREAR_CRITERIO',
            'SP_ACTUALIZAR_CRITERIO', 'SP_ELIMINAR_CRITERIO',
            // ESTADO_EVIDENCIA
            'SP_OBTENER_ESTADOS_EVIDENCIA', 'SP_BUSCAR_ESTADO_EVIDENCIA', 'SP_CREAR_ESTADO_EVIDENCIA',
            'SP_ACTUALIZAR_ESTADO_EVIDENCIA', 'SP_ELIMINAR_ESTADO_EVIDENCIA',
            // ESTANDAR
            'SP_OBTENER_ESTANDARES', 'SP_BUSCAR_ESTANDAR', 'SP_CREAR_ESTANDAR',
            'SP_ACTUALIZAR_ESTANDAR', 'SP_ELIMINAR_ESTANDAR',
            // EVIDENCIA
            'SP_OBTENER_EVIDENCIAS', 'SP_BUSCAR_EVIDENCIA', 'SP_CREAR_EVIDENCIA',
            'SP_ACTUALIZAR_EVIDENCIA', 'SP_ELIMINAR_EVIDENCIA',
            'SP_OBTENER_EVIDENCIAS_POR_ESTANDAR', 'SP_OBTENER_EVIDENCIAS_POR_DIMENSION',
            'SP_OBTENER_EVIDENCIAS_POR_COMPONENTE', 'SP_OBTENER_EVIDENCIAS_POR_CRITERIO',
            'SP_FILTRAR_EVIDENCIAS', 'SP_CONTAR_FILTRO_EVIDENCIAS',
            // ARCHIVO
            'SP_OBTENER_ARCHIVOS_POR_EVIDENCIA', 'SP_BUSCAR_ARCHIVO', 'SP_BUSCAR_ARCHIVO_POR_TOKEN',
            'SP_CREAR_ARCHIVO', 'SP_ACTUALIZAR_ARCHIVO_PUBLICO', 'SP_ELIMINAR_ARCHIVO',
            // USUARIO
            'SP_BUSCAR_USUARIO', 'SP_BUSCAR_USUARIO_POR_CEDULA', 'SP_BUSCAR_USUARIO_POR_EMAIL',
            'SP_CREAR_USUARIO', 'SP_ACTUALIZAR_USUARIO', 'SP_ACTIVAR_USUARIO', 'SP_DESACTIVAR_USUARIO',
            // TIPO_ACCION / BITACORA
            'SP_OBTENER_TIPOS_ACCION', 'SP_BUSCAR_TIPO_ACCION_POR_NOMBRE',
            'SP_REGISTRAR_ACCION', 'SP_OBTENER_BITACORA', 'SP_CONTAR_BITACORA',
            'SP_OBTENER_MODULOS_BITACORA', 'SP_OBTENER_BITACORA_EXPORTACION', 'SP_CONTAR_BITACORA_EXPORTACION',
            // COMENTARIO
            'SP_OBTENER_COMENTARIOS', 'SP_BUSCAR_COMENTARIO', 'SP_CREAR_COMENTARIO', 'SP_ELIMINAR_COMENTARIO',
            // EVIDENCIA_ASIGNACION
            'SP_OBTENER_ASIGNACIONES_EVIDENCIA', 'SP_BUSCAR_ASIGNACION_EVIDENCIA',
            'SP_CREAR_ASIGNACION_EVIDENCIA', 'SP_ACTUALIZAR_ASIGNACION_EVIDENCIA',
            'SP_ELIMINAR_ASIGNACION_EVIDENCIA', 'SP_VERIFICAR_DUPLICADO_ASIGNACION',
            'SP_OBTENER_EVIDENCIAS_PROXIMAS',
            // SOLICITUD_AMPLIACION
            'SP_OBTENER_SOLICITUDES_AMPLIACION', 'SP_CONTAR_SOLICITUDES_AMPLIACION',
            'SP_BUSCAR_SOLICITUD_AMPLIACION', 'SP_CREAR_SOLICITUD_AMPLIACION',
            'SP_VERIFICAR_SOLICITUD_PENDIENTE', 'SP_APROBAR_SOLICITUD_AMPLIACION',
            'SP_RECHAZAR_SOLICITUD_AMPLIACION', 'SP_ELIMINAR_SOLICITUD_AMPLIACION',
            // APROBACION_CRITERIO
            'SP_OBTENER_APROBACIONES_CRITERIO', 'SP_BUSCAR_APROBACION_CRITERIO',
            'SP_BUSCAR_APROBACION_CRITERIO_PROCESO', 'SP_UPSERT_APROBACION_CRITERIO',
            'SP_OBTENER_EVIDENCIAS_POR_CRITERIO_APROBACION',
            // APROBACION_EVIDENCIA
            'SP_UPSERT_APROBACION_EVIDENCIA',
            // NOTIFICACION
            'SP_CREAR_NOTIFICACION', 'SP_VERIFICAR_NOTIFICACION_DUPLICADA',
            'SP_OBTENER_ULTIMA_NOTIFICACION', 'SP_OBTENER_NOTIFICACIONES',
            'SP_MARCAR_NOTIFICACION_LEIDA', 'SP_MARCAR_TODAS_NOTIFICACIONES_LEIDAS',
            'SP_CONTAR_NO_LEIDAS', 'SP_LIMPIAR_NOTIFICACIONES_ANTIGUAS',
            'SP_ACTUALIZAR_ESTADO_EMAIL_NOTIFICACION',
            // COMPROMISO_MEJORA
            'SP_OBTENER_COMPROMISOS_MEJORA', 'SP_CONTAR_COMPROMISOS_MEJORA',
            'SP_BUSCAR_COMPROMISO_MEJORA', 'SP_BUSCAR_COMPROMISO_MEJORA_POR_PROCESO',
            'SP_CREAR_COMPROMISO_MEJORA', 'SP_ACTUALIZAR_COMPROMISO_MEJORA', 'SP_ESTABLECER_ACTIVO_COMPROMISO_MEJORA',
            'SP_OBTENER_IDS_EVIDENCIAS_COMPROMISO', 'SP_VINCULAR_EVIDENCIA_COMPROMISO',
            'SP_DESVINCULAR_EVIDENCIAS_COMPROMISO', 'SP_VINCULAR_ASIGNACION_COMPROMISO',
            'SP_DESVINCULAR_ASIGNACIONES_COMPROMISO', 'SP_OBTENER_IDS_ASIGNACIONES_COMPROMISO',
        ];

        foreach ($procedures as $sp) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$sp}");
        }
    }
};
