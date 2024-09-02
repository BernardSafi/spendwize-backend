<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return response()->json(['message' => 'User registered successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        // Check if the user exists
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            // User does not exist
            return response()->json(['error' => 'User does not exist'], 404);
        }
    
        // User exists, now check the password
        if (!Auth::attempt($request->only('email', 'password'))) {
            // Password is incorrect
            return response()->json(['error' => 'Wrong password'], 401);
        }
    
        // Generate token for the authenticated user
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json(['token' => $token]);
    }
}