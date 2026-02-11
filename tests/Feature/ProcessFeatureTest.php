<?php

use App\Models\Process;
use App\Models\AccreditationCycle;

it('can create and retrieve a process', function () {
    $accreditationCycle = AccreditationCycle::factory()->create();
    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
    ]);

    $found = Process::find($process->proceso_id);
    expect($found)->not->toBeNull();
    expect($found->proceso_id)->toBe($process->proceso_id);
});
