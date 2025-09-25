<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\File;

class FileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_file()
    {
        $file = File::factory()->create([
            'nombre' => 'Archivo 1',
        ]);

        $this->assertDatabaseHas('ARCHIVO', [
            'nombre' => 'Archivo 1',
        ]);
    }
}
