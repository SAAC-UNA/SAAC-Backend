<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'modules' => config('permissions.modules'),
            'labels'  => config('permissions.labels', []),   // si agregaste etiquetas por módulo
            'aliases' => config('permissions.list', []),     // si usás alias tipo gestion_*
        ], 200);
    }
}
