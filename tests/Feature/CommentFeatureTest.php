<?php

use App\Models\Comment;
use App\Models\Evidence;
use App\Models\User;

it('can create and retrieve a comment', function () {
    $evidence = Evidence::factory()->create();
    $comment = Comment::factory()->for($evidence, 'commentable')->create([
        'texto' => 'Comentario funcional',
    ]);

    $found = Comment::where('texto', 'Comentario funcional')->first();
    expect($found)->not->toBeNull();
    expect($found->texto)->toBe('Comentario funcional');
});

it('puede filtrar por usuario', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $evidence = Evidence::factory()->create();
    
    Comment::factory()->count(3)->for($evidence, 'commentable')->create(['usuario_id' => $user1->usuario_id]);
    Comment::factory()->count(2)->for($evidence, 'commentable')->create(['usuario_id' => $user2->usuario_id]);
    
    $user1Comments = Comment::where('usuario_id', $user1->usuario_id)->get();
    $user2Comments = Comment::where('usuario_id', $user2->usuario_id)->get();
    
    expect($user1Comments)->toHaveCount(3);
    expect($user2Comments)->toHaveCount(2);
});

it('puede filtrar por entidad comentable', function () {
    $evidence1 = Evidence::factory()->create();
    $evidence2 = Evidence::factory()->create();
    
    Comment::factory()->count(4)->for($evidence1, 'commentable')->create();
    Comment::factory()->count(2)->for($evidence2, 'commentable')->create();
    
    $evidence1Comments = Comment::where('commentable_type', Evidence::class)
        ->where('commentable_id', $evidence1->evidencia_id)
        ->get();
    $evidence2Comments = Comment::where('commentable_type', Evidence::class)
        ->where('commentable_id', $evidence2->evidencia_id)
        ->get();
    
    expect($evidence1Comments)->toHaveCount(4);
    expect($evidence2Comments)->toHaveCount(2);
});

it('puede cargar relaciones eager loading', function () {
    $evidence = Evidence::factory()->create();
    $comment = Comment::factory()->for($evidence, 'commentable')->create();
    
    $loaded = Comment::with(['user', 'commentable'])->find($comment->comentario_id);
    
    expect($loaded->relationLoaded('user'))->toBeTrue();
    expect($loaded->relationLoaded('commentable'))->toBeTrue();
    expect($loaded->user)->toBeInstanceOf(User::class);
    expect($loaded->commentable)->toBeInstanceOf(Evidence::class);
});

