<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\PermissionSet;
use Illuminate\Http\Request;

class RolePermissionSetController extends Controller
{
    public function assign(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_set_id' => 'required|exists:permission_sets,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $role->permission_set_id = $request->permission_set_id;
        $role->save();

        return response()->json([
            'message' => 'Permission set assigned',
            'role' => $role->load('permissionSet'),
        ]);
    }
}
