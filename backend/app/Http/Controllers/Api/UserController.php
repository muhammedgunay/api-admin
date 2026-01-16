<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private function getPermission(string $table, string $action): array|bool|null
    {
        $user = auth()->user();
    
        // 🔥 ADMIN = FULL ACCESS
        if ($user->hasRole('admin')) {
            return [
                'columns' => DB::getSchemaBuilder()->getColumnListing($table)
            ];
        }
    
        $permissions = $user->permissions();
    
        return $permissions[$table][$action] ?? null;
    }

    // 📋 Kullanıcıları listele
    public function index(Request $request)
    {
        $table = 'users';
        $perm = $this->getPermission($table, 'list');
        abort_if(!$perm, 403);
    
        // Password ve remember_token kolonlarını her zaman çıkar
        $allowedColumns = array_filter($perm['columns'], function($col) {
            return !in_array($col, ['password', 'remember_token']);
        });
    
        // Yetkiye göre kolonları seç
        $users = DB::table($table)
            ->select($allowedColumns)
            ->paginate(10);
        
        // Rolleri ekle (eğer id kolonu varsa)
        if (in_array('id', $allowedColumns)) {
            $userIds = collect($users->items())->pluck('id')->toArray();
            
            if (!empty($userIds)) {
                $rolesData = DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $userIds)
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->select('model_id', 'roles.id as role_id', 'roles.name as role_name')
                    ->get()
                    ->groupBy('model_id');
                
                // Her kullanıcıya rollerini ekle
                foreach ($users->items() as $user) {
                    $userRoles = $rolesData->get($user->id, collect());
                    $user->roles = $userRoles->map(function ($role) {
                        return [
                            'id' => $role->role_id,
                            'name' => $role->role_name
                        ];
                    })->toArray();
                }
            }
        }
        
        return response()->json($users);
    }

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
