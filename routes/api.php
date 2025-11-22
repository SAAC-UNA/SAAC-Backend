<?php

use Illuminate\Support\Facades\Route;

// Importante importa el controlador
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\DimensionController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\EvidenceAssignmentController;
use App\Http\Controllers\EvidenceStateController;
use App\Http\Controllers\StandardController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AuditLogController;

//solo para pruebas
use Illuminate\Support\Facades\App;
use App\Http\Controllers\DevUserController;
use App\Http\Controllers\DevCommentController;
use Illuminate\Http\Request;
use App\Models\Process;
use App\Models\AccreditationCycle;

/**
 * Rutas de Autenticación (públicas)
 */
Route::middleware('throttle:5,1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
});

/**
 * Rutas de Autenticación (protegidas)
 */
Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
});

// CRUD completo de cada endpoint
//Route::apiResource('estructura/universidades', UniversityController::class)->parameters(['universidades' => 'universidad'])->only(['index','store','show','update','destroy']);
Route::apiResource('estructura/universidades', UniversityController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/universidades/{id}/active', [UniversityController::class, 'setActive']);
Route::apiResource('estructura/campuses', CampusController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/campuses/{id}/active', [CampusController::class, 'setActive']);
Route::apiResource('estructura/facultades', FacultyController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/facultades/{id}/active', [FacultyController::class, 'setActive']);
Route::apiResource('estructura/carreras', CareerController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/carreras/{id}/active', [CareerController::class, 'setActive']);
Route::apiResource('estructura/dimensiones', DimensionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/dimensiones/{id}/active', [DimensionController::class, 'setActive']);
Route::apiResource('estructura/componentes', ComponentController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/componentes/{id}/active', [ComponentController::class, 'setActive']);
Route::apiResource('estructura/criterios', CriterionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/criterios/{id}/active', [CriterionController::class, 'setActive']);
Route::apiResource('estructura/evidencias', EvidenceController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/evidencias/{id}/active', [EvidenceController::class, 'setActive']);

// Rutas para asignaciones de evidencias (HU-007)
Route::apiResource('evidencias-asignaciones', EvidenceAssignmentController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::get('usuarios/{usuarioId}/evidencias-asignadas', [EvidenceAssignmentController::class, 'getByUser']);
Route::get('evidencias/{evidenciaId}/asignaciones', [EvidenceAssignmentController::class, 'getByEvidence']);
Route::get('procesos/{procesoId}/asignaciones', [EvidenceAssignmentController::class, 'getByProcess']);

Route::apiResource('estructura/estados-evidencia', EvidenceStateController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::apiResource('estructura/estandares', StandardController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::patch('estructura/estandares/{id}/active', [StandardController::class, 'setActive']);

Route::prefix('admin/users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    // Activa un usuario cambiando su estado a "active"
    // Ejemplo: Patch/api/admin/users/5/activate
    Route::patch('{user}/activate',   [UserController::class, 'activate'])
        ->missing(fn (Request $request) => response()->json(['error' => 'Usuario no encontrado'], 404));
    //Desactiva un usuario cambiando su estado a "inactive"
    // Ejemplo: Patch/api/admin/users/5/deactivate
    Route::patch('{user}/deactivate', [UserController::class, 'deactivate'])
        ->missing(fn (Request $request) => response()->json(['error' => 'Usuario no encontrado'], 404));
    Route::put('{user}/role', [UserController::class, 'assignRole'])
         ->missing(fn (Request $request) => response()->json(['error' => 'Usuario no encontrado'], 404));
    Route::put('{user}/permissions', [UserController::class, 'assignPermissions'])
        ->missing(fn (Request $r) => response()->json(['error' => 'Usuario no encontrado'], 404));
});

// Para vista de permisos
Route::get('admin/permissions', [PermissionController::class, 'index']);

// Rutas de Bitácora del Sistema (HU-005) - Solo Superusuario
Route::prefix('bitacora')->middleware(['role:Superusuario'])->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/{auditLog}', [AuditLogController::class, 'show']);
});

// Ejemplos de uso cuando actives autenticación en Sprint 3:
// Route::middleware('can:evidencias.view')->get('/evidencias', [EvidenceController::class, 'index']);
// Route::middleware('can:reportes.generate')->get('/reportes/generar', [ReportController::class, 'generate']);



// Solo para pruebas
if (App::environment('local')) {
    Route::prefix('dev')->group(function () {
        Route::post('/users', [DevUserController::class, 'store']);       // POST /api/dev/users
        Route::post('/comments', [DevCommentController::class, 'store']); // POST /api/dev/comments
        
        // Autenticación temporal para pruebas de middleware
        Route::post('/login', [\App\Http\Controllers\DevAuthController::class, 'login']);
        Route::post('/logout', [\App\Http\Controllers\DevAuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/me', [\App\Http\Controllers\DevAuthController::class, 'me'])->middleware('auth:sanctum');
        
        // Ver bitácora sin autenticación (SOLO PARA PRUEBAS)
        Route::get('/bitacora', function () {
            try {
                $logs = \DB::table('BITACORA')
                    ->join('TIPO_ACCION', 'BITACORA.tipo_accion_id', '=', 'TIPO_ACCION.tipo_accion_id')
                    ->leftJoin('USUARIO', 'BITACORA.usuario_id', '=', 'USUARIO.usuario_id')
                    ->select(
                        'BITACORA.bitacora_id',
                        'USUARIO.nombre as usuario',
                        'TIPO_ACCION.descripcion as accion',
                        'BITACORA.modulo',
                        'BITACORA.detalle',
                        'BITACORA.fecha_hora'
                    )
                    ->orderBy('BITACORA.fecha_hora', 'desc')
                    ->limit(10)
                    ->get();
                
                return response()->json($logs);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
        });
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

//Route::get('/estructura/ping2', fn() => response()->json(['ok' => true, 'scope' => 'ping2']));

Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'listRoles'])->name('roles.index');
    Route::post('/', [RoleController::class, 'createRole'])->name('roles.create');
    Route::get('/permisos', [RoleController::class, 'listPermissions'])->name('roles.permissions');
    Route::get('/{id}', [RoleController::class, 'showRole'])->name('roles.show');
    Route::put('/{id}', [RoleController::class, 'updateRole'])->name('roles.update');
    Route::delete('/{id}', [RoleController::class, 'deleteRole'])->name('roles.delete');

    
});
// Devuelve procesos con sus ciclos, sedes y carreras asociadas (datos simulados para pruebas sin autenticación).
Route::get('estructura/procesos', function () {
    return Process::with('accreditationCycle.careerCampus.career', 'accreditationCycle.careerCampus.campus')->get();
});
//  Ciclos filtrados automáticamente (solo los de la carrera del usuario simulado)
Route::get('estructura/ciclos-acreditacion', function () {
    return AccreditationCycle::with('careerCampus.career', 'careerCampus.campus')->get();
});
















































