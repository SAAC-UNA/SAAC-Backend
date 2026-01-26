<?php

use Illuminate\Support\Facades\Route;

// Importante importa el controlador
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\DimensionController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\EvidenceAssignmentController;
use App\Http\Controllers\ExtensionRequestController;
use App\Http\Controllers\ExtensionTimeRequestController; // RF-15: Controller del profesor
use App\Http\Controllers\EvidenceStateController;
use App\Http\Controllers\StandardController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ActionTypeController;
use App\Http\Controllers\ImprovementCommitmentController;
use App\Http\Controllers\CriterionApprovalController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;

//solo para pruebas
use Illuminate\Support\Facades\App;
use App\Http\Controllers\DevUserController;
use App\Http\Controllers\DevCommentController;
use Illuminate\Http\Request;
use App\Models\Process;
use App\Models\AccreditationCycle;

// ============================================
// Rutas de Autenticación (públicas)
// ============================================
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);      // POST /api/auth/login
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');                             // POST /api/auth/logout
    Route::get('/me', [AuthController::class, 'me'])
        ->middleware('auth:sanctum');                             // GET /api/auth/me
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
Route::apiResource('estructura/carreras', CareerController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/carreras/{id}/active', [CareerController::class, 'setActive']);
Route::apiResource('estructura/dimensiones', DimensionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/dimensiones/{id}/active', [DimensionController::class, 'setActive']);
Route::apiResource('estructura/componentes', ComponentController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/componentes/{id}/active', [ComponentController::class, 'setActive']);
Route::apiResource('estructura/criterios', CriterionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/criterios/{id}/active', [CriterionController::class, 'setActive']);
// HU-012: Filtrado avanzado de evidencias (DEBE ir ANTES de apiResource)
Route::get('estructura/evidencias/filter', [EvidenceController::class, 'filter'])->middleware('auth:sanctum');
Route::get('estructura/evidencias/export/excel', [EvidenceController::class, 'exportExcel'])->middleware('auth:sanctum');
Route::get('estructura/evidencias/export/pdf', [EvidenceController::class, 'exportPDF'])->middleware('auth:sanctum');
Route::apiResource('estructura/evidencias', EvidenceController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::patch('estructura/evidencias/{id}/active', [EvidenceController::class, 'setActive']);

// Rutas para asignaciones de evidencias (HU-007)
Route::post('evidencias-asignaciones/validar-duplicados', [EvidenceAssignmentController::class, 'validateDuplicates'])->middleware('auth:sanctum');
Route::apiResource('evidencias-asignaciones', EvidenceAssignmentController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::get('usuarios/{usuarioId}/evidencias-asignadas', [EvidenceAssignmentController::class, 'getByUser']);
Route::get('evidencias/{evidenciaId}/asignaciones', [EvidenceAssignmentController::class, 'getByEvidence']);
Route::get('procesos/{procesoId}/asignaciones', [EvidenceAssignmentController::class, 'getByProcess']);

// Rutas para solicitudes de ampliación (HU-016 - ENCARGADO)
Route::prefix('solicitudes-ampliacion')->group(function () {
    Route::get('/', [ExtensionRequestController::class, 'index']);                    // GET /api/solicitudes-ampliacion
    Route::get('/pendientes', [ExtensionRequestController::class, 'pending']);        // GET /api/solicitudes-ampliacion/pendientes
    Route::get('/mis-solicitudes', [ExtensionRequestController::class, 'mySolicitudes']); // GET /api/solicitudes-ampliacion/mis-solicitudes
    Route::get('/{id}', [ExtensionRequestController::class, 'show']);                 // GET /api/solicitudes-ampliacion/{id}
    Route::post('/', [ExtensionRequestController::class, 'store']);                   // POST /api/solicitudes-ampliacion
    Route::post('/{id}/aprobar', [ExtensionRequestController::class, 'approve']);     // POST /api/solicitudes-ampliacion/{id}/aprobar
    Route::post('/{id}/rechazar', [ExtensionRequestController::class, 'reject']);     // POST /api/solicitudes-ampliacion/{id}/rechazar
});

// Rutas para solicitudes de ampliación de tiempo del PROFESOR (RF-15)
// AUTENTICACIÓN: Requiere usuario autenticado con token Sanctum
// ============================================================================
// RF-15: SOLICITUDES DE AMPLIACIÓN DE TIEMPO (PROFESORES)
// ============================================================================
// ESTÁNDAR PL-10: Autenticación + Autorización + Rate Limiting
// - Middleware: auth:sanctum (autenticación)
// - Policies: ExtensionTimeRequestPolicy (autorización granular)
// - Rate Limiting: 60 peticiones/minuto (previene abuso)
// - Validación: FormRequests con sanitización
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])
    ->prefix('solicitudes-ampliacion-tiempo')
    ->group(function () {
        // GET: Listar solicitudes (profesores ven solo las suyas, encargados ven todas)
        Route::get('/', [ExtensionTimeRequestController::class, 'index']);
        
        // GET: Evidencias próximas a vencer (para sugerir en formulario)
        Route::get('/evidencias/proximas-vencer', [ExtensionTimeRequestController::class, 'upcomingEvidences']);
        
        // GET: Ver detalle de solicitud (autorización con Policy)
        Route::get('/{id}', [ExtensionTimeRequestController::class, 'show']);
        
        // POST: Crear solicitud (rate limit más estricto para evitar spam)
        Route::post('/', [ExtensionTimeRequestController::class, 'store'])
            ->middleware('throttle:10,1'); // Max 10 creaciones por minuto
        
        // PUT: Actualizar solicitud pendiente
        Route::put('/{id}', [ExtensionTimeRequestController::class, 'update']);
        
        // DELETE: Eliminar solicitud pendiente
        Route::delete('/{id}', [ExtensionTimeRequestController::class, 'destroy']);
    });

Route::apiResource('estructura/estados-evidencia', EvidenceStateController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::apiResource('estructura/estandares', StandardController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::patch('estructura/estandares/{id}/active', [StandardController::class, 'setActive']);

// Rutas para aprobación de criterios por bloques (HU-010)
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])->group(function () {
    Route::get('aprobaciones-criterios', [CriterionApprovalController::class, 'listApprovals']);
    Route::get('aprobaciones-criterios/{approvalId}', [CriterionApprovalController::class, 'showApproval']);
    Route::post('criterios/{criterioId}/aprobar', [CriterionApprovalController::class, 'approveCriterion'])->middleware('throttle:10,1');
    Route::post('criterios/{criterioId}/rechazar', [CriterionApprovalController::class, 'rejectCriterion'])->middleware('throttle:10,1');
});

// Rutas para archivos (HU-008 - Subida de Evidencias)
Route::prefix('archivos')->group(function () {
    // TEMPORAL: Obtener datos de prueba para formulario
    Route::get('/test-data', [FileController::class, 'getTestData']);
       
    // Listar archivos por evidencia o proceso
    Route::get('/', [FileController::class, 'index']); // ?evidencia_id={id} o ?proceso_id={id}
    
    // Subir nuevo archivo (máximo 10 uploads por minuto)
    Route::post('/', [FileController::class, 'store'])->middleware('throttle:10,1');
    
    // Ver metadatos de un archivo
    Route::get('/{archivo}', [FileController::class, 'show']);
    
    // Eliminar archivo
    Route::delete('/{archivo}', [FileController::class, 'destroy']);
    
    // Hacer público un archivo (generar enlace público)
    Route::post('/{archivo}/make-public', [FileController::class, 'makePublic']);
    
    // Revocar acceso público
    Route::post('/{archivo}/revoke-public', [FileController::class, 'revokePublic']);
    
    // Operación masiva: hacer públicos múltiples archivos
    Route::post('/bulk-make-public', [FileController::class, 'bulkMakePublic']);
});

// Ruta pública para acceso mediante token (SIN autenticación - para SINAES/informes)
Route::get('/p/{token}', [FileController::class, 'publicAccess'])->withoutMiddleware(['auth:sanctum']);


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

// Rutas de Notificaciones (HU-018) - Requieren autenticación
Route::middleware(['auth:sanctum'])->prefix('notificaciones')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);                           // GET /api/notificaciones
    Route::get('/no-leidas/contador', [NotificationController::class, 'getUnreadCount']); // GET /api/notificaciones/no-leidas/contador
    Route::post('/marcar-todas-leidas', [NotificationController::class, 'markAllAsRead']); // POST /api/notificaciones/marcar-todas-leidas
    Route::post('/{id}/marcar-leida', [NotificationController::class, 'markAsRead']);      // POST /api/notificaciones/{id}/marcar-leida
    Route::delete('/{id}', [NotificationController::class, 'destroy']);                   // DELETE /api/notificaciones/{id}
});

// Rutas de Bitácora del Sistema (HU-005) - Solo Superusuario
Route::prefix('bitacora')->middleware(['role:Superusuario'])->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/modulos', [AuditLogController::class, 'getModules']);
    Route::get('/tipos-accion', [ActionTypeController::class, 'index']);
    Route::get('/export', [AuditLogController::class, 'export']);
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

Route::prefix('compromisos-de-mejora')->group(function () {
    Route::get('/', [ImprovementCommitmentController::class, 'listCommitments'])->name('commitments.index');
    Route::get('/usuario/{usuarioId}', [ImprovementCommitmentController::class, 'getByUser'])->name('commitments.by-user');
    Route::get('/evidencia/{evidenciaId}', [ImprovementCommitmentController::class, 'getByEvidence'])->name('commitments.by-evidence');
    Route::post('/', [ImprovementCommitmentController::class, 'createCommitment'])->name('commitments.create');
    Route::get('/{id}', [ImprovementCommitmentController::class, 'showCommitment'])->name('commitments.show');
    Route::put('/{id}', [ImprovementCommitmentController::class, 'updateCommitment'])->name('commitments.update');
    Route::patch('/{id}/active', [ImprovementCommitmentController::class, 'setActive'])->name('commitments.set-active');
});

// Devuelve procesos con sus ciclos, sedes y carreras asociadas (datos simulados para pruebas sin autenticación).
Route::get('estructura/procesos', function () {
    return Process::with('accreditationCycle.careerCampus.career', 'accreditationCycle.careerCampus.campus')->get();
});
//  Ciclos filtrados automáticamente (solo los de la carrera del usuario simulado)
Route::get('estructura/ciclos-acreditacion', function () {
    return AccreditationCycle::with('careerCampus.career', 'careerCampus.campus')->get();
});
















































