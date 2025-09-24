<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UniversityController; // Importante importa el controlador
use App\Http\Controllers\CampusController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\CareerController;

// CRUD completo de cada endpoint
Route::apiResource('estructura/universidades', UniversityController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/campuses', CampusController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/facultades', FacultyController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/carreras', CareerController::class)->only(['index','store','show','update','destroy']);

// Ping raíz (para confirmar que el archivo se carga)
Route::get('/ping', function () {
    return response()->json([
        'ok'    => true,
        'scope' => 'root',
        'base'  => base_path(), // <- confirma carpeta
        'mark'  => 'X1'
    ]);
});


// Ruta de prueba sin controller
Route::get('/estructura/ping2', fn() => response()->json(['ok' => true, 'scope' => 'ping2']));








