<?php

/**
 * Lista oficial de permisos del sistema SAAC-UNA.
 * 
 * Este archivo es la fuente única de verdad (single source of truth)
 * para los permisos base. Se utiliza en seeders, validaciones
 * y cualquier lógica que requiera conocer los permisos oficiales.
 */

return [

    'list' => [
        'gestion_roles',
        'gestion_usuarios',
        'gestion_reportes',
        'gestion_programas',
        'gestion_ciclos',
    ],

];
