<?php

use App\Models\University;

it('can create and retrieve a university', function () {
    $university = University::factory()->create([
        'nombre' => 'Universidad Estatal',
    ]);

    $found = University::where('nombre', 'Universidad Estatal')->first();
    expect($found)->not->toBeNull();
    expect($found->nombre)->toBe('Universidad Estatal');
});

