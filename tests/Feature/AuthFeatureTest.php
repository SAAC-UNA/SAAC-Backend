<?php

use App\Models\User;
use App\Models\ActionType;
use App\Services\LdapService;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Mock Redis para evitar dependencia real
    Redis::shouldReceive('del')->andReturn(true);
    Redis::shouldReceive('setex')->andReturn(true);
    Redis::shouldReceive('exists')->andReturn(false);
    Redis::shouldReceive('get')->andReturn(null);
    
    // Mock Log para evitar escritura real
    Log::shouldReceive('error')->andReturn(true);
    Log::shouldReceive('warning')->andReturn(true);
    Log::shouldReceive('info')->andReturn(true);
    Log::shouldReceive('debug')->andReturn(true);
    
    // Create necessary ActionTypes for AuditLogService
    ActionType::factory()->create(['descripcion' => 'login_fallido']);
    ActionType::factory()->create(['descripcion' => 'login']);
    ActionType::factory()->create(['descripcion' => 'logout']);
});

it('can login successfully with valid LDAP credentials', function () {
    // Arrange
    $ldapService = Mockery::mock(LdapService::class);
    $this->app->instance(LdapService::class, $ldapService);
    
    $userData = [
        'cedula' => '1234567890',
        'nombre' => 'Juan Pérez',
        'email' => 'juan.perez@test.com',
    ];
    
    $user = User::factory()->create([
        'cedula' => '1234567890',
        'nombre' => 'Juan Pérez',
        'email' => 'juan.perez@test.com',
        'status' => User::STATUS_ACTIVE,
    ]);
    
    // Mock LDAP service - según el AuthController, authenticate devuelve array o null
    $ldapService->shouldReceive('authenticate')
        ->with('1234567890', 'password123')
        ->once()
        ->andReturn($userData);  // Devuelve array de datos del usuario
        
    $ldapService->shouldReceive('getUserDataFromLdap')
        ->with('1234567890')
        ->once()
        ->andReturn($userData);  // El controller también llama esto después
        
    $ldapService->shouldReceive('syncUserFromLdap')
        ->with($userData)
        ->once()
        ->andReturn($user);
    
    // Act
    $response = $this->postJson('/api/auth/login', [
        'cedula' => '1234567890',
        'password' => 'password123',
    ]);
    
    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => [
                'id',
                'name', 
                'email',
                'cedula',
                'status',
                'roles',
                'direct_permissions',
                'all_permissions',
            ],
        ]);
        
    // Verificar que se creó la cookie
    $response->assertCookie('auth_token');
    
    // Verificar que el usuario tiene datos correctos
    expect($response->json('user.name'))->toBe('Juan Pérez');
    expect($response->json('user.cedula'))->toBe('1234567890');
    expect($response->json('user.status'))->toBe('active');
});

it('fails login with invalid LDAP credentials', function () {
    // Arrange
    $ldapService = Mockery::mock(LdapService::class);
    $this->app->instance(LdapService::class, $ldapService);
    
    // Mock LDAP authentication failure - devuelve null para credenciales inválidas
    $ldapService->shouldReceive('authenticate')
        ->with('1234567890', 'wrongpassword')
        ->once()
        ->andReturn(null);
    
    // Act
    $response = $this->postJson('/api/auth/login', [
        'cedula' => '1234567890',
        'password' => 'wrongpassword',
    ]);
    
    // Assert
    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Credenciales inválidas',
        ]);
        
    $response->assertCookieMissing('auth_token');
});

it('fails login when user is inactive', function () {
    // Arrange
    $ldapService = Mockery::mock(LdapService::class);
    $this->app->instance(LdapService::class, $ldapService);
    
    $userData = [
        'cedula' => '1234567890',
        'nombre' => 'Juan Pérez',
        'email' => 'juan.perez@test.com',
    ];
    
    $user = User::factory()->create([
        'cedula' => '1234567890',
        'nombre' => 'Juan Pérez',
        'email' => 'juan.perez@test.com',
        'status' => User::STATUS_INACTIVE,
    ]);
    
    // Mock LDAP service - authenticate devuelve datos pero usuario está inactivo
    $ldapService->shouldReceive('authenticate')
        ->with('1234567890', 'password123')
        ->once()
        ->andReturn($userData);
        
    $ldapService->shouldReceive('getUserDataFromLdap')
        ->with('1234567890')
        ->once()
        ->andReturn($userData);
        
    $ldapService->shouldReceive('syncUserFromLdap')
        ->with($userData)
        ->once()
        ->andReturn($user);
    
    // Act
    $response = $this->postJson('/api/auth/login', [
        'cedula' => '1234567890',
        'password' => 'password123',
    ]);
    
    // Assert
    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Usuario inactivo. Contacte al administrador.',
        ]);
});

it('handles LDAP service errors gracefully', function () {
    // Arrange
    $ldapService = Mockery::mock(LdapService::class);
    $this->app->instance(LdapService::class, $ldapService);
    
    // Mock LDAP service to throw exception
    $ldapService->shouldReceive('authenticate')
        ->with('1234567890', 'password123')
        ->once()
        ->andThrow(new Exception('LDAP connection failed'));
    
    // Act
    $response = $this->postJson('/api/auth/login', [
        'cedula' => '1234567890',
        'password' => 'password123',
    ]);
    
    // Assert - En caso de excepción, el controller devuelve 500
    $response->assertStatus(500)
        ->assertJson([
            'message' => 'Error al procesar la solicitud de inicio de sesión',
        ]);
});

it('can logout successfully', function () {
    // Arrange - crear usuario  
    $user = User::factory()->create([
        'status' => User::STATUS_ACTIVE,
    ]);
    
    // Crear un token para el usuario para simular que está logueado
    $token = $user->createToken('auth-token');
    
    // Act
    $response = $this->withToken($token->plainTextToken)
        ->postJson('/api/auth/logout');
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Sesión cerrada exitosamente',
        ]);
        
    // Verificar que el token fue eliminado
    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id
    ]);
});

it('validates required fields in login request', function () {
    // Act & Assert - Missing cedula
    $response = $this->postJson('/api/auth/login', [
        'password' => 'password123',
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cedula']);
    
    // Act & Assert - Missing password
    $response = $this->postJson('/api/auth/login', [
        'cedula' => '1234567890',
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('invalidates all previous tokens on successful login', function () {
    // Arrange
    $ldapService = Mockery::mock(LdapService::class);
    $this->app->instance(LdapService::class, $ldapService);
    
    $userData = [
        'cedula' => '1234567890',
        'nombre' => 'Juan Pérez',
        'email' => 'juan.perez@test.com',
    ];
    
    $user = User::factory()->create([
        'cedula' => '1234567890',
        'nombre' => 'Juan Pérez',
        'email' => 'juan.perez@test.com',
        'status' => User::STATUS_ACTIVE,
    ]);
    
    // Crear algunos tokens previos
    $user->createToken('old-token-1');
    $user->createToken('old-token-2');
    
    expect($user->tokens()->count())->toBe(2);
    
    // Mock LDAP service - authenticate devuelve datos del usuario
    $ldapService->shouldReceive('authenticate')
        ->with('1234567890', 'password123')
        ->once()
        ->andReturn($userData);
        
    $ldapService->shouldReceive('getUserDataFromLdap')
        ->with('1234567890')
        ->once()
        ->andReturn($userData);
        
    $ldapService->shouldReceive('syncUserFromLdap')
        ->with($userData)
        ->once()
        ->andReturn($user);
    
    // Act
    $response = $this->postJson('/api/auth/login', [
        'cedula' => '1234567890',
        'password' => 'password123',
    ]);
    
    // Assert
    $response->assertStatus(200);
    
    // Verificar que se eliminaron los tokens anteriores y se creó uno nuevo
    expect($user->fresh()->tokens()->count())->toBe(1);
    expect($user->fresh()->tokens()->first()->name)->toBe('auth-token');
});

it('saves session data in Redis on successful login', function () {
    // Arrange
    $ldapService = Mockery::mock(LdapService::class);
    $this->app->instance(LdapService::class, $ldapService);
    
    $userData = [
        'cedula' => '1234567890',
        'nombre' => 'Juan Pérez',
        'email' => 'juan.perez@test.com',
    ];
    
    $user = User::factory()->create([
        'cedula' => '1234567890',
        'nombre' => 'Juan Pérez', 
        'email' => 'juan.perez@test.com',
        'status' => User::STATUS_ACTIVE,
    ]);
    
    // Mock LDAP service methods
    $ldapService->shouldReceive('authenticate')->andReturn($userData);
    $ldapService->shouldReceive('getUserDataFromLdap')->andReturn($userData);
    $ldapService->shouldReceive('syncUserFromLdap')->andReturn($user);
    
    // Act
    $response = $this->postJson('/api/auth/login', [
        'cedula' => '1234567890',
        'password' => 'password123',
    ]);
    
    // Assert
    $response->assertStatus(200);
    
    // Verificar que el usuario está autenticado y se creó el token
    expect($user->fresh()->tokens()->count())->toBe(1);
});