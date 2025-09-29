<?php

use Illuminate\Support\Facades\Route;



// Ruta de prueba (sin grupo) — más simple
Route::get('/api/ping', function () {
    return response()->json([
        'ok' => true,
        'time' => now()->toIso8601String(), // más compatible
    ]);
});