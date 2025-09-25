<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ActionType;

class ActionTypeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_action_type()
    {
        $actionType = ActionType::factory()->create([
            'descripcion' => 'Tipo de Acción',
        ]);

        $this->assertDatabaseHas('TIPO_ACCION', [
            'descripcion' => 'Tipo de Acción',
        ]);
    }
}
