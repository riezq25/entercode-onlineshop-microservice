<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['email', 'password']);

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'User not found. Check your credentials',
            ], 401);
        }

        $token = auth()->user()->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login success',
            'data' => [
                'token' => $token,
                'user' => auth()->user(),
            ],
        ]);
    }

    function logout()
    {
        $token = auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout success',
        ]);
    }

    function me()
    {
        return response()->json([
            'message' => 'Success',
            'data' => auth()->user(),
        ]);
    }

    function revoke()
    {
        auth()->user()->tokens()->delete();
        $token = auth()->user()->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Tokens revoked',
            'data' => [
                'token' => $token,
                'user' => auth()->user(),
            ],
        ]);
    }

    function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
            'role' => 'required|in:penjual,pembeli',
        ]);

        $user = User::create($request->only(['name', 'email', 'password', 'role']));

        return response()->json([
            'message' => 'Register success',
            'data' => $user,
        ]);
    }
}
