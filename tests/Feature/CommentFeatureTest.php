<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Comment;

class CommentFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_comment()
    {
        $comment = Comment::factory()->create([
            'texto' => 'Comentario funcional',
        ]);

        $found = Comment::where('texto', 'Comentario funcional')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Comentario funcional', $found->texto);
    }
}
