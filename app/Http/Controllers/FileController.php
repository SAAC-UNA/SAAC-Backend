<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Http\Resources\FileResource;
use App\Services\FileStorageFactory;
use App\Events\MultipleFilesUploaded;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    protected FileStorageFactory $factory;

    public function __construct(FileStorageFactory $factory)
    {
        $this->factory = $factory;
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
            'proceso_id'   => 'sometimes|integer|exists:PROCESO,proceso_id',
            'usuario_id'   => 'sometimes|integer|exists:USUARIO,usuario_id',
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

        // Filtrar por usuario responsable cuando se requiere aislar archivos por asignación.
        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->input('usuario_id'));
        }

        $archivos = $query->orderBy('fecha_subida', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => FileResource::collection($archivos),
        ]);
    }

    /**
     * Subir archivos o guardar enlaces como evidencia (máximo 5).
     * POST /api/archivos
     * 
     * Body (JSON o multipart/form-data):
     * - tipo: string (archivo|enlace)
     * - archivos: file[] (si tipo=archivo, 1-5 archivos, max 50MB c/u)
     * - enlaces: string[] (si tipo=enlace, 1-5 URLs)
     * - enlaces_nombres: string[] (opcional, nombres descriptivos para cada URL)
     * - evidencia_id: integer
     * - proceso_id: integer
     */
    public function store(StoreFileRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Obtener el usuario autenticado
        $usuarioId = Auth::id();
        
        if (!$usuarioId) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $archivos = [];
        $errores = [];

        $evidenciaId = $validated['evidencia_id'];

        // Delega siempre al servicio tradicional
        try {
            $service = $this->factory->make($evidenciaId, null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // Procesar según el tipo
        if ($validated['tipo'] === 'archivo') {
            // Procesar archivos físicos
            foreach ($request->file('archivos', []) as $index => $archivo) {
                try {
                    $archivoGuardado = $service->uploadFile(
                        file: $archivo,
                        usuarioId: $usuarioId,
                        procesoId: $validated['proceso_id'],
                        referenciaId: $evidenciaId
                    );

                    $archivoGuardado->load(['evidence', 'user', 'process']);
                    $archivos[] = new FileResource($archivoGuardado);
                } catch (\Exception $e) {
                    $errores[] = [
                        'indice' => $index,
                        'nombre' => $archivo->getClientOriginalName(),
                        'error'  => $e->getMessage(),
                    ];
                }
            }
        } else {
            // Procesar enlaces/URLs
            $enlaces = $validated['enlaces'] ?? [];
            $nombres = $validated['enlaces_nombres'] ?? [];

            foreach ($enlaces as $index => $url) {
                try {
                    $nombreDescriptivo = $nombres[$index] ?? null;

                    $enlaceGuardado = $service->saveLink(
                        url: $url,
                        usuarioId: $usuarioId,
                        procesoId: $validated['proceso_id'],
                        referenciaId: $evidenciaId,
                        nombreDescriptivo: $nombreDescriptivo
                    );

                    $enlaceGuardado->load(['evidence', 'user', 'process']);
                    $archivos[] = new FileResource($enlaceGuardado);
                } catch (\Exception $e) {
                    $errores[] = [
                        'indice' => $index,
                        'url'    => $url,
                        'error'  => $e->getMessage(),
                    ];
                }
            }
        }

        // Si hay errores, retornar con status 207 (Multi-Status)
        if (!empty($errores)) {
            // Si se subieron algunos archivos, disparar evento
            if (count($archivos) >= 2) {
                $filesData = array_map(fn($resource) => [
                    'nombre_original' => $resource->nombre_original,
                    'size_kb' => round($resource->size / 1024, 2),
                ], $archivos);
                
                event(new MultipleFilesUploaded($filesData, $usuarioId, $evidenciaId, $validated['proceso_id']));
            }
            
            return response()->json([
                'success' => count($archivos) > 0,
                'message' => count($archivos) . ' archivo(s) subido(s), ' . count($errores) . ' error(es).',
                'data' => $archivos,
                'errores' => $errores,
            ], 207);
        }

        // Éxito total: Disparar evento si se subieron múltiples archivos (2+)
        if (count($archivos) >= 2) {
            $filesData = array_map(fn($resource) => [
                'archivo_id' => $resource->archivo_id,
                'nombre_original' => $resource->nombre_original,
                'size_kb' => round($resource->size / 1024, 2),
            ], $archivos);
            
            event(new MultipleFilesUploaded($filesData, $usuarioId, $evidenciaId, $validated['proceso_id']));
        }

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

        $this->factory->makeFromFile($archivo)->deleteFile($archivo);

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

        $this->factory->makeFromFile($archivo)->makePublic($archivo, $expiresAt);

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

        $this->factory->makeFromFile($archivo)->revokePublicAccess($archivo);

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

        // bulkMakePublic: los archivos pueden mezclar modelos, se resuelve por archivo
        $archivos = [];
        foreach ($validated['archivos_ids'] as $id) {
            $archivoItem = File::find($id);
            if ($archivoItem) {
                $archivos[] = $this->factory->makeFromFile($archivoItem)->makePublic($archivoItem, $expiresAt);
            }
        }

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
     */
    public function download(File $archivo): BinaryFileResponse|JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $archivo);

        $tipo = $archivo->tipo ?? 'archivo';
        
        // Si es un enlace (URL), redirigir
        if ($tipo === 'enlace') {
            return redirect()->away($archivo->url);
        }

        // Si es un archivo físico, descargarlo
        try {
            $disk = $this->factory->makeFromFile($archivo)->getDisk();
            $path = Storage::disk($disk)->path($archivo->path);
            
            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado en el almacenamiento.',
                ], 404);
            }

            return response()->download($path, $archivo->nombre_original);
            
        } catch (\Exception $e) {
            Log::error('Error descargando archivo', [
                'archivo_id' => $archivo->archivo_id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

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
     * Acceso público a archivo o enlace mediante token.
     * GET /api/p/{token}
     *
     * Esta ruta NO requiere autenticación (para SINAES/informes externos).
     *
     * NOTA: Este endpoint es COMPARTIDO entre el modelo tradicional y el modelo flexible.
     * Funciona para ambos porque consulta el campo `token_publico` de la tabla ARCHIVO
     * sin distinción de modelo. Los archivos del modelo flexible (ElementFileController)
     * también usan esta tabla, por lo que make-public/revoke-public de ambos modelos
     * generan tokens accesibles aquí.
     * Si en el futuro se elimina FileController, este método debe moverse a un
     * controlador base o a un SharedFileController dedicado.
     */
    public function publicAccess(string $token)
    {
        // Buscar archivo/enlace por token público
        $archivo = File::where('token_publico', $token)->first();

        if (!$archivo) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo no encontrado o enlace inválido.',
            ], 404);
        }

        // Validar que el archivo sea público
        if (!$archivo->is_publico) {
            return response()->json([
                'success' => false,
                'message' => 'Este archivo no es público.',
            ], 403);
        }

        // Validar que el enlace no haya expirado
        if ($archivo->hasExpiredLink()) {
            return response()->json([
                'success' => false,
                'message' => 'El enlace ha expirado.',
            ], 410);
        }

        $tipo = $archivo->tipo ?? 'archivo';
        
        // Si es un enlace (URL), redirigir
        if ($tipo === 'enlace') {
            return redirect()->away($archivo->url);
        }

        // Si es un archivo físico, descargarlo
        try {
            $disk = $this->factory->makeFromFile($archivo)->getDisk();
            $path = Storage::disk($disk)->path($archivo->path);
            
            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado en el almacenamiento.',
                ], 404);
            }

            return response()->download($path, $archivo->nombre_original);
            
        } catch (\Exception $e) {
            Log::error('Error sirviendo archivo público', [
                'token' => $token,
                'archivo_id' => $archivo->archivo_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo.',
            ], 500);
        }
    }

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
