<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Http\Requests\StoreElementFileRequest;
use App\Http\Resources\ElementFileResource;
use App\Services\FlexibleFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Controller exclusivo para archivos del Modelo Flexible (HU-008 / Arquitectura B - Flujo 2).
 *
 * Responsabilidad única: gestionar la subida, listado, descarga y acceso público
 * de archivos cuya referencia es un ELEMENTO (modelo flexible SINAES 2026).
 *
 * No reutiliza FileController ni FileResource — cumple SOLID:
 * - FlexibleFileService inyectado directamente sin necesidad de Factory.
 * - ElementFileResource expone metadatos propios del modelo flexible
 *   (autor con rol, descripción del elemento, sin evidencia_id).
 */
class ElementFileController extends Controller
{
    protected FlexibleFileService $service;

    public function __construct(FlexibleFileService $service)
    {
        $this->service = $service;
    }

    /**
     * Listar archivos de un elemento específico.
     * GET /api/elementos-archivos?elemento_id={id}
     * GET /api/elementos-archivos?elemento_id={id}&proceso_id={id}
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'elemento_id' => 'required|integer|exists:ELEMENTO,elemento_id',
            'proceso_id'  => 'sometimes|integer|exists:PROCESO,proceso_id',
            'usuario_id'  => 'sometimes|integer|exists:USUARIO,usuario_id',
        ]);

        $query = File::query()
            ->with(['elemento', 'user.roles', 'process'])
            ->where('elemento_id', $request->input('elemento_id'));

        if ($request->has('proceso_id')) {
            $query->where('proceso_id', $request->input('proceso_id'));
        }

        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->input('usuario_id'));
        }

        $archivos = $query->orderBy('fecha_subida', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => ElementFileResource::collection($archivos),
            'count'   => $archivos->count(),
        ]);
    }

    /**
     * Subir archivos o guardar enlaces asociados a un elemento.
     * POST /api/elementos-archivos
     *
     * Body (multipart/form-data o JSON):
     * - tipo: archivo|enlace
     * - archivos[]: archivos físicos (si tipo=archivo, máx 5, 50 MB c/u)
     * - enlaces[]: URLs (si tipo=enlace, máx 5)
     * - enlaces_nombres[]: nombres descriptivos opcionales para cada URL
     * - elemento_id: integer
     * - proceso_id: integer
     */
    public function store(StoreElementFileRequest $request): JsonResponse
    {
        $validated  = $request->validated();
        $usuarioId  = Auth::id();
        $elementoId = $validated['elemento_id'];
        $procesoId  = $validated['proceso_id'];

        $archivos = [];
        $errores  = [];

        if ($validated['tipo'] === 'archivo') {
            foreach ($request->file('archivos', []) as $index => $archivo) {
                try {
                    $guardado = $this->service->uploadFile(
                        file:         $archivo,
                        usuarioId:    $usuarioId,
                        procesoId:    $procesoId,
                        referenciaId: $elementoId,
                    );

                    $guardado->load(['elemento', 'user.roles', 'process']);
                    $archivos[] = new ElementFileResource($guardado);
                } catch (\Exception $e) {
                    $errores[] = [
                        'indice' => $index,
                        'nombre' => $archivo->getClientOriginalName(),
                        'error'  => $e->getMessage(),
                    ];
                }
            }
        } else {
            $enlaces = $validated['enlaces']         ?? [];
            $nombres = $validated['enlaces_nombres'] ?? [];

            foreach ($enlaces as $index => $url) {
                try {
                    $guardado = $this->service->saveLink(
                        url:               $url,
                        usuarioId:         $usuarioId,
                        procesoId:         $procesoId,
                        referenciaId:      $elementoId,
                        nombreDescriptivo: $nombres[$index] ?? null,
                    );

                    $guardado->load(['elemento', 'user.roles', 'process']);
                    $archivos[] = new ElementFileResource($guardado);
                } catch (\Exception $e) {
                    $errores[] = [
                        'indice' => $index,
                        'url'    => $url,
                        'error'  => $e->getMessage(),
                    ];
                }
            }
        }

        if (!empty($errores)) {
            return response()->json([
                'success' => count($archivos) > 0,
                'message' => count($archivos) . ' archivo(s) subido(s), ' . count($errores) . ' error(es).',
                'data'    => $archivos,
                'errores' => $errores,
            ], 207);
        }

        return response()->json([
            'success' => true,
            'message' => count($archivos) . ' archivo(s) subido(s) exitosamente.',
            'data'    => $archivos,
            'count'   => count($archivos),
        ], 201);
    }

    /**
     * Mostrar metadatos de un archivo de elemento.
     * GET /api/elementos-archivos/{archivo}
     */
    public function show(File $archivo): JsonResponse
    {
        $archivo->load(['elemento', 'user.roles', 'process']);

        return response()->json([
            'success' => true,
            'data'    => new ElementFileResource($archivo),
        ]);
    }

    /**
     * Eliminar un archivo de elemento.
     * DELETE /api/elementos-archivos/{archivo}
     */
    public function destroy(File $archivo): JsonResponse
    {
        $this->service->deleteFile($archivo);

        return response()->json([
            'success' => true,
            'message' => 'Archivo eliminado exitosamente.',
        ]);
    }

    /**
     * Descargar archivo de elemento (autenticado).
     * GET /api/elementos-archivos/{archivo}/download
     */
    public function download(File $archivo): BinaryFileResponse|JsonResponse|RedirectResponse
    {
        if ($archivo->tipo === 'enlace') {
            return redirect()->away($archivo->url);
        }

        try {
            $path = Storage::disk($this->service->getDisk())->path($archivo->path);

            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado en el almacenamiento.',
                ], 404);
            }

            return response()->download($path, $archivo->nombre_original);

        } catch (\Exception $e) {
            Log::error('[Flexible] Error descargando archivo', [
                'archivo_id' => $archivo->archivo_id,
                'user_id'    => Auth::id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    /**
     * Hacer público un archivo de elemento (generar enlace público).
     * POST /api/elementos-archivos/{archivo}/make-public
     */
    public function makePublic(File $archivo, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expires_at' => 'sometimes|date|after:now',
        ]);

        $expiresAt = isset($validated['expires_at'])
            ? new \DateTime($validated['expires_at'])
            : null;

        $this->service->makePublic($archivo, $expiresAt);
        $archivo->load(['elemento', 'user.roles', 'process']);

        return response()->json([
            'success' => true,
            'message' => 'Archivo marcado como público exitosamente.',
            'data'    => new ElementFileResource($archivo),
        ]);
    }

    /**
     * Revocar acceso público de un archivo de elemento.
     * POST /api/elementos-archivos/{archivo}/revoke-public
     */
    public function revokePublic(File $archivo): JsonResponse
    {
        $this->service->revokePublicAccess($archivo);
        $archivo->load(['elemento', 'user.roles', 'process']);

        return response()->json([
            'success' => true,
            'message' => 'Acceso público revocado exitosamente.',
            'data'    => new ElementFileResource($archivo),
        ]);
    }
}
