<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador temporal para simular autenticación durante desarrollo.
 * ELIMINAR en producción.
 */
class DevAuthController extends Controller
{
    /**
     * Simula login y retorna token Sanctum
     * 
     * POST /api/dev/login
     * Body: { "usuario_id": 1 }
     */
    public function login(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:USUARIO,usuario_id'
        ]);

        $user = User::with('roles')->findOrFail($request->usuario_id);
        
        // Crear token de autenticación
        $token = $user->createToken('dev-token')->plainTextToken;

        return response()->json([
            'message' => 'Login simulado exitoso',
            'user' => [
                'usuario_id' => $user->usuario_id,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')
            ],
            'token' => $token
        ]);
    }

    /**
     * Cerrar sesión (eliminar token)
     * 
     * POST /api/dev/logout
     * Headers: Authorization: Bearer {token}
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Ver usuario actual autenticado
     * 
     * GET /api/dev/me
     * Headers: Authorization: Bearer {token}
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('roles');

        return response()->json([
            'usuario_id' => $user->usuario_id,
            'nombre' => $user->nombre,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')
        ]);
    }
}
