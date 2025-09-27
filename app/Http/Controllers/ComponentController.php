<?php

namespace App\Http\Controllers;

use App\Models\Component;
<<<<<<< HEAD
use App\Http\Requests\StoreComponentRequest;
use App\Http\Requests\UpdateComponentRequest;

class ComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreComponentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Component $component)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Component $component)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComponentRequest $request, Component $component)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Component $component)
    {
        //
=======
use Illuminate\Database\QueryException;
use App\Services\ComponentService;
use App\Http\Requests\ComponentRequest;


class ComponentController extends Controller
{
    protected $service; // Service

    public function __construct(ComponentService $service) // Service
    {
        $this->service = $service;
    }
    /**
     * GET /api/estructura/componentes
     */
    public function index()
    {
       $items = $this->service->getAll(); // antes: Eloquent directo
        return response()->json($items, 200);
    }

    /**
     * GET /api/estructura/componentes/{id}
     */
    public function show($id)
    {
       $component = $this->service->findById((int)$id); // antes: Eloquent directo
        if (!$component) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }
        return response()->json($component, 200);
    }

    /**
     * POST /api/estructura/componentes
     */
    public function store(ComponentRequest $request)
    {
        $component = $this->service->create($request->validated());

        return response()->json([
        'message' => 'Componente creado correctamente.',
        'data'    => $component->load(['dimension','comment']),
        ], 201);
    }

    /**
     * PUT/PATCH /api/estructura/componentes/{id}
     * (permite actualización parcial)
     */
    public function update(ComponentRequest $request, $id)
    {
        $component = Component::find($id);
        if (!$component) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }

        $updated = $this->service->update($component, $request->validated());

        return response()->json([
            'message' => 'Componente actualizado correctamente.',
            'data'    => $updated,
        ], 200);
    }

    /**
     * DELETE /api/estructura/componentes/{id}
     */
    public function destroy($id)
    {
        $component = Component::find($id);
        if (!$component) {
            return response()->json(['message' => 'Componente no encontrado.'], 404);
        }

        try {
            $this->service->delete($component); // antes: $c->delete()
            return response()->noContent(); // 204
        } catch (QueryException $e) {
            $sqlState  = $e->errorInfo[0] ?? null;   // '23000' => integridad
            $driverErr = (int)($e->errorInfo[1] ?? 0); // 1451 => FK en DELETE

            if ($sqlState === '23000' && $driverErr === 1451) {
            return response()->json([
                'message' => 'No se puede eliminar: el componente tiene registros relacionados.',
                'code'    => 'FK_CONSTRAINT',
            ], 409);
    }

        return response()->json([
            'message' => 'Error al eliminar.',
            'error'   => $e->getMessage(),
         ], 500);
        }
>>>>>>> 02_API_de_Endpoints_de_Estructura
    }
}
