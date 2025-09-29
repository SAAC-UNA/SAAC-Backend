<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class DevUserController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'cedula' => 'required|string|unique:USUARIO,cedula',
            'nombre' => 'required|string|max:80',
            'email'  => 'required|email',
        ]);

        $user = User::create($data);
        return response()->json($user, 201);
    }
}
