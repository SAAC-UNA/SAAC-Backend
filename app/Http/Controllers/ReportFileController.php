<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportFileRequest;
use App\Http\Resources\ReportFileResource;
use App\Models\ReportFile;
use App\Services\ReportFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportFileController extends Controller
{
    public function __construct(
        private readonly ReportFileService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'proceso_id' => 'required|integer|exists:PROCESO,proceso_id',
            'usuario_id' => 'sometimes|integer|exists:USUARIO,usuario_id',
        ]);

        $query = ReportFile::query()
            ->with(['user.roles', 'process'])
            ->where('proceso_id', $request->integer('proceso_id'));

        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->integer('usuario_id'));
        }

        $archivos = $query->orderBy('fecha_subida', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => ReportFileResource::collection($archivos),
            'count' => $archivos->count(),
        ]);
    }

    public function store(StoreReportFileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $usuarioId = Auth::id();
        $procesoId = $validated['proceso_id'];

        $tipo = $validated['tipo'];

        $archivos = [];
        $errores = [];

        foreach ($request->file('archivos', []) as $index => $archivo) {
            try {
                $guardado = $this->service->uploadFile($archivo, $usuarioId, $procesoId, $tipo);
                $guardado->load(['user.roles', 'process']);
                $archivos[] = new ReportFileResource($guardado);
            } catch (\Exception $e) {
                $errores[] = [
                    'indice' => $index,
                    'nombre' => $archivo->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        $status = empty($errores) ? 201 : (empty($archivos) ? 422 : 207);

        return response()->json([
            'success' => !empty($archivos),
            'message' => empty($errores)
                ? 'Archivo(s) de informe procesados correctamente.'
                : 'Se procesaron algunos registros con errores.',
            'data' => $archivos,
            'errors' => $errores,
        ], $status);
    }

    public function show(ReportFile $archivoInforme): JsonResponse
    {
        $archivoInforme->load(['user.roles', 'process']);

        return response()->json([
            'success' => true,
            'data' => new ReportFileResource($archivoInforme),
        ]);
    }

    public function destroy(ReportFile $archivoInforme): JsonResponse
    {
        $this->service->deleteFile($archivoInforme);

        return response()->json([
            'success' => true,
            'message' => 'Archivo de informe eliminado correctamente.',
        ]);
    }

    public function download(ReportFile $archivoInforme): StreamedResponse|JsonResponse|RedirectResponse
    {
        if (($archivoInforme->tipo ?? 'archivo') === 'enlace') {
            return redirect()->away($archivoInforme->url);
        }

        if (!$archivoInforme->path || !Storage::disk($this->service->getDisk())->exists($archivoInforme->path)) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no existe en almacenamiento.',
            ], 404);
        }

        return Storage::disk($this->service->getDisk())->download(
            $archivoInforme->path,
            $archivoInforme->nombre_original
        );
    }

    public function makePublic(ReportFile $archivoInforme, Request $request): JsonResponse
    {
        $expiresAt = null;
        if ($request->filled('expires_at')) {
            $expiresAt = new \DateTime($request->input('expires_at'));
        }

        $updated = $this->service->makePublic($archivoInforme, $expiresAt);
        $updated->load(['user.roles', 'process']);

        return response()->json([
            'success' => true,
            'message' => 'Archivo de informe marcado como público.',
            'data' => new ReportFileResource($updated),
        ]);
    }

    public function revokePublic(ReportFile $archivoInforme): JsonResponse
    {
        $updated = $this->service->revokePublicAccess($archivoInforme);
        $updated->load(['user.roles', 'process']);

        return response()->json([
            'success' => true,
            'message' => 'Acceso público revocado correctamente.',
            'data' => new ReportFileResource($updated),
        ]);
    }

    public function publicAccess(string $token)
    {
        $archivo = ReportFile::publicAndValid()->where('token_publico', $token)->first();
        if (!$archivo) {
            return response()->json([
                'success' => false,
                'message' => 'Link público inválido o expirado.',
            ], 404);
        }

        if ($archivo->tipo === 'enlace') {
            return redirect()->away($archivo->url);
        }

        if (!$archivo->path || !Storage::disk($this->service->getDisk())->exists($archivo->path)) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no existe en almacenamiento.',
            ], 404);
        }

        return Storage::disk($this->service->getDisk())->download($archivo->path, $archivo->nombre_original);
    }
}
