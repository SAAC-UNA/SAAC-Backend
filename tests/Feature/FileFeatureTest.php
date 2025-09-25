<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\File;

class FileFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_file()
    {
        $file = File::factory()->create([
            'nombre' => 'Archivo 2',
        ]);

        $found = File::where('nombre', 'Archivo 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Archivo 2', $found->nombre);
    }
}
