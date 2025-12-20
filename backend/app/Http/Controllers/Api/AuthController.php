<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Models\Role as AppRole;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        /*
        $isAdmin = $user->hasRole('admin');

        if ($isAdmin) {
            return response()->json([
                'token' => $token,
                'user' => $user,
                'permissions' => [
                    '__admin__' => true
                ]
            ]);
        }

        */



        $spatieRole = $user->roles()->first();

        $role = AppRole::with('permissionSet')
            ->where('id', $spatieRole->id)
            ->first();
    
        return response()->json([
            'token' => $token,
            'user' => $user,
            'permissions' => $role?->permissionSet?->permissions ?? []
        ]);
    }
    

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
