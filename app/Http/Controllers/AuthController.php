<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Iniciar sesión con LDAP.
     */
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->attempt($request->cedula, $request->password);

            if (!$result['success']) {
                return response()->json(['message' => match ($result['reason']) {
                    'invalid_credentials' => 'Credenciales inválidas',
                    'inactive'            => 'Usuario inactivo. Contacte al administrador.',
                    'ldap_data'           => 'No se pudieron obtener los datos del usuario desde LDAP',
                    default               => 'Error al sincronizar el usuario',
                }], $result['status']);
            }

            $sessionLifetimeInSeconds = config('session.lifetime') * 60;

            return response()->json([
                'user' => new UserResource($result['user']), 
                'token' => $result['token'],
                'session_lifetime' => $sessionLifetimeInSeconds,
            ], 200)
                ->cookie($result['cookie']);

        } catch (\Exception $e) {
            Log::error('Error en login', [
                'message'    => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            return response()->json(['message' => 'Error al procesar la solicitud de inicio de sesión'], 500);
        }
    }

    /**
     * Cerrar sesión.
     */
    public function logout(Request $request)
    {
        try {
            $this->authService->terminate($request->user());
            return response()->json(['message' => 'Sesión cerrada exitosamente'], 200)
                ->cookie(cookie()->forget('auth_token'));
        } catch (\Exception $e) {
            Log::error('Error en logout: ' . $e->getMessage());
            return response()->json(['message' => 'Error al cerrar sesión'], 500);
        }
    }

    /**
     * Obtener información del usuario autenticado
     *
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            // Si no hay usuario en la solicitud, la sesión no es válida o ha expirado.
            if (!$user) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            // Verificar sesión en Redis (capa extra de seguridad)
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
                'user' => new UserResource($user),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en me: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al obtener información del usuario',
            ], 500);
        }
    }

    /**
     * Obtener permisos y roles del usuario autenticado (para el frontend)
     * 
     * Retorna:
     * - Roles del usuario
     * - Permisos directos asignados al usuario
     * - Permisos heredados de los roles (todos los permisos efectivos)
     * - Descripciones legibles de los permisos
     * 
     * El frontend puede usar esta información para:
     * - Mostrar/ocultar menús según permisos
     * - Deshabilitar botones de acciones no permitidas
     * - Mostrar mensajes informativos sobre restricciones
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissions(Request $request)
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

            // Renovar TTL de la sesión
            Redis::expire($sessionKey, 1800);

            // Obtener roles del usuario
            $roles = $user->roles->pluck('name');

            // Obtener todos los permisos efectivos del usuario
            // (incluye permisos directos + permisos heredados de roles)
            $allPermissions = $user->getAllPermissions()->pluck('name');

            // Obtener solo permisos directos (sin los de roles)
            $directPermissions = $user->permissions->pluck('name');

            // Cargar descripciones de permisos desde configuración
            $permissionDescriptions = config('permissions.descriptions', []);

            // Generar array de permisos con descripciones
            $permissionsWithDescriptions = $allPermissions->mapWithKeys(function ($permission) use ($permissionDescriptions) {
                return [
                    $permission => $permissionDescriptions[$permission] ?? $permission
                ];
            });

            return response()->json([
                'roles' => $roles,
                'permissions' => $allPermissions->values(), // Array simple de permisos
                'permissions_with_descriptions' => $permissionsWithDescriptions, // Objeto con descripciones
                'direct_permissions' => $directPermissions->values(), // Permisos asignados directamente
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en permissions: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al obtener permisos del usuario',
            ], 500);
        }
    }
}
