<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONFIGURACIÓN DE PERMISOS DEL SISTEMA SAAC-UNA
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Este archivo es la FUENTE ÚNICA DE VERDAD para permisos y roles del sistema.
 * Define permisos hardcodeados por módulo y las asignaciones de permisos por rol.
 * 
 * ESTRUCTURA:
 * - modules: Módulos del sistema con sus acciones CRUD
 * - permissions: Lista completa de permisos (generados desde modules)
 * - roles: Roles del sistema con sus permisos asignados
 * - descriptions: Etiquetas legibles para el frontend
 * 
 * NOMENCLATURA:
 * - Permisos: modulo.accion (ej: usuarios.view, evidencias.create)
 * - Acciones: view, create, edit, delete, assign, approve, reject, generate
 * 
 * USO:
 * - Seeders: Crear permisos y asignarlos a roles
 * - Policies: Verificar permisos granulares
 * - Frontend: Mostrar/ocultar elementos según permisos del usuario
 * - Middleware: Proteger rutas con permisos específicos
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Módulos y Acciones del Sistema
    |--------------------------------------------------------------------------
    |
    | Define los módulos principales del sistema y las acciones permitidas
    | en cada uno. Estas acciones generan permisos automáticamente en el
    | formato: modulo.accion
    |
    */

    'modules' => [
        // Gestión de Usuarios (HU-002)
        'usuarios' => ['view', 'create', 'edit', 'delete'],
        
        // Gestión de Roles (Admin)
        'roles' => ['view', 'create', 'edit', 'delete', 'assign'],
        
        // Estructura Académica
        'universidades' => ['view', 'create', 'edit', 'delete'],
        'campuses' => ['view', 'create', 'edit', 'delete'],
        'carreras' => ['view', 'create', 'edit', 'delete'],
        
        // Marco de Acreditación SINAES
        'dimensiones' => ['view', 'create', 'edit', 'delete'],
        'componentes' => ['view', 'create', 'edit', 'delete'],
        'criterios' => ['view', 'create', 'edit', 'delete'],
        'estandares' => ['view', 'create', 'edit', 'delete'],
        
        // Evidencias (HU-012)
        'evidencias' => ['view', 'create', 'edit', 'delete', 'assign'],
        
        // Asignaciones (HU-007)
        'asignaciones' => ['view', 'create', 'edit', 'delete'],
        
        // Archivos (HU-008)
        'archivos' => ['view', 'upload', 'download', 'delete', 'make_public'],
        
        // Solicitudes de Ampliación (HU-016)
        'solicitudes_ampliacion' => ['view', 'create', 'edit', 'delete', 'approve', 'reject'],
        
        // Aprobación de Criterios (HU-010)
        'aprobaciones' => ['view', 'approve', 'reject'],
        
        // Compromisos de Mejora
        'compromisos_mejora' => ['view', 'create', 'edit', 'delete'],
        
        // Ciclos de Acreditación (NO se pueden eliminar, solo desactivar)
        'ciclos' => ['view', 'create', 'edit'],
        
        // Reportes
        'reportes' => ['view', 'generate', 'export'],
        
        // Notificaciones (HU-018) - Solo lectura, el sistema las genera automáticamente
        'notificaciones' => ['view'],
        
        // Bitácora (HU-005)
        'bitacora' => ['view', 'export'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles del Sistema
    |--------------------------------------------------------------------------
    |
    | Define los roles del sistema y los permisos asignados a cada uno.
    | 
    | ROLES:
    | - Superusuario: Acceso total al sistema
    | - Administrador: Coordinador de carrera con gestión completa
    | - Encargado de Acreditación: Evaluación y aprobación de evidencias
    | - Profesor: Gestión de evidencias asignadas
    */

    'roles' => [
        
        'Superusuario' => [
            // Acceso total - se asignarán TODOS los permisos automáticamente
            'all_permissions' => true,
        ],

        'Administrador' => [
            // Gestión de usuarios de su carrera
            'usuarios.view',
            'usuarios.create',
            'usuarios.edit',
            'usuarios.delete',
            
            // Gestión de roles (ver y asignar a usuarios)
            'roles.view',
            'roles.assign',
            
            // Estructura académica (solo lectura)
            'universidades.view',
            'campuses.view',
            'carreras.view',
            'carreras.edit', // Puede editar SU carrera
            
            // Marco SINAES (lectura)
            'dimensiones.view',
            'componentes.view',
            'criterios.view',
            'estandares.view',
            
            // Evidencias (gestión completa)
            'evidencias.view',
            'evidencias.create',
            'evidencias.edit',
            'evidencias.delete',
            'evidencias.assign',
            
            // Asignaciones (gestión completa)
            'asignaciones.view',
            'asignaciones.create',
            'asignaciones.edit',
            'asignaciones.delete',
            
            // Archivos
            'archivos.view',
            'archivos.upload',
            'archivos.download',
            'archivos.delete',
            'archivos.make_public',
            
            // Solicitudes (ver y aprobar)
            'solicitudes_ampliacion.view',
            'solicitudes_ampliacion.approve',
            'solicitudes_ampliacion.reject',
            
            // Aprobaciones
            'aprobaciones.view',
            
            // Compromisos de mejora
            'compromisos_mejora.view',
            'compromisos_mejora.create',
            'compromisos_mejora.edit',
            'compromisos_mejora.delete',
            
            // Ciclos (no se pueden eliminar, solo desactivar)
            'ciclos.view',
            'ciclos.create',
            'ciclos.edit',
            
            // Reportes
            'reportes.view',
            'reportes.generate',
            'reportes.export',
            
            // Notificaciones (solo lectura)
            'notificaciones.view',
            
            // Bitácora (lectura y exportación)
            'bitacora.view',
            'bitacora.export',
        ],

        'Encargado de Acreditación' => [
            // Usuarios (solo lectura)
            'usuarios.view',
            
            // Estructura académica (lectura)
            'universidades.view',
            'campuses.view',
            'carreras.view',
            
            // Marco SINAES (lectura)
            'dimensiones.view',
            'componentes.view',
            'criterios.view',
            'estandares.view',
            
            // Evidencias (ver y editar estados)
            'evidencias.view',
            'evidencias.edit',
            'evidencias.assign',
            
            // Asignaciones (ver y crear)
            'asignaciones.view',
            'asignaciones.create',
            
            // Archivos (ver y descargar)
            'archivos.view',
            'archivos.download',
            'archivos.make_public',
            
            // Solicitudes (aprobar/rechazar)
            'solicitudes_ampliacion.view',
            'solicitudes_ampliacion.approve',
            'solicitudes_ampliacion.reject',
            
            // Aprobaciones (aprobar/rechazar criterios)
            'aprobaciones.view',
            'aprobaciones.approve',
            'aprobaciones.reject',
            
            // Compromisos de mejora
            'compromisos_mejora.view',
            'compromisos_mejora.create',
            'compromisos_mejora.edit',
            
            // Ciclos (lectura)
            'ciclos.view',
            
            // Reportes
            'reportes.view',
            'reportes.generate',
            'reportes.export',
            
            // Notificaciones
            'notificaciones.view',
        ],

        'Profesor' => [
            // Estructura académica (lectura)
            'carreras.view',
            
            // Marco SINAES (lectura)
            'dimensiones.view',
            'componentes.view',
            'criterios.view',
            
            // Evidencias (ver y gestionar las asignadas)
            'evidencias.view',
            'evidencias.edit', // Solo las asignadas
            
            // Asignaciones (ver las propias)
            'asignaciones.view',
            
            // Archivos (subir y descargar)
            'archivos.view',
            'archivos.upload',
            'archivos.download',
            'archivos.delete', // Solo los propios
            
            // Solicitudes (crear y ver propias)
            'solicitudes_ampliacion.view',
            'solicitudes_ampliacion.create',
            
            // Compromisos (ver los asignados)
            'compromisos_mejora.view',
            
            // Reportes (solo visualización)
            'reportes.view',
            
            // Notificaciones (solo lectura)
            'notificaciones.view',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Descripciones de Permisos (Frontend)
    |--------------------------------------------------------------------------
    |
    | Etiquetas legibles en español para mostrar en el frontend.
    | Se generan automáticamente al combinar módulos + acciones.
    |
    */

    'descriptions' => [
        // Usuarios
        'usuarios.view' => 'Ver usuarios',
        'usuarios.create' => 'Crear usuarios',
        'usuarios.edit' => 'Editar usuarios',
        'usuarios.delete' => 'Eliminar usuarios',
        
        // Roles
        'roles.view' => 'Ver roles',
        'roles.create' => 'Crear roles',
        'roles.edit' => 'Editar roles',
        'roles.delete' => 'Eliminar roles',
        
        // Universidades
        'universidades.view' => 'Ver universidades',
        'universidades.create' => 'Crear universidades',
        'universidades.edit' => 'Editar universidades',
        'universidades.delete' => 'Eliminar universidades',
        
        // Campuses
        'campuses.view' => 'Ver sedes',
        'campuses.create' => 'Crear sedes',
        'campuses.edit' => 'Editar sedes',
        'campuses.delete' => 'Eliminar sedes',
        
        // Carreras
        'carreras.view' => 'Ver carreras',
        'carreras.create' => 'Crear carreras',
        'carreras.edit' => 'Editar carreras',
        'carreras.delete' => 'Eliminar carreras',
        
        // Dimensiones
        'dimensiones.view' => 'Ver dimensiones',
        'dimensiones.create' => 'Crear dimensiones',
        'dimensiones.edit' => 'Editar dimensiones',
        'dimensiones.delete' => 'Eliminar dimensiones',
        
        // Componentes
        'componentes.view' => 'Ver componentes',
        'componentes.create' => 'Crear componentes',
        'componentes.edit' => 'Editar componentes',
        'componentes.delete' => 'Eliminar componentes',
        
        // Criterios
        'criterios.view' => 'Ver criterios',
        'criterios.create' => 'Crear criterios',
        'criterios.edit' => 'Editar criterios',
        'criterios.delete' => 'Eliminar criterios',
        
        // Estándares
        'estandares.view' => 'Ver estándares',
        'estandares.create' => 'Crear estándares',
        'estandares.edit' => 'Editar estándares',
        'estandares.delete' => 'Eliminar estándares',
        
        // Evidencias
        'evidencias.view' => 'Ver evidencias',
        'evidencias.create' => 'Crear evidencias',
        'evidencias.edit' => 'Editar evidencias',
        'evidencias.delete' => 'Eliminar evidencias',
        'evidencias.assign' => 'Asignar evidencias',
        
        // Asignaciones
        'asignaciones.view' => 'Ver asignaciones',
        'asignaciones.create' => 'Crear asignaciones',
        'asignaciones.edit' => 'Editar asignaciones',
        'asignaciones.delete' => 'Eliminar asignaciones',
        
        // Archivos
        'archivos.view' => 'Ver archivos',
        'archivos.upload' => 'Subir archivos',
        'archivos.download' => 'Descargar archivos',
        'archivos.delete' => 'Eliminar archivos',
        'archivos.make_public' => 'Hacer archivos públicos',
        
        // Solicitudes de ampliación
        'solicitudes_ampliacion.view' => 'Ver solicitudes de ampliación',
        'solicitudes_ampliacion.create' => 'Crear solicitudes de ampliación',
        'solicitudes_ampliacion.edit' => 'Editar solicitudes de ampliación',
        'solicitudes_ampliacion.delete' => 'Eliminar solicitudes de ampliación',
        'solicitudes_ampliacion.approve' => 'Aprobar solicitudes de ampliación',
        'solicitudes_ampliacion.reject' => 'Rechazar solicitudes de ampliación',
        
        // Aprobaciones de criterios
        'aprobaciones.view' => 'Ver aprobaciones de criterios',
        'aprobaciones.approve' => 'Aprobar criterios',
        'aprobaciones.reject' => 'Rechazar criterios',
        
        // Compromisos de mejora
        'compromisos_mejora.view' => 'Ver compromisos de mejora',
        'compromisos_mejora.create' => 'Crear compromisos de mejora',
        'compromisos_mejora.edit' => 'Editar compromisos de mejora',
        'compromisos_mejora.delete' => 'Eliminar compromisos de mejora',
        
        // Ciclos de acreditación
        'ciclos.view' => 'Ver ciclos de acreditación',
        'ciclos.create' => 'Crear ciclos de acreditación',
        'ciclos.edit' => 'Editar ciclos de acreditación',
        
        // Reportes
        'reportes.view' => 'Ver reportes',
        'reportes.generate' => 'Generar reportes',
        'reportes.export' => 'Exportar reportes',
        
        // Notificaciones
        'notificaciones.view' => 'Ver notificaciones',
        
        // Bitácora
        'bitacora.view' => 'Ver bitácora del sistema',
        'bitacora.export' => 'Exportar bitácora',
    ],

];
