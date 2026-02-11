<?php

use App\Models\AccreditationCycle;

it('can create and retrieve an accreditation cycle', function () {
    $cycle = AccreditationCycle::factory()->create([
        'nombre' => 'Ciclo 2026',
    ]);

    $found = AccreditationCycle::where('nombre', 'Ciclo 2026')->first();
    expect($found)->not->toBeNull();
    expect($found->nombre)->toBe('Ciclo 2026');
});

