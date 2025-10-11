<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ActionType;

class ActionTypeFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_an_action_type()
    {
        $actionType = ActionType::factory()->create([
            'descripcion' => 'Tipo de Acción 2',
        ]);

        $found = ActionType::where('descripcion', 'Tipo de Acción 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Tipo de Acción 2', $found->descripcion);
    }
}

