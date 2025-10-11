<?php

namespace App\Http\Controllers;

use App\Models\Autoevaluation;
use App\Http\Requests\StoreAutoevaluationRequest;
use App\Http\Requests\UpdateAutoevaluationRequest;

class AutoevaluationController extends Controller
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
    public function store(StoreAutoevaluationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Autoevaluation $autoevaluation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Autoevaluation $autoevaluation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAutoevaluationRequest $request, Autoevaluation $autoevaluation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Autoevaluation $autoevaluation)
    {
        //
    }
}
