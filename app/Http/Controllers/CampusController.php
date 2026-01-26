<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Services\CampusService;
use App\Http\Requests\CampusRequest;
use App\Services\AuditLogService;

class CampusController extends Controller
{
    protected $service; // Para service

    public function __construct(CampusService $service) // Para service
    {
        $this->service = $service;
    }

    /**
     * GET /api/campus?universidad_id=#
     */
    public function index(Request $request)
    {
        // Delegar al service no directo  (mismo comportamiento)
        $universidadId = $request->filled('universidad_id')
            ? (int) $request->input('universidad_id')
            : null;

        $items = $this->service->getAll($universidadId);
        return response()->json($items, 200);
    }

    /**
     * GET /api/campus/{id}
     */
    public function show($id)
    {
        $campus = $this->service->findById((int)$id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }
        return response()->json($campus, 200);
    }

    /**
     * POST /api/campus
     * { "nombre":"Occidente", "universidad_id":1 }
     */
    public function store(CampusRequest $request)
    {
        $campus = $this->service->create($request->validated());

        $primaryKeyName = $campus->getKeyName();
        // Registro en el log de auditoría
        AuditLogService::log(
'crear',
    "Se creo el campus \"{$campus->nombre}\" (ID: {$campus->campus_id}) perteneciente a la universidad ID {$campus->universidad_id}.",
    'Campus'
        );

        return response()
            ->json([
                'message' => 'Campus creado correctamente.',
                'data'    => $campus
            ], 201)
            ->header('Location', route('campuses.show', $campus->$primaryKeyName));
    }

    /**
     * PUT/PATCH /api/campus/{id}
     */
    public function update(CampusRequest $request, $id)
    {
        $campus = Campus::find($id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }

        $updated = $this->service->update($campus, $request->validated());
        $oldName = $campus->nombre;// Nombre antes de la actualización

        // Registrar en bitácora SOLO si hubo cambios reales
        if ($oldName !== $updated->nombre) {
            AuditLogService::log(
                'editar',
                "Campus actualizado: {$oldName} → {$updated->nombre} (ID: {$updated->campus_id})",
                'Campus'
            );
        }

        return response()->json([
            'message' => 'Campus actualizado correctamente.',
            'data'    => $updated
        ], 200);
    }
    /**
     * DELETE /api/campus/{id}
     */
    public function destroy($id)
    {
        $campus = Campus::find($id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }

        try {
            // Guardar datos antes de la eliminación para el log de auditoría
            $nombre = $campus->nombre;
            $idCampus = $campus->campus_id;
            $universidadId = $campus->universidad_id;
            // antes: $campus->delete()
            // ahora: service->delete()
            $this->service->delete($campus);
            // Registro en el log de bitácora
            AuditLogService::log(
    'eliminar',
        "Se elimino el campus \"{$nombre}\" (ID: {$idCampus}), perteneciente a la universidad ID {$universidadId}.",
        'Campus'
            );


            return response()->noContent(); // 204
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT'
                ], 409);
            }
            return response()->json(['message' => 'Error al eliminar.', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * PATCH /api/campuses/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(Request $request, $id)
    {
        $campus = Campus::find($id);
        if (!$campus) {
            return response()->json(['message' => 'Campus no encontrado.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $newActiveState = $validated['active'];

        // Actualizar el estado del campus
        $campus->activo = $newActiveState;
        $campus->save();

        // Aplicar cambio en cascada a todos los elementos hijos
        foreach ($campus->faculties as $faculty) {
            $faculty->activo = $newActiveState;
            $faculty->save();

            // Aplicar a carreras de la facultad
            foreach ($faculty->careers as $career) {
                $career->activo = $newActiveState;
                $career->save();
            }
        }

        $cascadeMessage = $newActiveState 
            ? ' Elementos hijos activados en cascada.' 
            : ' Elementos hijos desactivados en cascada.';
        $estadoAnterior = $campus->getOriginal('activo') ? 'ACTIVO' : 'INACTIVO';
        $estadoNuevo    = $newActiveState ? 'ACTIVO' : 'INACTIVO';

        AuditLogService::log(
            'editar',
            "Se actualizó el estado del campus \"{$campus->nombre}\" (ID: {$campus->campus_id}). " .
            "Estado anterior: {$estadoAnterior}. Estado actual: {$estadoNuevo}. " .
            "El cambio se aplicó también a sus facultades y carreras asociadas.",
            'Campus'
        );

        return response()->json([
            'message' => 'Estado del campus actualizado correctamente.' . $cascadeMessage,
            'data'    => $campus
        ], 200);
    }
}
