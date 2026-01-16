<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PermissionSet;

class AdminPermissionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'permissions' => 'required|array'
        ]);

        return PermissionSet::create([
            'name' => $request->name,
            'permissions' => $request->permissions
        ]);
    }

    public function index()
    {
        return PermissionSet::all();
    }

    public function show($id)
    {
        return PermissionSet::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'permissions' => 'required|array'
        ]);

        $permissionSet = PermissionSet::findOrFail($id);
        $permissionSet->update([
            'name' => $request->name,
            'permissions' => $request->permissions
        ]);

        return $permissionSet;
    }

    public function destroy($id)
    {
        $permissionSet = PermissionSet::findOrFail($id);
        $permissionSet->delete();

        return response()->json(['message' => 'Permission set deleted successfully']);
    }
}
