<?php

namespace App\Services;

use App\Models\User;
use LdapRecord\Container;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para autenticaci??n y sincronizaci??n con LDAP.
 * 
 * Este servicio maneja:
 * - Autenticaci??n de usuarios contra el servidor LDAP
 * - Obtenci??n de datos del usuario desde LDAP
 * - Sincronizaci??n autom??tica de usuarios LDAP a la base de datos
 */
class LdapService
{
    /**
     * Autentica un usuario contra LDAP usando su c??dula y contrase??a.
     *
     * @param string $cedula C??dula del usuario
     * @param string $password Contrase??a del usuario
     * @return array|null Retorna datos del usuario si autenticaci??n exitosa, null si falla
     */
    public function authenticate(string $cedula, string $password): ?array
    {
        try {
            // Verificar si LDAP est?? disponible antes de intentar conectar
            if (empty(config('ldap.connections.default.hosts.0'))) {
                Log::warning("LDAP no configurado, autenticaci??n fallida", [
                    'cedula_hash' => hash('sha256', $cedula),
                    'cedula_last4' => substr($cedula, -4),
                ]);
                return null;
            }
            
            $connection = Container::getConnection('default');
            
            // Construir el DN del usuario basado en la c??dula
            // Formato: uid=203948609,ou=profesores,ou=users,dc=una,dc=local
            // o: uid=203948609,ou=estudiantes,ou=users,dc=una,dc=local
            
            // Intentar primero como profesor
            $userDn = "uid={$cedula},ou=profesores,ou=users," . config('ldap.connections.default.base_dn');
            
            if ($connection->auth()->attempt($userDn, $password)) {
                Log::info("Autenticaci??n LDAP exitosa (profesor)", [
                    'cedula_hash' => hash('sha256', $cedula),
                    'cedula_last4' => substr($cedula, -4),
                ]);
                return $this->getUserDataFromLdap($cedula, $connection);
            }
            
            // Intentar como estudiante
            $userDn = "uid={$cedula},ou=estudiantes,ou=users," . config('ldap.connections.default.base_dn');
            
            if ($connection->auth()->attempt($userDn, $password)) {
                Log::info("Autenticaci??n LDAP exitosa (estudiante)", [
                    'cedula_hash' => hash('sha256', $cedula),
                    'cedula_last4' => substr($cedula, -4),
                ]);
                return $this->getUserDataFromLdap($cedula, $connection);
            }
            
            Log::warning("Autenticaci??n LDAP fallida", [
                'cedula_hash' => hash('sha256', $cedula),
                'cedula_last4' => substr($cedula, -4),
                'ip' => request()->ip(),
            ]);
            return null;
            
        } catch (Exception $e) {
            Log::error("Error en autenticaci??n LDAP", [
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
     * @param string $cedula C??dula del usuario
     * @param \LdapRecord\Connection|null $connection Conexi??n LDAP (opcional)
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
            // Buscar usuario por c??dula
            $user = User::where('cedula', $ldapData['cedula'])->first();
            
            $syncData = [
                'cedula' => $ldapData['cedula'],
                'nombre' => $ldapData['nombre'],
                'email' => $ldapData['email'],
                'password' => null, // Siempre NULL para usuarios LDAP
                'status' => User::STATUS_ACTIVE,
            ];
            
            if ($user) {
                // Usuario existe, actualizar datos
                $user->update($syncData);
                Log::info("Usuario actualizado desde LDAP: {$ldapData['cedula']}");
            } else {
                // Usuario nuevo, crear
                $user = User::create($syncData);
                Log::info("Usuario creado desde LDAP: {$ldapData['cedula']}");
            }
            
            return $user;
            
        } catch (Exception $e) {
            Log::error("Error sincronizando usuario desde LDAP: " . $e->getMessage(), [
                'ldap_data' => $ldapData,
                'exception' => get_class($e)
            ]);
            throw $e;
        }
    }

    /**
     * Verifica si LDAP est?? habilitado en la configuraci??n.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return env('LDAP_ENABLED', false) === true 
            && !empty(config('ldap.connections.default.hosts'));
    }
}
