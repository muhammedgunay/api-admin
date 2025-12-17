<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function assignRole(User $user, Request $request)
    {
        $request->validate(['role' => 'required|string|exists:roles,name']);
        $user->assignRole($request->role);
        return response()->json([
            'message' => "Role '{$request->role}' assigned to user {$user->name}.",
            'roles' => $user->roles
        ]);
    }

    public function givePermission(User $user, Request $request)
    {
        $request->validate(['permission' => 'required|string|exists:permissions,name']);
        $user->givePermissionTo($request->permission);
        return response()->json([
            'message' => "Permission '{$request->permission}' given to user {$user->name}.",
            'permissions' => $user->permissions
        ]);
    }
}
