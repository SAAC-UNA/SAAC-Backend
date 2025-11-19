# Autenticación con LDAP y Redis - HU-001

Documentación completa del sistema de autenticación implementado para el proyecto SAAC.

## 📋 Tabla de Contenidos

1. [Resumen General](#resumen-general)
2. [Requisitos Previos](#requisitos-previos)
3. [Configuración de Contenedores Docker](#configuración-de-contenedores-docker)
4. [Variables de Entorno](#variables-de-entorno)
5. [Base de Datos](#base-de-datos)
6. [Usuarios LDAP](#usuarios-ldap)
7. [Estructura del Código](#estructura-del-código)
8. [Uso de los Endpoints](#uso-de-los-endpoints)
9. [Gestión de Sesiones](#gestión-de-sesiones)
10. [Troubleshooting](#troubleshooting)

---

## Resumen General

El sistema implementa autenticación mediante:

- **LDAP (OpenLDAP)**: Validación de credenciales contra servidor LDAP
- **Redis**: Almacenamiento de sesiones con TTL de 30 minutos
- **Laravel Sanctum**: Generación y gestión de tokens de API
- **Spatie Permissions**: Control de roles y permisos
- **Auto-sync**: Sincronización automática de usuarios LDAP a base de datos local

### Flujo de Autenticación

1. Usuario envía cédula + password a `/api/auth/login`
2. Sistema valida credenciales contra LDAP
3. Si es válido, sincroniza/actualiza usuario en BD local
4. Verifica que el usuario esté activo
5. Genera token Sanctum
6. Guarda sesión en Redis con TTL de 30 minutos
7. Retorna usuario completo + token

---

## Requisitos Previos

### Software Necesario

- **Docker Desktop** (para contenedores LDAP y Redis)
- **PHP 8.4+** con extensiones:
  - `ldap`
  - `pdo_mysql`
  - `redis` (opcional, usa Predis como alternativa)
- **MySQL 8.0+**
- **Composer 2.x**

### Extensiones PHP

Verificar en `php.ini`:

```ini
extension=ldap
extension=pdo_mysql
# extension=redis  ; Opcional - Predis funciona sin esto
```

---

## Configuración de Contenedores Docker

### Contenedor Redis

El proyecto usa Redis en Docker para gestión de sesiones:

```yaml
version: '3.8'

services:
  # Redis
  redis:
    image: redis:latest
    container_name: saac_redis
    ports:
      - "6379:6379"
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    networks:
      - saac

volumes:
  redis_data:

networks:
  saac:
    driver: bridge
```

### Comandos Docker

```bash
# Iniciar contenedor Redis
docker-compose up -d redis

# Verificar estado
docker ps | grep saac_redis

# Ver logs
docker logs saac_redis

# Detener contenedor
docker stop saac_redis

# Eliminar contenedor
docker rm saac_redis

# Detener y eliminar volumen (¡CUIDADO! Borra datos)
docker-compose down -v
```

---

## Variables de Entorno

### Archivo `.env`

```env
# Aplicación
APP_NAME=SAAC
APP_ENV=local
APP_KEY=base64:TU_LLAVE_AQUI
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=saac
DB_USERNAME=saac_user
DB_PASSWORD=saac_password

# Redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX="laravel-database-"

# Sesiones
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# LDAP
LDAP_ENABLED=true
LDAP_LOGGING=true
LDAP_CACHE=false
LDAP_HOST=localhost
LDAP_PORT=389
LDAP_BASE_DN=dc=una,dc=local
LDAP_USERNAME=cn=admin,dc=una,dc=local
LDAP_PASSWORD=admin
LDAP_TIMEOUT=5
```

### Variables Importantes

| Variable | Valor | Descripción |
|----------|-------|-------------|
| `REDIS_CLIENT` | `predis` | Cliente PHP puro (no requiere extensión redis) |
| `SESSION_DRIVER` | `redis` | Sesiones en Redis en lugar de archivos/BD |
| `LDAP_ENABLED` | `true` | Habilita autenticación LDAP |
| `LDAP_BASE_DN` | `dc=una,dc=local` | DN base del directorio LDAP |

---

## Base de Datos

### Migración de Usuarios

**Archivo**: `database/migrations/2025_09_21_140513_create_users_table.php`

```php
Schema::create('USUARIO', function (Blueprint $table) {
    $table->id()->name('usuario_id');
    $table->string('cedula', 20)->unique();      // Cédula 9-20 caracteres
    $table->string('nombre', 80);                // Nombre completo
    $table->string('email', 255)->unique();      // Email único
    $table->string('password', 255)->nullable(); // NULL para usuarios LDAP
    $table->string('status', 20)->default('active'); // active/inactive
    $table->timestamps();
});
```

### Campos Importantes

- **cedula**: Identificador único (9-12 caracteres para cédulas CR)
- **password**: `NULL` para usuarios LDAP (autentican contra servidor)
- **status**: `active` o `inactive` (constantes en modelo User)
- **email**: Debe ser único (usado para `firstOrCreate`)

### Ejecutar Migraciones

```bash
# Ejecutar todas las migraciones
php artisan migrate

# Refrescar migraciones (¡CUIDADO! Borra datos)
php artisan migrate:fresh

# Ejecutar seeders
php artisan db:seed --class=UserSeeder
```

---

## Usuarios LDAP

### Estructura del Directorio

```
dc=una,dc=local
└── ou=users
    ├── ou=profesores
    │   ├── uid=203849675 (Pablo Castillo)
    │   └── uid=203948609 (Cristopher Montero)
    └── ou=estudiantes
        ├── uid=801490957 (Naydelin Jiron)
        ├── uid=208330811 (Jose Jara)
        ├── uid=118620669 (Marisol Hidalgo)
        ├── uid=207800171 (Ian Villegas)
        └── uid=206870079 (Ana Zuniga)
```

### Usuarios Disponibles

| Cédula | Nombre | Email | Password | Rol | OU |
|--------|--------|-------|----------|-----|-----|
| 203849675 | Pablo Castillo Quesada | pablo.castillo.quesada@una.cr | password123 | Superusuario | profesores |
| 203948609 | Cristopher Montero Jimenez | cristopher.montero.jimenez@una.cr | password123 | Administrador | profesores |
| 801490957 | Naydelin Nayeli Jiron Castellon | nayidelin.jiron.castellon@est.una.ac.cr | password123 | Profesor | estudiantes |
| 208330811 | Jose Andres Jara Arias | jose.jara.arias@est.una.ac.cr | password123 | Profesor | estudiantes |
| 118620669 | Marisol Hidalgo Murillo | marisol.hidalgo.murillo@est.una.ac.cr | password123 | Profesor | estudiantes |
| 207800171 | Ian Enmanuel Villegas Jimenez | ian.villegas.jimenez@est.una.ac.cr | password123 | Profesor | estudiantes |
| 206870079 | Ana Cristina Zuniga Cardenas | ana.zuniga.cardenas@est.una.ac.cr | password123 | Profesor | estudiantes |

### Importar Usuarios LDAP

Si necesitas reimportar usuarios:

```bash
# Copiar archivo LDIF al contenedor
docker cp users.ldif saac_ldap:/tmp/users.ldif

# Importar al servidor LDAP
docker exec saac_ldap ldapadd -x -D "cn=admin,dc=una,dc=local" -w admin -f /tmp/users.ldif -c

# Verificar importación
docker exec saac_ldap ldapsearch -x -b "dc=una,dc=local" "(objectClass=inetOrgPerson)" uid cn mail
```

### Comandos Útiles LDAP

```bash
# Ver todos los usuarios
docker exec saac_ldap slapcat

# Buscar por cédula
docker exec saac_ldap ldapsearch -x -b "dc=una,dc=local" "(uid=203948609)" cn mail

# Buscar profesores
docker exec saac_ldap ldapsearch -x -b "ou=profesores,ou=users,dc=una,dc=local" uid cn

# Buscar estudiantes
docker exec saac_ldap ldapsearch -x -b "ou=estudiantes,ou=users,dc=una,dc=local" uid cn
```

---

## Estructura del Código

### Archivos Creados/Modificados

```
app/
├── Http/
│   ├── Controllers/
│   │   └── AuthController.php         # Controlador de autenticación
│   ├── Middleware/
│   │   └── RefreshSessionMiddleware.php  # Renueva TTL de sesión
│   └── Requests/
│       └── LoginRequest.php           # Validación de login
├── Models/
│   └── User.php                       # Modelo actualizado
└── Services/
    └── LdapService.php                # Servicio de LDAP

database/
├── migrations/
│   └── 2025_09_21_140513_create_users_table.php
└── seeders/
    └── UserSeeder.php                 # Seeders de usuarios LDAP

config/
└── ldap.php                           # Configuración LDAP (publicado)

routes/
└── api.php                            # Rutas de autenticación

bootstrap/
└── app.php                            # Registro de middleware
```

### Modelo User

**Archivo**: `app/Models/User.php`

Constantes de estado:

```php
const STATUS_ACTIVE = 'active';
const STATUS_INACTIVE = 'inactive';
```

Métodos útiles:

```php
$user->isActive();      // Verifica si status == 'active'
$user->activate();      // Cambia status a 'active'
$user->deactivate();    // Cambia status a 'inactive'

User::active()->get();  // Scope para usuarios activos
```

### LdapService

**Archivo**: `app/Services/LdapService.php`

Métodos principales:

```php
// Autenticar usuario contra LDAP
$ldapService->authenticate($cedula, $password);

// Obtener datos de usuario desde LDAP
$ldapData = $ldapService->getUserDataFromLdap($cedula);

// Sincronizar usuario LDAP a BD local
$user = $ldapService->syncUserFromLdap($ldapData);

// Verificar si LDAP está habilitado
$ldapService->isEnabled();
```

---

## Uso de los Endpoints

### Base URL

```
http://localhost:8000/api
```

### 1. Login

**Endpoint**: `POST /auth/login`

**Headers**:
```
Content-Type: application/json
Accept: application/json
```

**Body**:
```json
{
  "cedula": "203948609",
  "password": "password123"
}
```

**Respuesta Exitosa (200)**:
```json
{
  "message": "Inicio de sesión exitoso",
  "user": {
    "usuario_id": 2,
    "cedula": "203948609",
    "nombre": "Cristopher Montero Jimenez",
    "email": "cristopher.montero.jimenez@una.cr",
    "status": "active",
    "created_at": "2025-11-19T22:00:12.000000Z",
    "updated_at": "2025-11-19T22:00:12.000000Z",
    "roles": [
      {
        "id": 2,
        "name": "Administrador",
        "guard_name": "api"
      }
    ],
    "permissions": [],
    "careers": [
      {
        "carrera_id": 1,
        "nombre": "Ingeniería en Sistemas de Información"
      }
    ]
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz123456789"
}
```

**Errores**:
- `401`: Credenciales inválidas
- `403`: Usuario inactivo
- `500`: Error en sincronización o LDAP

### 2. Obtener Usuario Actual

**Endpoint**: `GET /auth/me`

**Headers**:
```
Authorization: Bearer {token}
Accept: application/json
```

**Respuesta Exitosa (200)**:
```json
{
  "user": {
    "usuario_id": 2,
    "cedula": "203948609",
    "nombre": "Cristopher Montero Jimenez",
    "email": "cristopher.montero.jimenez@una.cr",
    "status": "active",
    "roles": [...],
    "permissions": [...],
    "careers": [...]
  },
  "session": {
    "usuario_id": 2,
    "cedula": "203948609",
    "nombre": "Cristopher Montero Jimenez",
    "email": "cristopher.montero.jimenez@una.cr",
    "roles": ["Administrador"],
    "login_at": "2025-11-19 22:11:31"
  }
}
```

**Errores**:
- `401`: Token inválido o sesión expirada

### 3. Logout

**Endpoint**: `POST /auth/logout`

**Headers**:
```
Authorization: Bearer {token}
Accept: application/json
```

**Respuesta Exitosa (200)**:
```json
{
  "message": "Sesión cerrada exitosamente"
}
```

**Errores**:
- `401`: Token inválido
- `500`: Error al cerrar sesión

### Ejemplos con cURL

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"cedula":"203948609","password":"password123"}'

# Me
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Logout
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Ejemplos con PowerShell

```powershell
# Login
$body = @{ cedula = "203948609"; password = "password123" } | ConvertTo-Json
Invoke-WebRequest -Uri "http://localhost:8000/api/auth/login" `
  -Method POST -Body $body -ContentType "application/json" | 
  Select-Object -ExpandProperty Content

# Me
$token = "tu_token_aqui"
Invoke-WebRequest -Uri "http://localhost:8000/api/auth/me" `
  -Method GET -Headers @{ "Authorization" = "Bearer $token"; "Accept" = "application/json" } |
  Select-Object -ExpandProperty Content

# Logout
Invoke-WebRequest -Uri "http://localhost:8000/api/auth/logout" `
  -Method POST -Headers @{ "Authorization" = "Bearer $token"; "Accept" = "application/json" } |
  Select-Object -ExpandProperty Content
```

---

## Gestión de Sesiones

### Redis

Las sesiones se almacenan en Redis con la siguiente estructura:

**Clave**: `laravel-database-session:user:{usuario_id}`

**Valor** (JSON):
```json
{
  "usuario_id": 2,
  "cedula": "203948609",
  "nombre": "Cristopher Montero Jimenez",
  "email": "cristopher.montero.jimenez@una.cr",
  "roles": ["Administrador"],
  "login_at": "2025-11-19 22:11:31"
}
```

**TTL**: 1800 segundos (30 minutos)

### Sliding Expiration

El sistema implementa **sliding expiration**: cada petición autenticada renueva automáticamente el TTL a 30 minutos.

**Comportamiento**:
- Usuario hace login → TTL = 30 min
- Usuario hace petición a los 10 min → TTL se renueva a 30 min
- Usuario inactivo por 30 min → Sesión expira automáticamente

### Comandos Redis

```bash
# Ver todas las claves
docker exec saac_redis redis-cli KEYS "*"

# Ver sesión específica
docker exec saac_redis redis-cli GET "laravel-database-session:user:2"

# Ver TTL de sesión
docker exec saac_redis redis-cli TTL "laravel-database-session:user:2"

# Eliminar sesión manualmente
docker exec saac_redis redis-cli DEL "laravel-database-session:user:2"

# Limpiar Redis completamente (¡CUIDADO!)
docker exec saac_redis redis-cli FLUSHALL

# Verificar conexión
docker exec saac_redis redis-cli PING
```

### Middleware RefreshSessionMiddleware

Este middleware se ejecuta en todas las rutas protegidas y renueva el TTL automáticamente:

```php
Route::middleware(['auth:sanctum', 'refresh.session'])->group(function () {
    // Todas estas rutas renuevan automáticamente la sesión
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    // ... otras rutas protegidas
});
```

---

## Troubleshooting

### Problema: No se puede conectar a LDAP

**Síntomas**:
- Error: "Could not connect to LDAP server"

**Solución**:
```bash
# Verificar que el contenedor esté corriendo
docker ps | grep saac_ldap

# Ver logs del contenedor
docker logs saac_ldap

# Verificar conectividad
telnet localhost 389

# Reiniciar contenedor
docker restart saac_ldap
```

### Problema: Redis no guarda sesiones

**Síntomas**:
- Sesión no aparece en Redis
- Prefijo duplicado en clave

**Solución**:
```bash
# Verificar configuración de Redis
php artisan tinker
> config('database.redis.options.prefix')

# Debe retornar: "laravel-database-"

# Limpiar Redis y reintentar
docker exec saac_redis redis-cli FLUSHALL
```

### Problema: Credenciales LDAP inválidas

**Síntomas**:
- Login retorna 401 con credenciales correctas

**Solución**:
```bash
# Verificar usuario en LDAP
docker exec saac_ldap ldapsearch -x -b "dc=una,dc=local" "(uid=203948609)"

# Probar autenticación directa
docker exec saac_ldap ldapsearch -x -D "uid=203948609,ou=profesores,ou=users,dc=una,dc=local" -w password123 -b "dc=una,dc=local"

# Verificar logs de Laravel
tail -f storage/logs/laravel.log
```

### Problema: Token inválido después de login

**Síntomas**:
- Login exitoso pero `/me` retorna 401

**Solución**:
```bash
# Verificar tokens en BD
php artisan tinker
> \App\Models\User::find(2)->tokens

# Limpiar tokens viejos
php artisan sanctum:prune-expired

# Verificar middleware en rutas
grep -r "auth:sanctum" routes/api.php
```

### Problema: Sesión expira demasiado rápido

**Síntomas**:
- Sesión expira antes de 30 minutos

**Solución**:
```bash
# Verificar TTL en Redis
docker exec saac_redis redis-cli TTL "laravel-database-session:user:2"

# Debe retornar ~1800 (30 min en segundos)

# Verificar que el middleware esté aplicado
# Debe estar en todas las rutas protegidas
Route::middleware(['auth:sanctum', 'refresh.session'])
```

### Problema: Usuario no se sincroniza desde LDAP

**Síntomas**:
- Login exitoso pero usuario no aparece en BD

**Solución**:
```bash
# Verificar conexión a BD
php artisan tinker
> DB::connection()->getPdo()

# Verificar que LdapService esté habilitado
> app(\App\Services\LdapService::class)->isEnabled()

# Ejecutar seeder manualmente
php artisan db:seed --class=UserSeeder

# Ver logs
tail -f storage/logs/laravel.log
```

### Logs Útiles

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# LDAP container logs
docker logs -f saac_ldap

# Redis container logs
docker logs -f saac_redis

# MySQL container logs
docker logs -f saac_mysql

# Artisan serve output
php artisan serve --verbose
```

---

## Resumen de Comandos

### Setup Inicial

```bash
# 1. Instalar dependencias
composer install

# 2. Copiar .env
cp .env.example .env

# 3. Generar APP_KEY
php artisan key:generate

# 4. Iniciar contenedores Docker
docker-compose up -d

# 5. Ejecutar migraciones
php artisan migrate

# 6. Ejecutar seeders
php artisan db:seed

# 7. Importar usuarios LDAP
docker cp users.ldif saac_ldap:/tmp/users.ldif
docker exec saac_ldap ldapadd -x -D "cn=admin,dc=una,dc=local" -w admin -f /tmp/users.ldif -c

# 8. Iniciar servidor
php artisan serve
```

### Comandos de Mantenimiento

```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver rutas
php artisan route:list

# Ver migraciones
php artisan migrate:status

# Refrescar BD (¡CUIDADO!)
php artisan migrate:fresh --seed

# Limpiar tokens expirados
php artisan sanctum:prune-expired
```

---

## Notas Finales

- **Seguridad**: En producción, cambiar todos los passwords por defecto
- **HTTPS**: Usar SSL/TLS para LDAP (puerto 636) en producción
- **Logs**: Revisar logs regularmente para detectar intentos de login fallidos
- **Backup**: Hacer backup regular de Redis si hay datos críticos en sesiones
- **Performance**: Monitorear uso de Redis y ajustar TTL según necesidades

---

**Documentación creada**: 19 de noviembre de 2025  
**Versión**: 1.0  
**Historia de Usuario**: HU-001 Inicio de Sesión
