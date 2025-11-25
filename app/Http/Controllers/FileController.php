<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Http\Resources\FileResource;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FileController extends Controller
{
    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Listar archivos de una evidencia específica.
     * GET /api/archivos?evidencia_id={id}
     * GET /api/archivos?proceso_id={id}
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'evidencia_id' => 'sometimes|integer|exists:EVIDENCIA,evidencia_id',
            'proceso_id' => 'sometimes|integer|exists:PROCESO,proceso_id',
        ]);

        $query = File::query()->with(['evidence', 'user', 'process']);

        // Filtrar por evidencia si se proporciona
        if ($request->has('evidencia_id')) {
            $evidenciaId = $request->input('evidencia_id');
            
            // Verificar autorización para ver archivos de esta evidencia
            Gate::authorize('viewAny', [File::class, $evidenciaId]);
            
            $query->where('evidencia_id', $evidenciaId);
        }

        // Filtrar por proceso si se proporciona
        if ($request->has('proceso_id')) {
            $query->where('proceso_id', $request->input('proceso_id'));
        }

        $archivos = $query->orderBy('fecha_subida', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => FileResource::collection($archivos),
        ]);
    }

    /**
     * Subir uno o más archivos (máximo 5).
     * POST /api/archivos
     * 
     * Body (multipart/form-data):
     * - archivos: file[] (1-5 archivos, max 50MB cada uno)
     * - evidencia_id: integer
     * - proceso_id: integer
     */
    public function store(StoreFileRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Obtener el usuario autenticado
        $usuarioId = auth()->id();
        
        if (!$usuarioId) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        // Procesar todos los archivos
        $archivos = [];
        $errores = [];
        
        foreach ($request->file('archivos', []) as $index => $archivo) {
            try {
                $archivoGuardado = $this->fileService->uploadFile(
                    file: $archivo,
                    evidenciaId: $validated['evidencia_id'],
                    usuarioId: $usuarioId,
                    procesoId: $validated['proceso_id']
                );
                
                $archivoGuardado->load(['evidence', 'user', 'process']);
                $archivos[] = new FileResource($archivoGuardado);
            } catch (\Exception $e) {
                $errores[] = [
                    'indice' => $index,
                    'nombre' => $archivo->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Si hay errores, retornar con status 207 (Multi-Status)
        if (!empty($errores)) {
            return response()->json([
                'success' => count($archivos) > 0,
                'message' => count($archivos) . ' archivo(s) subido(s), ' . count($errores) . ' error(es).',
                'data' => $archivos,
                'errores' => $errores,
            ], 207);
        }

        // Éxito total
        return response()->json([
            'success' => true,
            'message' => count($archivos) . ' archivo(s) subido(s) exitosamente.',
            'data' => $archivos,
            'count' => count($archivos),
        ], 201);
    }

    /**
     * Mostrar metadatos de un archivo específico.
     * GET /api/archivos/{archivo}
     */
    public function show(File $archivo): JsonResponse
    {
        Gate::authorize('view', $archivo);

        $archivo->load(['evidence', 'user', 'process']);

        return response()->json([
            'success' => true,
            'data' => new FileResource($archivo),
        ]);
    }

    /**
     * Eliminar un archivo.
     * DELETE /api/archivos/{archivo}
     */
    public function destroy(File $archivo): JsonResponse
    {
        Gate::authorize('delete', $archivo);

        $this->fileService->deleteFile($archivo);

        return response()->json([
            'success' => true,
            'message' => 'Archivo eliminado exitosamente.',
        ]);
    }

    /**
     * Hacer público un archivo (generar enlace público).
     * POST /api/archivos/{archivo}/make-public
     * 
     * Body (opcional):
     * - expires_at: fecha de expiración (default: 1 año)
     */
    public function makePublic(File $archivo, Request $request): JsonResponse
    {
        Gate::authorize('makePublic', $archivo);

        $validated = $request->validate([
            'expires_at' => 'sometimes|date|after:now',
        ]);

        $expiresAt = isset($validated['expires_at']) 
            ? new \DateTime($validated['expires_at']) 
            : null;

        $this->fileService->makePublic($archivo, $expiresAt);

        $archivo->load(['evidence', 'user', 'process']);

        return response()->json([
            'success' => true,
            'message' => 'Archivo marcado como público exitosamente.',
            'data' => new FileResource($archivo),
        ]);
    }

    /**
     * Revocar acceso público de un archivo.
     * POST /api/archivos/{archivo}/revoke-public
     */
    public function revokePublic(File $archivo): JsonResponse
    {
        Gate::authorize('revokePublicAccess', $archivo);

        $this->fileService->revokePublicAccess($archivo);

        $archivo->load(['evidence', 'user', 'process']);

        return response()->json([
            'success' => true,
            'message' => 'Acceso público revocado exitosamente.',
            'data' => new FileResource($archivo),
        ]);
    }

    /**
     * Hacer públicos múltiples archivos masivamente.
     * POST /api/archivos/bulk-make-public
     * 
     * Body:
     * - archivos_ids: array de IDs
     * - expires_at: fecha de expiración (opcional)
     */
    public function bulkMakePublic(Request $request): JsonResponse
    {
        Gate::authorize('bulkMakePublic', File::class);

        $validated = $request->validate([
            'archivos_ids' => 'required|array|min:1',
            'archivos_ids.*' => 'integer|exists:ARCHIVO,archivo_id',
            'expires_at' => 'sometimes|date|after:now',
        ], [
            'archivos_ids.required' => 'Debe proporcionar al menos un ID de archivo.',
            'archivos_ids.*.exists' => 'Uno o más IDs de archivo no existen.',
        ]);

        $expiresAt = isset($validated['expires_at']) 
            ? new \DateTime($validated['expires_at']) 
            : null;

        $archivos = $this->fileService->bulkMakePublic(
            $validated['archivos_ids'],
            $expiresAt
        );

        return response()->json([
            'success' => true,
            'message' => 'Archivos marcados como públicos exitosamente.',
            'data' => FileResource::collection($archivos),
            'count' => count($archivos),
        ]);
    }

    // =================================================================
    // MÉTODOS PARA SERVING DE ARCHIVOS (A IMPLEMENTAR EN EL FUTURO)
    // =================================================================
    
    /**
     * Descargar archivo (autenticado).
     * GET /api/archivos/{archivo}/download
     * 
     * NOTA: A implementar cuando se programe el serving de archivos.
     */
    // public function download(File $archivo): StreamedResponse
    // {
    //     Gate::authorize('download', $archivo);
    //     // Implementar lógica de descarga con Storage::download()
    // }

    /**
     * Ver archivo inline (autenticado).
     * GET /api/archivos/{archivo}/view
     * 
     * NOTA: A implementar cuando se programe el serving de archivos.
     */
    // public function view(File $archivo): StreamedResponse
    // {
    //     Gate::authorize('download', $archivo);
    //     // Implementar lógica de visualización con Storage::response()
    // }

    /**
     * Acceso público a archivo mediante token.
     * GET /api/p/{token}
     * 
     * NOTA: A implementar cuando se programe el serving de archivos.
     * Esta ruta NO requiere autenticación (para SINAES).
     */
    // public function publicAccess(string $token): StreamedResponse
    // {
    //     // Buscar archivo por token_publico
    //     // Validar is_publico y link_expira_en
    //     // Servir archivo con Storage::response()
    // }

    // =================================================================
    // MÉTODOS TEMPORALES PARA PRUEBAS (REMOVER EN PRODUCCIÓN)
    // =================================================================

    /**
     * Obtener datos para los selectores del formulario de prueba.
     * GET /api/archivos/test-data
     */
    public function getTestData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'usuarios' => \App\Models\User::select('usuario_id', 'nombre', 'email')
                    ->limit(20)
                    ->get(),
                'evidencias' => \App\Models\Evidence::select('evidencia_id', 'descripcion', 'nomenclatura')
                    ->limit(20)
                    ->get(),
                'procesos' => \App\Models\Process::with('accreditationCycle')
                    ->limit(20)
                    ->get()
                    ->map(fn($proceso) => [
                        'proceso_id' => $proceso->proceso_id,
                        'tipo_proceso' => $proceso->tipo_proceso,
                        'ciclo' => $proceso->accreditationCycle?->año ?? 'N/A',
                    ]),
            ],
        ]);
    }
}
