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
}
