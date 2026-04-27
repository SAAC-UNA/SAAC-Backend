<?php

namespace App\Services;

use App\Models\User;

/**
 * Gestiona el ciclo completo de autenticación de sesiones:
 * credenciales LDAP, invalidación de tokens previos y cookie.
 *
 * Extrae estas responsabilidades de AuthController (SRP).
 */
class AuthService
{
    public function __construct(
        private readonly LdapService $ldapService
    ) {}

    /**
     * Intenta autenticar al usuario contra LDAP y, si tiene éxito,
    * establece el token Sanctum y la cookie.
     *
     * @return array{success: bool, reason?: string, status?: int, user?: User, cookie?: \Symfony\Component\HttpFoundation\Cookie}
     */
    public function attempt(string $cedula, string $password): array
    {
        // authenticate() ya llama getUserDataFromLdap internamente y retorna los datos
        $ldapData = $this->ldapService->authenticate($cedula, $password);

        if (!$ldapData) {
            $userId = User::where('cedula', $cedula)->value('usuario_id');
            AuditLogService::log(
                'login_fallido',
                "Intento de login fallido - Credenciales inválidas para cédula: {$cedula}",
                'Autenticación',
                $userId
            );
            return ['success' => false, 'reason' => 'invalid_credentials', 'status' => 401];
        }

        $user = $this->ldapService->syncUserFromLdap($ldapData);

        if (!$user) {
            return ['success' => false, 'reason' => 'sync_error', 'status' => 500];
        }

        if (!$user->isActive()) {
            AuditLogService::log(
                'login_fallido',
                "Intento de login fallido - Usuario inactivo: {$user->nombre} (Cédula: {$cedula})",
                'Autenticación',
                $user->usuario_id
            );
            return ['success' => false, 'reason' => 'inactive', 'status' => 403];
        }

        // SEGURIDAD: Invalidar todas las sesiones y tokens previos
        $user->tokens()->delete();

        $sessionLifetimeMinutes = (int) config('session.lifetime', 120);
        $token = $user->createToken('auth-token', ['*'], now()->addMinutes($sessionLifetimeMinutes))->plainTextToken;
        $user->load(['roles', 'permissions', 'careers.career']);

        AuditLogService::log(
            'login',
            "Usuario {$user->nombre} inició sesión exitosamente",
            'Autenticación',
            $user->usuario_id
        );

        $cookie = cookie(
            name:     'auth_token',
            value:    $token,
            minutes:  $sessionLifetimeMinutes,
            path:     '/',
            domain:   '',            // Vacío = solo el host actual
            secure:   false,         // true en producción con HTTPS
            httpOnly: true,          // NO accesible desde JavaScript (XSS protection)
            raw:      false,
            sameSite: 'lax'
        );

        return ['success' => true, 'user' => $user, 'cookie' => $cookie, 'token' => $token];
    }

    /**
     * Cierra la sesión del usuario: elimina token actual, limpia contexto y registra en bitácora.
     */
    public function terminate(User $user): void
    {
        optional($user->currentAccessToken())->delete();
        app(GlobalFilterContextService::class)->clear($user);
        AuditLogService::log('logout', "Usuario {$user->nombre} cerró sesión", 'Autenticación');
    }
}
