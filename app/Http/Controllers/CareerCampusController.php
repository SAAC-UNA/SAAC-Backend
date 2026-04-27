<?php

namespace App\Http\Controllers;

use App\Models\CareerCampus;
use App\Http\Requests\StoreCareerCampusRequest;
use App\Http\Requests\UpdateCareerCampusRequest;
use Illuminate\Http\Request;

class CareerCampusController extends Controller
{
    /**
     * Display a listing of the resource.
     * Returns all carrera-sede pairs the authenticated user has access to.
     * Superusuario sees all; other roles only see their assigned carrera_sede_id(s).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = CareerCampus::with(['career', 'campus']);

        if ($user && !$user->hasRole('Superusuario')) {
            $ids = $user->careers()->pluck('CARRERA_SEDE.carrera_sede_id');
            $query->whereIn('carrera_sede_id', $ids);
        }

        $items = $query->get()->map(fn(CareerCampus $cs) => [
            'carrera_sede_id' => $cs->carrera_sede_id,
            'carrera_id'      => $cs->carrera_id,
            'sede_id'         => $cs->sede_id,
            'carrera_nombre'  => $cs->career?->nombre ?? '—',
            'sede_nombre'     => $cs->campus?->nombre ?? '—',
        ]);

        return response()->json($items);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Resolve or create a CARRERA_SEDE entry.
     *
     * Receives carrera_id + sede_id, finds an existing pivot entry or creates one,
     * and returns the carrera_sede_id. This is called before creating an accreditation cycle.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'carrera_id' => ['required', 'integer', 'exists:CARRERA,carrera_id'],
            'sede_id'    => ['required', 'integer', 'exists:SEDE,sede_id'],
        ]);

        $careerCampus = CareerCampus::firstOrCreate(
            [
                'carrera_id' => $validated['carrera_id'],
                'sede_id'    => $validated['sede_id'],
            ]
        );

        return response()->json([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(CareerCampus $careerCampus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CareerCampus $careerCampus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCareerCampusRequest $request, CareerCampus $careerCampus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CareerCampus $careerCampus)
    {
        //
    }
}
