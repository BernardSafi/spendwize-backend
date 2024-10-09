<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Method to get user details
    public function getUserDetails()
    {
        $user = Auth::user(); // Get the currently authenticated user

        if ($user) {
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
            ]);
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    }

    public function getUserName()
{
    $user = Auth::user(); // Get the currently authenticated user

    if ($user) {
        return response()->json([
            'name' => $user->name,
        ]);
    } else {
        return response()->json(['error' => 'User not authenticated'], 401);
    }
}

}
