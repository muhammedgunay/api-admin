<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RolePermissionSet;

class AdminRolePermissionController extends Controller
{
    public function assign(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_set_id' => 'required|exists:permission_sets,id'
        ]);

        return RolePermissionSet::updateOrCreate(
            ['role_id' => $request->role_id],
            ['permission_set_id' => $request->permission_set_id]
        );
    }
}

