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
        // Prueba de creación de comentario
        $comment = \App\Models\Comment::factory()->create([
            'texto' => 'Comentario de prueba',
        ]);
        $this->assertDatabaseHas('COMENTARIO', [
            'texto' => 'Comentario de prueba',
        ]);
    }

    /** @test */
    public function it_requires_texto_field()
    {
        // Prueba de validación: campo texto es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\Comment::factory()->create(['texto' => null]);
    }

    /** @test */
    public function it_updates_a_comment()
    {
        // Prueba de actualización de comentario
        $comment = \App\Models\Comment::factory()->create(['texto' => 'Original']);
        $comment->update(['texto' => 'Actualizado']);
        $this->assertDatabaseHas('COMENTARIO', ['texto' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_comment()
    {
        // Prueba de eliminación de comentario
        $comment = \App\Models\Comment::factory()->create();
        $comment->delete();
        $this->assertDatabaseMissing('COMENTARIO', ['comentario_id' => $comment->comentario_id]);
    }
}

