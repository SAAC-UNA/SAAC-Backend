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
        // Permisos de gestión general (aliases)
        'gestion_roles'                => 'Gestión de Roles',
        'gestion_usuarios'             => 'Gestión de Usuarios',
        'gestion_reportes'             => 'Gestión de Reportes',
        'gestion_ciclos'               => 'Gestión de Ciclos',
        'gestion_evidencias'           => 'Gestión de Evidencias',
        'gestion_programas'            => 'Gestión de Programas',
        
        // Permisos atómicos de usuarios
        'usuarios.view'                => 'Ver Usuarios',
        'usuarios.create'              => 'Crear Usuarios',
        'usuarios.edit'                => 'Editar Usuarios',
        'usuarios.delete'              => 'Eliminar Usuarios',
        
        // Permisos atómicos de evidencias
        'evidencias.view'              => 'Ver Evidencias',
        'evidencias.create'            => 'Crear Evidencias',
        'evidencias.edit'              => 'Editar Evidencias',
        'evidencias.delete'            => 'Eliminar Evidencias',
        
        // Permisos atómicos de reportes
        'reportes.generate'            => 'Generar Reportes',
        
        // Permisos atómicos de ciclos
        'ciclos.view'                  => 'Ver Ciclos',
        'ciclos.create'                => 'Crear Ciclos',
        'ciclos.edit'                  => 'Editar Ciclos',
        'ciclos.delete'                => 'Eliminar Ciclos',
        
        // Permiso maestro
        'admin.super'                  => 'Super Administrador',
    ],

    // Matriz oficial de módulos y acciones atómicas (HU-02)
    'modules' => [
        'usuarios'   => ['view', 'create', 'edit', 'delete'],
        'evidencias' => ['view', 'create', 'edit', 'delete'],
        'reportes'   => ['generate'],
        'ciclos'     => ['view', 'create', 'edit', 'delete'],
    ],


];
