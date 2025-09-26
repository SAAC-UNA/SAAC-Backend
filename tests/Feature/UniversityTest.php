<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\University;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class UniversityTest extends TestCase
{
    use RefreshDatabase;

   
    public function puede_listar_universidades()
    {
        University::factory()->count(3)->create();

        $this->getJson('/api/universidades')
            ->assertOk()
            ->assertJsonCount(3);
    }

    
    public function puede_ver_una_universidad()
    {
        $u = University::factory()->create();

        $this->getJson("/api/universidades/{$u->getKey()}")
            ->assertOk()
            ->assertJsonFragment(['nombre' => $u->nombre]);
    }
}
