<?php

use App\Models\User;

it('creates a user', function () {
    $user = User::factory()->create([
        'nombre' => 'Juan Perez',
        'cedula' => '123456789',
        'email' => 'juan@example.com',
    ]);
    
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->nombre)->toBe('Juan Perez')
        ->and($user->cedula)->toBe('123456789')
        ->and($user->email)->toBe('juan@example.com');
    
    $this->assertDatabaseHas('USUARIO', [
        'nombre' => 'Juan Perez',
        'cedula' => '123456789',
        'email' => 'juan@example.com',
    ]);
});

it('requires nombre field', function () {
    User::factory()->create(['nombre' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('requires email field', function () {
    User::factory()->create(['email' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a user', function () {
    $user = User::factory()->create(['nombre' => 'Original']);
    $user->update(['nombre' => 'Actualizado']);
    
    $this->assertDatabaseHas('USUARIO', ['nombre' => 'Actualizado']);
});

it('deletes a user', function () {
    $user = User::factory()->create();
    $userId = $user->usuario_id;
    $user->delete();
    
    $this->assertDatabaseMissing('USUARIO', ['usuario_id' => $userId]);
});
