<?php

use App\Models\Comment;
use App\Models\Evidence;
use App\Models\User;

it('creates a comment', function () {
    // Prueba de creación de comentario polimórfico
    $evidence = Evidence::factory()->create();
    $comment = Comment::factory()->for($evidence, 'commentable')->create([
        'texto' => 'Comentario de prueba',
    ]);
    $this->assertDatabaseHas('COMENTARIO', [
        'texto' => 'Comentario de prueba',
        'commentable_type' => Evidence::class,
        'commentable_id' => $evidence->evidencia_id,
    ]);
});

it('requires texto field', function () {
    // Prueba de validación: campo texto es obligatorio
    $evidence = Evidence::factory()->create();
    Comment::factory()->for($evidence, 'commentable')->create(['texto' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a comment', function () {
    // Prueba de actualización de comentario
    $evidence = Evidence::factory()->create();
    $comment = Comment::factory()->for($evidence, 'commentable')->create(['texto' => 'Original']);
    $comment->update(['texto' => 'Actualizado']);
    $this->assertDatabaseHas('COMENTARIO', ['texto' => 'Actualizado']);
});

it('deletes a comment', function () {
    // Prueba de eliminación de comentario
    $evidence = Evidence::factory()->create();
    $comment = Comment::factory()->for($evidence, 'commentable')->create();
    $comment->delete();
    $this->assertDatabaseMissing('COMENTARIO', ['comentario_id' => $comment->comentario_id]);
});

it('belongs to user', function () {
    $user = User::factory()->create();
    $evidence = Evidence::factory()->create();
    $comment = Comment::factory()->for($evidence, 'commentable')->create([
        'usuario_id' => $user->usuario_id
    ]);
    
    expect($comment->user)->toBeInstanceOf(User::class);
    expect($comment->user->usuario_id)->toBe($user->usuario_id);
});

it('has polymorphic commentable relation', function () {
    $evidence = Evidence::factory()->create();
    $comment = Comment::factory()->for($evidence, 'commentable')->create();
    
    expect($comment->commentable)->toBeInstanceOf(Evidence::class);
    expect($comment->commentable->evidencia_id)->toBe($evidence->evidencia_id);
    expect($comment->commentable_type)->toBe(Evidence::class);
    expect($comment->commentable_id)->toBe($evidence->evidencia_id);
});

