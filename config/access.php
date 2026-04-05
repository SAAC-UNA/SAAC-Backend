<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission aliases
    |--------------------------------------------------------------------------
    | Equivalencias semanticas entre permisos historicos y actuales.
    | Se usan para evitar 403 cuando dos modulos expresan la misma capacidad
    | con nombres diferentes.
    */
    'permission_aliases' => [
        'evidencias.view' => [
            'asignaciones.view',
        ],
        'evidencias.assign' => [
            'asignaciones.create',
            'asignaciones.edit',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Capability contract (backend -> frontend)
    |--------------------------------------------------------------------------
    | Capacidades funcionales de alto nivel para UI y autorizacion declarativa.
    */
    'capabilities' => [
        'cap.admin.roles.manage' => ['roles.create', 'roles.edit', 'roles.delete'],
        'cap.admin.users.manage' => ['usuarios.create', 'usuarios.edit', 'usuarios.delete'],
        'cap.audit.view' => ['bitacora.view'],

        'cap.evidence.assign' => ['evidencias.assign', 'asignaciones.create', 'asignaciones.edit'],
        'cap.evidence.view' => ['evidencias.view', 'asignaciones.view'],
        'cap.evidence.upload' => ['archivos.upload'],

        'cap.extension.manage' => ['solicitudes_ampliacion.approve', 'solicitudes_ampliacion.reject'],
        'cap.extension.view' => ['solicitudes_ampliacion.view'],

        'cap.accreditation.process.view' => ['procesos.view'],
        'cap.accreditation.model.view' => ['modelos.view'],
        'cap.accreditation.cycle.view' => ['ciclos.view'],

        'cap.improvement.access' => ['compromisos_mejora.view', 'compromisos_mejora.create', 'compromisos_mejora.edit'],
        'cap.approvals.view' => ['aprobaciones.view'],
        'cap.reports.access' => ['reportes.generate', 'reportes.export'],
    ],

];
