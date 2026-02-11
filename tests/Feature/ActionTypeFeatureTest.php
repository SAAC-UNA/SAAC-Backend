<?php

use App\Models\ActionType;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // Crear rol Superusuario si no existe
    $role = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);
    $this->user->assignRole($role);
    
    Sanctum::actingAs($this->user);
});

it('puede listar tipos de acción', function () {
    ActionType::factory()->count(3)->create();
    
    $response = $this->getJson('/api/bitacora/tipos-accion');
    
    $response->assertStatus(200)
        ->assertJsonCount(3);
});

it('puede crear y recuperar un tipo de acción', function () {
    $actionType = ActionType::factory()->create([
        'descripcion' => 'Tipo de Acción Test',
    ]);

    $found = ActionType::where('descripcion', 'Tipo de Acción Test')->first();
    expect($found)->not->toBeNull();
    expect($found->descripcion)->toBe('Tipo de Acción Test');
});

