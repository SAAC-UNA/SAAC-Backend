<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columna modelo_estructura_id
        Schema::table('JERARQUIA', function (Blueprint $table) {
            $table->unsignedBigInteger('modelo_estructura_id')
                ->after('jerarquia_id')
                ->nullable()
                ->comment('FK al modelo de estructura al que pertenece este elemento');
            
            // FK constraint
            $table->foreign('modelo_estructura_id')
                ->references('modelo_estructura_id')
                ->on('MODELO_ESTRUCTURA')
                ->onDelete('cascade');
            
            // Índice para mejorar consultas
            $table->index('modelo_estructura_id', 'idx_jerarquia_modelo');
        });

        // 2. Actualizar registros existentes: asignar al modelo 2 (SINAES 2026 - Flexible)
        DB::statement("UPDATE JERARQUIA SET modelo_estructura_id = 2");

        // 3. Hacer el campo NOT NULL después de actualizar
        DB::statement("ALTER TABLE JERARQUIA MODIFY modelo_estructura_id BIGINT UNSIGNED NOT NULL");

        // 4. Agregar columna categoria
        Schema::table('JERARQUIA', function (Blueprint $table) {
            $table->enum('categoria', ['A', 'B', 'C', 'D'])
                ->after('tipo')
                ->nullable()
                ->comment('Categoría de relevancia (A=mayor importancia). Solo aplica para tipo=pauta');
            
            // Índice para filtrar por categoría
            $table->index('categoria', 'idx_jerarquia_categoria');
        });

        // 5. Actualizar stored procedures para incluir modelo_estructura_id y categoria
        
        // =====================================================
        // SP_OBTENER_JERARQUIAS
        // =====================================================
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_JERARQUIAS');
        DB::unprepared("
            CREATE PROCEDURE SP_OBTENER_JERARQUIAS(
                IN p_tipo VARCHAR(30),
                IN p_modelo_estructura_id BIGINT UNSIGNED
            )
            BEGIN
                IF p_tipo IS NULL AND p_modelo_estructura_id IS NULL THEN
                    SELECT 
                        jerarquia_id,
                        modelo_estructura_id,
                        parent_id,
                        nombre,
                        tipo,
                        categoria,
                        nomenclatura,
                        descripcion,
                        orden,
                        activo,
                        created_at,
                        updated_at
                    FROM JERARQUIA
                    WHERE activo = 1
                    ORDER BY orden ASC, jerarquia_id ASC;
                    
                ELSEIF p_tipo IS NULL THEN
                    SELECT 
                        jerarquia_id,
                        modelo_estructura_id,
                        parent_id,
                        nombre,
                        tipo,
                        categoria,
                        nomenclatura,
                        descripcion,
                        orden,
                        activo,
                        created_at,
                        updated_at
                    FROM JERARQUIA
                    WHERE activo = 1
                      AND modelo_estructura_id = p_modelo_estructura_id
                    ORDER BY orden ASC, jerarquia_id ASC;
                    
                ELSEIF p_modelo_estructura_id IS NULL THEN
                    SELECT 
                        jerarquia_id,
                        modelo_estructura_id,
                        parent_id,
                        nombre,
                        tipo,
                        categoria,
                        nomenclatura,
                        descripcion,
                        orden,
                        activo,
                        created_at,
                        updated_at
                    FROM JERARQUIA
                    WHERE tipo = p_tipo
                      AND activo = 1
                    ORDER BY orden ASC, jerarquia_id ASC;
                    
                ELSE
                    SELECT 
                        jerarquia_id,
                        modelo_estructura_id,
                        parent_id,
                        nombre,
                        tipo,
                        categoria,
                        nomenclatura,
                        descripcion,
                        orden,
                        activo,
                        created_at,
                        updated_at
                    FROM JERARQUIA
                    WHERE tipo = p_tipo
                      AND modelo_estructura_id = p_modelo_estructura_id
                      AND activo = 1
                    ORDER BY orden ASC, jerarquia_id ASC;
                END IF;
            END
        ");

        // =====================================================
        // SP_OBTENER_ARBOL_JERARQUIA
        // =====================================================
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_ARBOL_JERARQUIA');
        DB::unprepared("
            CREATE PROCEDURE SP_OBTENER_ARBOL_JERARQUIA(
                IN p_root_id BIGINT UNSIGNED,
                IN p_modelo_estructura_id BIGINT UNSIGNED
            )
            BEGIN
                WITH RECURSIVE arbol AS (
                    -- Caso base: elementos raíz
                    SELECT 
                        jerarquia_id,
                        modelo_estructura_id,
                        parent_id,
                        nombre,
                        tipo,
                        categoria,
                        nomenclatura,
                        descripcion,
                        orden,
                        activo,
                        0 AS nivel,
                        CAST(jerarquia_id AS CHAR(500)) AS ruta,
                        CAST(LPAD(orden, 5, '0') AS CHAR(1000)) AS orden_completo
                    FROM JERARQUIA
                    WHERE parent_id IS NULL
                      AND activo = 1
                      AND (p_root_id IS NULL OR jerarquia_id = p_root_id)
                      AND (p_modelo_estructura_id IS NULL OR modelo_estructura_id = p_modelo_estructura_id)
                    
                    UNION ALL
                    
                    -- Caso recursivo: hijos
                    SELECT 
                        j.jerarquia_id,
                        j.modelo_estructura_id,
                        j.parent_id,
                        j.nombre,
                        j.tipo,
                        j.categoria,
                        j.nomenclatura,
                        j.descripcion,
                        j.orden,
                        j.activo,
                        a.nivel + 1,
                        CONCAT(a.ruta, '->', j.jerarquia_id),
                        CONCAT(a.orden_completo, '-', LPAD(j.orden, 5, '0'))
                    FROM JERARQUIA j
                    INNER JOIN arbol a ON j.parent_id = a.jerarquia_id
                    WHERE j.activo = 1
                      AND (p_modelo_estructura_id IS NULL OR j.modelo_estructura_id = p_modelo_estructura_id)
                )
                SELECT 
                    jerarquia_id,
                    modelo_estructura_id,
                    parent_id,
                    nombre,
                    tipo,
                    categoria,
                    nomenclatura,
                    descripcion,
                    orden,
                    activo,
                    nivel,
                    ruta
                FROM arbol
                ORDER BY orden_completo;
            END
        ");

        // =====================================================
        // SP_CREAR_JERARQUIA
        // =====================================================
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_CREAR_JERARQUIA');
        DB::unprepared("
            CREATE PROCEDURE SP_CREAR_JERARQUIA(
                IN p_modelo_estructura_id BIGINT UNSIGNED,
                IN p_parent_id BIGINT UNSIGNED,
                IN p_nombre VARCHAR(255),
                IN p_tipo VARCHAR(30),
                IN p_categoria ENUM('A', 'B', 'C', 'D'),
                IN p_nomenclatura VARCHAR(50),
                IN p_descripcion TEXT,
                IN p_orden INT,
                IN p_activo TINYINT(1)
            )
            BEGIN
                INSERT INTO JERARQUIA (
                    modelo_estructura_id,
                    parent_id,
                    nombre,
                    tipo,
                    categoria,
                    nomenclatura,
                    descripcion,
                    orden,
                    activo,
                    created_at,
                    updated_at
                ) VALUES (
                    p_modelo_estructura_id,
                    p_parent_id,
                    p_nombre,
                    p_tipo,
                    p_categoria,
                    p_nomenclatura,
                    p_descripcion,
                    COALESCE(p_orden, 1),
                    COALESCE(p_activo, 1),
                    NOW(),
                    NOW()
                );
                
                SELECT LAST_INSERT_ID() AS jerarquia_id;
            END
        ");

        // =====================================================
        // SP_ACTUALIZAR_JERARQUIA
        // =====================================================
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_JERARQUIA');
        DB::unprepared("
            CREATE PROCEDURE SP_ACTUALIZAR_JERARQUIA(
                IN p_jerarquia_id BIGINT UNSIGNED,
                IN p_parent_id BIGINT UNSIGNED,
                IN p_nombre VARCHAR(255),
                IN p_tipo VARCHAR(30),
                IN p_categoria ENUM('A', 'B', 'C', 'D'),
                IN p_nomenclatura VARCHAR(50),
                IN p_descripcion TEXT,
                IN p_orden INT,
                IN p_activo TINYINT(1)
            )
            BEGIN
                UPDATE JERARQUIA
                SET 
                    parent_id = p_parent_id,
                    nombre = COALESCE(p_nombre, nombre),
                    tipo = COALESCE(p_tipo, tipo),
                    categoria = p_categoria,
                    nomenclatura = COALESCE(p_nomenclatura, nomenclatura),
                    descripcion = p_descripcion,
                    orden = COALESCE(p_orden, orden),
                    activo = COALESCE(p_activo, activo),
                    updated_at = NOW()
                WHERE jerarquia_id = p_jerarquia_id;
                
                -- Devolver el registro actualizado (no affected_rows)
                SELECT * FROM JERARQUIA WHERE jerarquia_id = p_jerarquia_id LIMIT 1;
            END
        ");

        // =====================================================
        // SP_ELIMINAR_JERARQUIA
        // =====================================================
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_ELIMINAR_JERARQUIA');
        DB::unprepared("
            CREATE PROCEDURE SP_ELIMINAR_JERARQUIA(
                IN p_jerarquia_id BIGINT UNSIGNED
            )
            BEGIN
                DECLARE v_has_children INT;
                
                -- Verificar si tiene hijos
                SELECT COUNT(*) INTO v_has_children
                FROM JERARQUIA
                WHERE parent_id = p_jerarquia_id;
                
                IF v_has_children > 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'No se puede eliminar un elemento que tiene hijos';
                ELSE
                    DELETE FROM JERARQUIA WHERE jerarquia_id = p_jerarquia_id;
                    SELECT ROW_COUNT() AS affected_rows;
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar stored procedures a versión anterior
        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_JERARQUIAS');
        DB::unprepared("
            CREATE PROCEDURE SP_OBTENER_JERARQUIAS(IN p_tipo VARCHAR(30))
            BEGIN
                IF p_tipo IS NULL THEN
                    SELECT * FROM JERARQUIA WHERE activo = 1 ORDER BY orden ASC, jerarquia_id ASC;
                ELSE
                    SELECT * FROM JERARQUIA WHERE tipo = p_tipo AND activo = 1 ORDER BY orden ASC, jerarquia_id ASC;
                END IF;
            END
        ");

        DB::unprepared('DROP PROCEDURE IF EXISTS SP_OBTENER_ARBOL_JERARQUIA');
        DB::unprepared("
            CREATE PROCEDURE SP_OBTENER_ARBOL_JERARQUIA(IN p_root_id BIGINT UNSIGNED)
            BEGIN
                WITH RECURSIVE arbol AS (
                    SELECT jerarquia_id, parent_id, nombre, tipo, nomenclatura, descripcion, orden, activo, 0 AS nivel, CAST(jerarquia_id AS CHAR(500)) AS ruta
                    FROM JERARQUIA
                    WHERE parent_id IS NULL AND activo = 1 AND (p_root_id IS NULL OR jerarquia_id = p_root_id)
                    UNION ALL
                    SELECT j.jerarquia_id, j.parent_id, j.nombre, j.tipo, j.nomenclatura, j.descripcion, j.orden, j.activo, a.nivel + 1, CONCAT(a.ruta, '->', j.jerarquia_id)
                    FROM JERARQUIA j
                    INNER JOIN arbol a ON j.parent_id = a.jerarquia_id
                    WHERE j.activo = 1
                )
                SELECT * FROM arbol ORDER BY ruta;
            END
        ");

        // Eliminar columnas
        Schema::table('JERARQUIA', function (Blueprint $table) {
            $table->dropForeign(['modelo_estructura_id']);
            $table->dropIndex('idx_jerarquia_modelo');
            $table->dropIndex('idx_jerarquia_categoria');
            $table->dropColumn(['modelo_estructura_id', 'categoria']);
        });
    }
};
