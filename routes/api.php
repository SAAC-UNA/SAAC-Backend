<?php

use Illuminate\Support\Facades\Route;

// Importante importa el controlador
use App\Http\Controllers\UniversityController; 
use App\Http\Controllers\CampusController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\DimensionController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\EvidenceStateController;
use App\Http\Controllers\StandardController;

//solo para pruebas
use Illuminate\Support\Facades\App;
use App\Http\Controllers\DevUserController;
use App\Http\Controllers\DevCommentController;


// CRUD completo de cada endpoint
//Route::apiResource('estructura/universidades', UniversityController::class)->parameters(['universidades' => 'universidad'])->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/universidades', UniversityController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/campuses', CampusController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/facultades', FacultyController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/carreras', CareerController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/dimensiones', DimensionController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/componentes', ComponentController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/criterios', CriterionController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/evidencias', EvidenceController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/estados-evidencia', EvidenceStateController::class)->only(['index','show','store','update','destroy']);
Route::apiResource('estructura/estandares', StandardController::class)->only(['index','show','store','update','destroy']);

// Solo para pruebas
if (App::environment('local')) {
    Route::prefix('dev')->group(function () {
        Route::post('/users', [DevUserController::class, 'store']);       // POST /api/dev/users
        Route::post('/comments', [DevCommentController::class, 'store']); // POST /api/dev/comments
    });
}

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








