<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DynamicTable;
use App\Models\Role as AppRole;

class MeController extends Controller
{
    public function permissions(Request $request)
    {
        $user = $request->user();
        //dd($user->hasRole('admin'));
        
        if ($user->hasRole('admin')) {
            return response()->json([
                'permissions' => $this->buildAdminPermissions()
            ]);
        }

        $spatieRole = $user->roles()->first();
        
        $role = AppRole::with('permissionSet')
            ->where('id', $spatieRole->id)
            ->first();
        //dd($role);
        if (!$role || !$role->permissionSet) {
            return response()->json([]);
        }

        return response()->json(
            $role->permissionSet->permissions
        );
    }

    private function buildAdminPermissions(): array
    {
        $tables = DynamicTable::with('columns')->get();
        $result = [];

        foreach ($tables as $table) {
            $columns = $table->columns->pluck('name')->toArray();

            $result[$table->name] = [
                'list'   => ['columns' => $columns],
                'view'   => ['columns' => $columns],
                'create' => ['columns' => $columns],
                'update' => ['columns' => $columns],
                'delete' => true,
            ];
        }

        return $result;
    }

}
