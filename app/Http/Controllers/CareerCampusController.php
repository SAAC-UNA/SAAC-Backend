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
            $ids = $user->careers()
                ->join('CARRERA_SEDE', 'CARRERA.carrera_id', '=', 'CARRERA_SEDE.carrera_id')
                ->pluck('CARRERA_SEDE.carrera_sede_id');
            $query->whereIn('carrera_sede_id', $ids);
        }

        $items = $query->get()->map(fn(CareerCampus $cs) => [
            'carrera_sede_id' => $cs->carrera_sede_id,
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
     * Store a newly created resource in storage.
     */
    public function store(StoreCareerCampusRequest $request)
    {
        //
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
