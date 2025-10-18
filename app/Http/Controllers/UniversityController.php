<?php

namespace App\Http\Controllers;

use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Services\UniversityService;
use App\Http\Requests\UniversityRequest;

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

        // Datos ya validados por el FormRequest (incluye la regla unique con ignore)
        $updated = $this->service->update($university, $request->validated());

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
            $university = University::find($id);
            if (!$university) {
                return response()->json(['message' => 'Universidad no encontrada.'], 404);
            }

            $validated = $request->validate([
                'active' => ['required', 'boolean'],
            ]);

            $newActiveState = $validated['active'];

            // Actualizar el estado de la universidad
            $university->activo = $newActiveState;
            $university->save();

            // Aplicar cambio en cascada a todos los elementos hijos
            // Procesar campus hijos
            foreach ($university->campuses as $campus) {
                $campus->activo = $newActiveState;
                $campus->save();

                // Aplicar a facultades del campus
                foreach ($campus->faculties as $faculty) {
                    $faculty->activo = $newActiveState;
                    $faculty->save();

                    // Aplicar a carreras de la facultad
                    foreach ($faculty->careers as $career) {
                        $career->activo = $newActiveState;
                        $career->save();
                    }
                }
            }

            // Procesar facultades directas de la universidad (que no tienen campus)
            foreach ($university->faculties as $faculty) {
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
            // ahora elimina el service
            $this->service->delete($university);
            return response()->noContent(); // 204
        } catch (QueryException $e) {
            // Error 1451: violación de FK (registro relacionado)
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: la universidad tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT'
                ], 409);
            }
            return response()->json([
                'message' => 'Error al eliminar.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
