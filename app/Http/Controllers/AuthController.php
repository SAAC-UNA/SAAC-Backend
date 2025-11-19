<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\User;

class AuthController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    /**
     * Iniciar sesión con LDAP
     * 
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            $cedula = $request->cedula;
            $password = $request->password;

            // Autenticar contra LDAP
            if (!$this->ldapService->authenticate($cedula, $password)) {
                return response()->json([
                    'message' => 'Credenciales inválidas',
                ], 401);
            }

            // Obtener datos del usuario desde LDAP
            $ldapData = $this->ldapService->getUserDataFromLdap($cedula);
            
            if (!$ldapData) {
                return response()->json([
                    'message' => 'No se pudieron obtener los datos del usuario desde LDAP',
                ], 500);
            }

            // Sincronizar usuario en la base de datos local
            $user = $this->ldapService->syncUserFromLdap($ldapData);

            if (!$user) {
                return response()->json([
                    'message' => 'Error al sincronizar el usuario',
                ], 500);
            }

            // Verificar que el usuario esté activo
            if (!$user->isActive()) {
                return response()->json([
                    'message' => 'Usuario inactivo. Contacte al administrador.',
                ], 403);
            }

            // Generar token de Sanctum
            $token = $user->createToken('auth-token')->plainTextToken;

            // Cargar relaciones
            $user->load(['roles', 'permissions', 'careers']);

            // Guardar sesión en Redis
            $sessionKey = "session:user:{$user->usuario_id}";
            $sessionData = [
                'usuario_id' => $user->usuario_id,
                'cedula' => $user->cedula,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'login_at' => now()->toDateTimeString(),
            ];
            
            Redis::setex($sessionKey, 1800, json_encode($sessionData)); // 30 minutos

            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'user' => $user,
                'token' => $token,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al procesar la solicitud de inicio de sesión',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cerrar sesión (eliminar token actual)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            // Eliminar sesión de Redis
            $sessionKey = "session:user:{$user->usuario_id}";
            Redis::del($sessionKey);
            
            // Eliminar el token actual del usuario
            $user->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Sesión cerrada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en logout: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al cerrar sesión',
            ], 500);
        }
    }

    /**
     * Obtener información del usuario autenticado
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            // Verificar sesión en Redis
            $sessionKey = "session:user:{$user->usuario_id}";
            $sessionData = Redis::get($sessionKey);
            
            if (!$sessionData) {
                return response()->json([
                    'message' => 'Sesión expirada',
                ], 401);
            }
            
            // Renovar TTL de la sesión (sliding expiration - 30 minutos más)
            Redis::expire($sessionKey, 1800);
            
            // Cargar relaciones necesarias
            $user->load(['roles', 'permissions', 'careers']);

            return response()->json([
                'user' => $user,
                'session' => json_decode($sessionData, true),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en me: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al obtener información del usuario',
            ], 500);
        }
    }
}
