<?php

use App\Models\User;

it('can create and retrieve a user', function () {
    $user = User::factory()->create([
        'nombre' => 'Maria Lopez',
        'cedula' => '987654321',
        'email' => 'maria@example.com',
    ]);

    $found = User::where('cedula', '987654321')->first();
    expect($found)->not->toBeNull();
    expect($found->nombre)->toBe('Maria Lopez');
    expect($found->email)->toBe('maria@example.com');
});

