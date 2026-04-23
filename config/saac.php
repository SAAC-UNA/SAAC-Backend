<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Límite de seguridad para exportar registros de bitácora
    |--------------------------------------------------------------------------
    |
    | Controla cuántos registros como máximo permite exportar el sistema
    | para evitar sobrecarga o exportaciones gigantes que comprometan
    | el rendimiento del servidor.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Disco de almacenamiento activo
    |--------------------------------------------------------------------------
    |
    | Controla qué disco de Filesystems usa FileService para subir archivos.
    | Para cambiar de almacenamiento (local NAS → S3, etc.) basta con
    | actualizar STORAGE_DISK en .env sin tocar el código fuente (DIP).
    |
    */
    'storage_disk' => env('STORAGE_DISK', 'simulated_nas'),

    // Disco dedicado para archivos de informes (separado de evidencias)
    'report_storage_disk' => env('REPORT_STORAGE_DISK', 'simulated_nas_reports'),

    'export_limit' => 20000,

];
