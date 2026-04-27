<?php

namespace App\Services;

use App\Models\User;
use LdapRecord\Container;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para autenticación y sincronización con LDAP.
 * 
 * Este servicio maneja:
 * - Autenticación de usuarios contra el servidor LDAP
 * - Obtención de datos del usuario desde LDAP
 * - Sincronización automática de usuarios LDAP a la base de datos
 */
class LdapService
{
    /**
     * Autentica un usuario contra LDAP usando su cédula y contraseña.
     *
     * @param string $cedula Cédula del usuario
     * @param string $password Contraseña del usuario
     * @return array|null Retorna datos del usuario si autenticación exitosa, null si falla
     */
    public function authenticate(string $cedula, string $password): ?array
    {
        try {
            // Verificar si LDAP está disponible antes de intentar conectar
            if (empty(config('ldap.connections.default.hosts.0'))) {
                Log::warning("LDAP no configurado, autenticación fallida", [
                    'cedula_hash' => hash('sha256', $cedula),
                    'cedula_last4' => substr($cedula, -4),
                ]);
                return null;
            }
            
            $connection = Container::getConnection('default');
            $baseDn     = config('ldap.connections.default.base_dn');

            foreach (config('ldap.organizational_units', ['profesores', 'estudiantes']) as $ou) {
                $userDn = "uid={$cedula},ou={$ou},ou=users,{$baseDn}";
                if ($connection->auth()->attempt($userDn, $password)) {
                    Log::info("Autenticación LDAP exitosa ({$ou})", [
                        'cedula_hash'  => hash('sha256', $cedula),
                        'cedula_last4' => substr($cedula, -4),
                    ]);
                    return $this->getUserDataFromLdap($cedula, $connection);
                }
            }
            
            Log::warning("Autenticación LDAP fallida", [
                'cedula_hash' => hash('sha256', $cedula),
                'cedula_last4' => substr($cedula, -4),
                'ip' => request()->ip(),
            ]);
            return null;
            
        } catch (Exception $e) {
            Log::error("Error en autenticación LDAP", [
                'cedula_hash' => hash('sha256', $cedula),
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'ip' => request()->ip(),
            ]);
            return null;
        }
    }

    /**
     * Obtiene los datos del usuario desde LDAP.
     *
     * @param string $cedula Cédula del usuario
     * @param \LdapRecord\Connection|null $connection Conexión LDAP (opcional)
     * @return array|null Datos del usuario o null si no se encuentra
     */
    public function getUserDataFromLdap(string $cedula, $connection = null): ?array
    {
        try {
            $connection = $connection ?? Container::getConnection('default');
            
            // Buscar el usuario en LDAP
            $query = $connection->query();
            $users = $query->where('uid', '=', $cedula)->get();
            
            if (count($users) === 0) {
                Log::warning("Usuario no encontrado en LDAP", [
                    'cedula_hash' => hash('sha256', $cedula),
                ]);
                return null;
            }
            
            $user = $users[0];
            
            // Extraer datos relevantes
            return [
                'cedula' => $user['uid'][0] ?? $cedula,
                'nombre' => $user['cn'][0] ?? 'Usuario LDAP',
                'email' => $user['mail'][0] ?? "{$cedula}@una.cr",
            ];
            
        } catch (Exception $e) {
            Log::error("Error obteniendo datos de LDAP", [
                'cedula_hash' => hash('sha256', $cedula),
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return null;
        }
    }

    /**
     * Sincroniza un usuario desde LDAP a la base de datos.
     * Si el usuario no existe, lo crea. Si existe, actualiza sus datos.
     *
     * @param array $ldapData Datos del usuario desde LDAP
     * @return User Usuario sincronizado
     */
    public function syncUserFromLdap(array $ldapData): User
    {
        try {
            $user = User::updateOrCreate(
                ['cedula' => $ldapData['cedula']],
                [
                    'nombre' => $ldapData['nombre'],
                    'email'  => $ldapData['email'],
                    'status' => User::STATUS_ACTIVE,
                ]
            );

            if ($user->wasRecentlyCreated) {
                $user->assignRole('Profesor');
                Log::info("Usuario creado desde LDAP con rol Profesor: {$ldapData['cedula']}");
            } else {
                Log::info("Usuario actualizado desde LDAP: {$ldapData['cedula']}");
            }

            return $user;
        } catch (Exception $e) {
            Log::error("Error sincronizando usuario desde LDAP: " . $e->getMessage(), [
                'ldap_data' => $ldapData,
                'exception' => get_class($e),
            ]);
            throw $e;
        }
    }

    /**
     * Verifica si LDAP está habilitado en la configuración.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return env('LDAP_ENABLED', false) === true 
            && !empty(config('ldap.connections.default.hosts'));
    }
}
