<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agregar stored procedures para MODELO_ESTRUCTURA
     */
    public function up(): void
    {
        // SP para obtener todos los modelos
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_MODELOS_ESTRUCTURA');
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_MODELOS_ESTRUCTURA()
BEGIN
    SELECT * FROM MODELO_ESTRUCTURA ORDER BY version DESC, nombre;
END
        ');

        // SP para obtener modelos activos
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_MODELOS_ACTIVOS');
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_MODELOS_ACTIVOS()
BEGIN
    SELECT * FROM MODELO_ESTRUCTURA WHERE activo = 1 ORDER BY version DESC;
END
        ');

        // SP para buscar modelo por ID
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_BUSCAR_MODELO_ESTRUCTURA');
        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_MODELO_ESTRUCTURA(IN p_id BIGINT)
BEGIN
    SELECT * FROM MODELO_ESTRUCTURA WHERE modelo_estructura_id = p_id LIMIT 1;
END
        ');

        // SP modificado para CREAR_PROCESO (ahora incluye modelo_estructura_id)
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_CREAR_PROCESO');
        DB::unprepared('
CREATE PROCEDURE SP_CREAR_PROCESO(
    IN p_ciclo_id BIGINT, 
    IN p_tipo VARCHAR(50),
    IN p_modelo_estructura_id BIGINT
)
BEGIN
    INSERT INTO PROCESO (ciclo_acreditacion_id, tipo_proceso, modelo_estructura_id, created_at, updated_at)
    VALUES (p_ciclo_id, p_tipo, p_modelo_estructura_id, NOW(), NOW());
    CALL SP_BUSCAR_PROCESO(LAST_INSERT_ID());
END
        ');

        // SP modificado para ACTUALIZAR_PROCESO
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_PROCESO');
        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_PROCESO(
    IN p_id BIGINT, 
    IN p_ciclo_id BIGINT, 
    IN p_tipo VARCHAR(50),
    IN p_modelo_estructura_id BIGINT
)
BEGIN
    UPDATE PROCESO
    SET ciclo_acreditacion_id = p_ciclo_id, 
        tipo_proceso = p_tipo,
        modelo_estructura_id = p_modelo_estructura_id,
        updated_at = NOW()
    WHERE proceso_id = p_id;
    CALL SP_BUSCAR_PROCESO(p_id);
END
        ');

        // SP modificado para BUSCAR_PROCESO (ahora incluye JOIN con MODELO_ESTRUCTURA)
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_BUSCAR_PROCESO');
        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_PROCESO(IN p_id BIGINT)
BEGIN
    SELECT p.*, 
           ca.nombre AS ciclo_nombre,
           me.nombre AS modelo_nombre,
           me.tipo AS modelo_tipo,
           me.version AS modelo_version
    FROM PROCESO p
    JOIN CICLO_ACREDITACION ca ON ca.ciclo_acreditacion_id = p.ciclo_acreditacion_id
    LEFT JOIN MODELO_ESTRUCTURA me ON me.modelo_estructura_id = p.modelo_estructura_id
    WHERE p.proceso_id = p_id LIMIT 1;
END
        ');

        // SP modificado para OBTENER_PROCESOS
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_PROCESOS');
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_PROCESOS(IN p_ciclo_id BIGINT, IN p_tipo VARCHAR(50))
BEGIN
    SELECT p.*, 
           ca.nombre AS ciclo_nombre,
           me.nombre AS modelo_nombre,
           me.tipo AS modelo_tipo
    FROM PROCESO p
    JOIN CICLO_ACREDITACION ca ON ca.ciclo_acreditacion_id = p.ciclo_acreditacion_id
    LEFT JOIN MODELO_ESTRUCTURA me ON me.modelo_estructura_id = p.modelo_estructura_id
    WHERE (p_ciclo_id IS NULL OR p.ciclo_acreditacion_id = p_ciclo_id)
      AND (p_tipo IS NULL OR p.tipo_proceso = p_tipo)
    ORDER BY p.proceso_id;
END
        ');
    }

    public function down(): void
    {
        // Restaurar SPs originales
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_MODELOS_ESTRUCTURA');
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_MODELOS_ACTIVOS');
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_BUSCAR_MODELO_ESTRUCTURA');

        // Restaurar versiones originales de los SP de PROCESO
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_CREAR_PROCESO');
        DB::unprepared('
CREATE PROCEDURE SP_CREAR_PROCESO(IN p_ciclo_id BIGINT, IN p_tipo VARCHAR(50))
BEGIN
    INSERT INTO PROCESO (ciclo_acreditacion_id, tipo_proceso, created_at, updated_at)
    VALUES (p_ciclo_id, p_tipo, NOW(), NOW());
    CALL SP_BUSCAR_PROCESO(LAST_INSERT_ID());
END
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_PROCESO');
        DB::unprepared('
CREATE PROCEDURE SP_ACTUALIZAR_PROCESO(IN p_id BIGINT, IN p_ciclo_id BIGINT, IN p_tipo VARCHAR(50))
BEGIN
    UPDATE PROCESO
    SET ciclo_acreditacion_id = p_ciclo_id, tipo_proceso = p_tipo, updated_at = NOW()
    WHERE proceso_id = p_id;
    CALL SP_BUSCAR_PROCESO(p_id);
END
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS SP_BUSCAR_PROCESO');
        DB::unprepared('
CREATE PROCEDURE SP_BUSCAR_PROCESO(IN p_id BIGINT)
BEGIN
    SELECT p.*, ca.nombre AS ciclo_nombre
    FROM PROCESO p
    JOIN CICLO_ACREDITACION ca ON ca.ciclo_acreditacion_id = p.ciclo_acreditacion_id
    WHERE p.proceso_id = p_id LIMIT 1;
END
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_PROCESOS');
        DB::unprepared('
CREATE PROCEDURE SP_OBTENER_PROCESOS(IN p_ciclo_id BIGINT, IN p_tipo VARCHAR(50))
BEGIN
    SELECT p.*, ca.nombre AS ciclo_nombre
    FROM PROCESO p
    JOIN CICLO_ACREDITACION ca ON ca.ciclo_acreditacion_id = p.ciclo_acreditacion_id
    WHERE (p_ciclo_id IS NULL OR p.ciclo_acreditacion_id = p_ciclo_id)
      AND (p_tipo IS NULL OR p.tipo_proceso = p_tipo)
    ORDER BY p.proceso_id;
END
        ');
    }
};
