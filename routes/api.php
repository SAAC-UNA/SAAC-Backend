<?php
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\ExtensionTimeRequestController;
use App\Http\Controllers\EvidenceStateController;
use App\Http\Controllers\StandardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
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
// Rutas de Autenticación
// ============================================
// Login es público (no requiere autenticación)
Route::post('auth/login', [AuthController::class, 'login']);

// Logout y Me requieren autenticación
Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
});

// ============================================
// Rutas de Estructura (protegidas)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {
    // Universidades
    Route::apiResource('estructura/universidades', UniversityController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('estructura/universidades/{id}/active', [UniversityController::class, 'setActive']);
    
    // Campuses
    Route::apiResource('estructura/campuses', CampusController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('estructura/campuses/{id}/active', [CampusController::class, 'setActive']);
    
    // Carreras
    Route::apiResource('estructura/carreras', CareerController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('estructura/carreras/{id}/active', [CareerController::class, 'setActive']);
    
    // Dimensiones
    Route::apiResource('estructura/dimensiones', DimensionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('estructura/dimensiones/{id}/active', [DimensionController::class, 'setActive']);
    
    // Componentes
    Route::apiResource('estructura/componentes', ComponentController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('estructura/componentes/{id}/active', [ComponentController::class, 'setActive']);
    
    // Criterios
    Route::apiResource('estructura/criterios', CriterionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('estructura/criterios/{id}/active', [CriterionController::class, 'setActive']);
    
    // Evidencias (HU-012: Filtrado avanzado DEBE ir ANTES de apiResource)
    Route::get('estructura/evidencias/filter', [EvidenceController::class, 'filter']);
    Route::get('estructura/evidencias/export/excel', [EvidenceController::class, 'exportExcel']);
    Route::get('estructura/evidencias/export/pdf', [EvidenceController::class, 'exportPDF']);
    Route::apiResource('estructura/evidencias', EvidenceController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('estructura/evidencias/{id}/active', [EvidenceController::class, 'setActive']);
    
    // Estados de evidencia
    Route::apiResource('estructura/estados-evidencia', EvidenceStateController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    
    // Estándares
    Route::apiResource('estructura/estandares', StandardController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::patch('estructura/estandares/{id}/active', [StandardController::class, 'setActive']);
    
    // Procesos y Ciclos
    Route::get('estructura/procesos', function () {
        return Process::with('accreditationCycle.careerCampus.career', 'accreditationCycle.careerCampus.campus')->get();
    });
    Route::get('estructura/ciclos-acreditacion', function () {
        return AccreditationCycle::with('careerCampus.career', 'careerCampus.campus')->get();
    });
});

// ============================================
// Rutas para Asignaciones de Evidencias (HU-007)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {
    Route::post('evidencias-asignaciones/validar-duplicados', [EvidenceAssignmentController::class, 'validateDuplicates']);
    Route::apiResource('evidencias-asignaciones', EvidenceAssignmentController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('usuarios/{usuarioId}/evidencias-asignadas', [EvidenceAssignmentController::class, 'getByUser']);
    Route::get('evidencias/{evidenciaId}/asignaciones', [EvidenceAssignmentController::class, 'getByEvidence']);
    Route::get('procesos/{procesoId}/asignaciones', [EvidenceAssignmentController::class, 'getByProcess']);
});

// ============================================
// Solicitudes de Ampliación (HU-016 - ENCARGADO)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->prefix('solicitudes-ampliacion')->group(function () {
    Route::get('/', [ExtensionRequestController::class, 'index']);
    Route::get('/pendientes', [ExtensionRequestController::class, 'pending']);
    Route::get('/mis-solicitudes', [ExtensionRequestController::class, 'mySolicitudes']);
    Route::get('/{id}', [ExtensionRequestController::class, 'show']);
    Route::post('/', [ExtensionRequestController::class, 'store']);
    Route::post('/{id}/aprobar', [ExtensionRequestController::class, 'approve']);
    Route::post('/{id}/rechazar', [ExtensionRequestController::class, 'reject']);
});

// ============================================
// Solicitudes de Ampliación de Tiempo (RF-15 - PROFESORES)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])
    ->prefix('solicitudes-ampliacion-tiempo')
    ->group(function () {
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

// ============================================
// Archivos (HU-008 - Subida de Evidencias)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->prefix('archivos')->group(function () {
    Route::get('/test-data', [FileController::class, 'getTestData']); // TEMPORAL
    Route::get('/', [FileController::class, 'index']); // ?evidencia_id={id} o ?proceso_id={id}
    Route::post('/', [FileController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/{archivo}', [FileController::class, 'show']);
    Route::get('/{archivo}/download', [FileController::class, 'download']);
    Route::delete('/{archivo}', [FileController::class, 'destroy']);
    Route::post('/{archivo}/make-public', [FileController::class, 'makePublic']);
    Route::post('/{archivo}/revoke-public', [FileController::class, 'revokePublic']);
    Route::post('/bulk-make-public', [FileController::class, 'bulkMakePublic']);
});

// Acceso público mediante token (SIN autenticación - para SINAES/informes)
Route::get('/p/{token}', [FileController::class, 'publicAccess']);

// ============================================
// Aprobación de Criterios por Bloques (HU-010)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session', 'throttle:60,1'])->group(function () {
    Route::get('aprobaciones-criterios', [CriterionApprovalController::class, 'listApprovals']);
    Route::get('aprobaciones-criterios/{approvalId}', [CriterionApprovalController::class, 'showApproval']);
    Route::post('criterios/{criterioId}/aprobar', [CriterionApprovalController::class, 'approveCriterion'])->middleware('throttle:10,1');
    Route::post('criterios/{criterioId}/rechazar', [CriterionApprovalController::class, 'rejectCriterion'])->middleware('throttle:10,1');
});

// ============================================
// Rutas de Gestión de Usuarios (HU-002)
// ============================================
// Protegidas con:
// - auth:sanctum: Requiere usuario autenticado con token válido
// - permission:usuarios.edit: Requiere permiso específico para editar usuarios
Route::prefix('admin/users')->middleware(['auth:sanctum', 'permission:usuarios.edit'])->group(function () {
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

// ============================================
// Gestión de Roles y Permisos
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {
    // Permisos
    Route::get('admin/permissions', [PermissionController::class, 'index']);
});

// ============================================
// Notificaciones (HU-018)
// ============================================
Route::middleware(['auth:sanctum', 'refresh.session'])->prefix('notificaciones')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/no-leidas/contador', [NotificationController::class, 'getUnreadCount']);
    Route::post('/marcar-todas-leidas', [NotificationController::class, 'markAllAsRead']);
    Route::post('/{id}/marcar-leida', [NotificationController::class, 'markAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});

// ============================================
// Bitácora del Sistema (HU-005) - Solo Superusuario
// ============================================
Route::prefix('bitacora')->middleware(['auth:sanctum', 'refresh.session', 'role:Superusuario'])->group(function () {
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
        'base'  => base_path(),
        'mark'  => 'X1'
    ]);
});


// Ruta de prueba sin controller

//Route::get('/estructura/ping2', fn() => response()->json(['ok' => true, 'scope' => 'ping2']));




Route::middleware([
    'auth:sanctum',
    'refresh.session',
    'role:Superusuario|Administrador',
])->prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'listRoles'])->name('roles.index');
    Route::post('/', [RoleController::class, 'createRole'])->name('roles.create');
    Route::get('/permisos', [RoleController::class, 'listPermissions'])->name('roles.permissions');
    Route::get('/{id}', [RoleController::class, 'showRole'])->name('roles.show');
    Route::put('/{id}', [RoleController::class, 'updateRole'])->name('roles.update');
    Route::delete('/{id}', [RoleController::class, 'deleteRole'])->name('roles.delete');


});

Route::middleware([
    'auth:sanctum',
    'refresh.session',
    'role:Superusuario|Administrador|Encargado de Acreditación',
])->group(function () {
    Route::prefix('compromisos-de-mejora')->group(function () {
        Route::get('/', [ImprovementCommitmentController::class, 'listCommitments'])->name('commitments.index');
        Route::get('/usuario/{usuarioId}', [ImprovementCommitmentController::class, 'getByUser'])->name('commitments.by-user');
        Route::get('/evidencia/{evidenciaId}', [ImprovementCommitmentController::class, 'getByEvidence'])->name('commitments.by-evidence');
        Route::post('/', [ImprovementCommitmentController::class, 'createCommitment'])->name('commitments.create');
        Route::get('/{id}', [ImprovementCommitmentController::class, 'showCommitment'])->name('commitments.show');
        Route::put('/{id}', [ImprovementCommitmentController::class, 'updateCommitment'])->name('commitments.update');
        Route::patch('/{id}/active', [ImprovementCommitmentController::class, 'setActive'])->name('commitments.set-active');
    });

    // Alias de compatibilidad (docs/colecciones Postman viejas): /api/compromisos-mejora
    Route::prefix('compromisos-mejora')->group(function () {
        Route::get('/', [ImprovementCommitmentController::class, 'listCommitments']);
        Route::get('/usuario/{usuarioId}', [ImprovementCommitmentController::class, 'getByUser']);
        Route::get('/evidencia/{evidenciaId}', [ImprovementCommitmentController::class, 'getByEvidence']);
        Route::post('/', [ImprovementCommitmentController::class, 'createCommitment']);
        Route::get('/{id}', [ImprovementCommitmentController::class, 'showCommitment']);
        Route::put('/{id}', [ImprovementCommitmentController::class, 'updateCommitment']);
        Route::patch('/{id}/active', [ImprovementCommitmentController::class, 'setActive']);
    });
});
















































