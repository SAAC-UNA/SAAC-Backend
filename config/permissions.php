<?php

/**
 * Lista oficial de permisos del sistema SAAC-UNA.
 * 
 * Este archivo es la fuente única de verdad (single source of truth)
 * para los permisos base. Se utiliza en seeders, validaciones
 * y cualquier lógica que requiera conocer los permisos oficiales.
 *
 * Nomenclatura:
 * - Claves internas (list y descriptions): en español y snake_case,
 *   porque así están almacenadas en la base de datos.
 * - Etiquetas legibles (descriptions): en español, para mostrar al usuario final.
 */

return [

    'list' => [
        'gestion_roles',
        'gestion_usuarios',
        'gestion_reportes',
        'gestion_ciclos',
    ],

    // Etiquetas legibles para frontend
    'descriptions' => [
        'gestion_roles'                => 'Gestión de Roles',
        'gestion_usuarios'             => 'Gestión de Usuarios',
        'gestion_reportes'             => 'Gestión de Reportes',
        'gestion_ciclos'               => 'Gestión de Ciclos',


    ],

    // Matriz oficial de módulos y acciones atómicas (HU-02)
    'modules' => [
        'usuarios'   => ['view', 'create', 'edit', 'delete'],
        'evidencias' => ['view', 'create', 'edit', 'delete'],
        'reportes'   => ['generate'],
        'ciclos'     => ['view', 'create', 'edit', 'delete'],
    ],


];
