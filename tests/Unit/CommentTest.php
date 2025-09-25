<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Comment;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_comment()
    {
        $comment = \App\Models\Comment::factory()->create([
            'texto' => 'Comentario de prueba',
        ]);

        $this->assertDatabaseHas('COMENTARIO', [
            'texto' => 'Comentario de prueba',
        ]);
    }
}
