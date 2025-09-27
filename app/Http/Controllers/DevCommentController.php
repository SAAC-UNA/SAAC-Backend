<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;

class DevCommentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario_id' => 'required|exists:USUARIO,usuario_id',
            'texto' => 'required|string',
            'fecha_creacion' => 'nullable|date',
        ]);

        $comment = Comment::create($data);
        return response()->json($comment, 201);
    }
}
