<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return Role::with('permissionSet')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permission_set_id' => 'nullable|exists:permission_sets,id',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'permission_set_id' => $request->permission_set_id,
            'guard_name' => 'web',
        ]);
        
        return response()->json($role->load('permissionSet'), 201);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permission_set_id' => 'nullable|exists:permission_sets,id',
        ]);

        $role->update([
            'name' => $request->name,
            'permission_set_id' => $request->permission_set_id,
            'guard_name' => 'web',
        ]);
        
        return response()->json($role->load('permissionSet'));
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Role deleted']);
    }
}
