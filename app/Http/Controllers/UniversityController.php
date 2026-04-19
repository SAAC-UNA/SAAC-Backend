<?php

namespace App\Http\Controllers;

use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Services\UniversityService;
use App\Http\Requests\UniversityRequest;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;


class UniversityController extends Controller
{
    protected $service;

    public function __construct(UniversityService $service)
    {
        $this->service = $service;
    }
    /**
     * GET /api/universidades
     */
    public function index()
    {
        $items = $this->service->getAll();
        return response()->json($items, 200);
    }

    /**
     * GET /api/universidades/{id}
     */
    public function show($id)
    {
        $university = $this->service->findById($id);

        if (!$university) {
            return response()->json(['message' => 'Universidad no encontrada.'], 404);
        }
        return response()->json($university, 200);
    }

    /**
     * POST /api/universidades
     * Body JSON: { "nombre": "UNA" }
     */
    public function store(UniversityRequest $request)
    {
        $university = $this->service->create($request->validated());
        //Metodo para registrar en el log de auditoria
        AuditLogService::log(
'crear',
    "Universidad creada: {$university->nombre} (ID: {$university->universidad_id})",
    'Universidad'
        );

        return response()
            ->json([
                'message' => 'Universidad creada correctamente.',
                'data'    => $university
            ], 201)
            ->header('Location', route('universidades.show', $university->universidad_id));
    }

    /**
     * PUT/PATCH /api/universidades/{id}
     * Body JSON: { "nombre": "UCR" }
     */
    public function update(UniversityRequest $request, $id)
    {
        $university = University::find($id);
        if (!$university) {
            return response()->json(['message' => 'Universidad no encontrada.'], 404);
        }
        // Guardar estado original (solo si querés saber qué cambió)
         $oldName = $university->nombre;
         
        // Datos ya validados por el FormRequest (incluye la regla unique con ignore)
        $updated = $this->service->update($university, $request->validated());

        // Registrar en bitácora SOLO si hubo cambios reales
        if ($oldName !== $updated->nombre) {
            AuditLogService::log(
                'editar',
                "Universidad actualizada: {$oldName} → {$updated->nombre} (ID: {$updated->universidad_id})",
                'Universidad'
            );
        }

        return response()->json([
            'message' => 'Universidad actualizada correctamente.',
            'data'    => $updated
        ], 200);
    }

        /**
         * PATCH /api/universidades/{id}/active
         * Body JSON: { "active": true }
         */
        public function setActive(Request $request, $id)
        {
            $university = University::with(['campuses'])->find($id);
            if (!$university) {
                return response()->json(['message' => 'Universidad no encontrada.'], 404);
            }

            $validated = $request->validate([
                'active' => ['required', 'boolean'],
            ]);

            $newActiveState = $validated['active'];
            $previousState  = $university->activo; // Estado antes del cambio

            // Actualizar el estado de la universidad
            $university->activo = $newActiveState;
            $university->save();

            // Aplicar cambio en cascada a todos los elementos hijos
            // Procesar campus hijos
            foreach ($university->campuses ?? [] as $campus) {
                $campus->activo = $newActiveState;
                $campus->save();
            }

            $cascadeMessage = $newActiveState 
                ? ' Elementos hijos activados en cascada.' 
                : ' Elementos hijos desactivados en cascada.';
            // Construcción de texto descriptivo 
        $previousStatus = $previousState ? 'ACTIVA' : 'INACTIVA';
        $newStatus      = $newActiveState ? 'ACTIVA' : 'INACTIVA';

        AuditLogService::log(
            'editar',
            "Se actualizó el estado de la universidad \"{$university->nombre}\" (ID: {$university->universidad_id}). " .
            "Estado anterior: {$previousStatus}. Estado actual: {$newStatus}. " .
            "El cambio se aplicó también a sus campus asociados.",
            'Universidad'
        );

            return response()->json([
                'message' => 'Estado de la universidad actualizado correctamente.' . $cascadeMessage,
                'data'    => $university
            ], 200);
        }
    /**
     * DELETE /api/universidades/{id}
     */
    public function destroy($id)
    {
        $university = University::find($id);
        if (!$university) {
            return response()->json(['message' => 'Universidad no encontrada.'], 404);
        }

        try {
            $nombre = $university->nombre;     // Guardamos nombre antes de eliminar
            $universityId  = $university->universidad_id;
            // ahora elimina el service
            $this->service->delete($university);
            // Registrar en bitácora
            AuditLogService::log(
        'eliminar',
            "Universidad eliminada: {$nombre} (ID: {$universityId})",
            'Universidad'
            );

            return response()->noContent(); // 204
        } catch (QueryException $e) {
            // Error 1451: violación de FK (registro relacionado)
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: la universidad tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT'
                ], 409);
            }
            Log::error('Error deleting university', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al eliminar.'], 500);
        }
    }
}
