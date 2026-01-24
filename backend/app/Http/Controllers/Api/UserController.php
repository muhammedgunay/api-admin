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
    
        // 🔥 ADMIN = FULL ACCESS (no filters)
        if ($user->hasRole('admin')) {
            return [
                'columns' => DB::getSchemaBuilder()->getColumnListing($table),
                'filters' => [] // Admin has no filters
            ];
        }
    
        $permissions = $user->permissions();
        
        $tablePermission = $permissions[$table][$action] ?? null;
        
        if (!$tablePermission) {
            return null;
        }
        
        // Filtreleri al
        $filters = $this->getFiltersForPermission($user, $table, $action);
        
        return [
            'columns' => $tablePermission['columns'] ?? [],
            'filters' => $filters
        ];
    }
    
    /**
     * Kullanıcının permission set'inden filtreleri al
     */
    private function getFiltersForPermission($user, string $table, string $action): array
    {
        $role = $user->roles()->first();
        if (!$role || !$role->permissionSet) {
            return [];
        }
        
        $filters = $role->permissionSet
            ->filters()
            ->where('permission_set_filters.table_name', $table)
            ->where('permission_set_filters.action', $action)
            ->where('filters.is_active', true)
            ->orderBy('permission_set_filters.priority', 'desc')
            ->get();
        
        return $filters->map(fn($f) => $f->toSqlWhere($user))->toArray();
    }

    // 📋 Kolon bilgilerini getir
    public function getColumns()
    {
        $table = 'users';
        $perm = $this->getPermission($table, 'list');
        abort_if(!$perm, 403);

        // Password ve remember_token kolonlarını her zaman çıkar
        $columns = array_filter($perm['columns'], function($col) {
            return !in_array($col, ['password', 'remember_token', 'email_verified_at']);
        });

        return response()->json([
            'columns' => array_values($columns)
        ]);
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
        $query = DB::table($table)->select($allowedColumns);
        
        // 🔥 Filtreleri uygula (Row-level security)
        if (!empty($perm['filters'])) {
            foreach ($perm['filters'] as $filter) {
                $query->whereRaw($filter);
            }
        }
        
        $users = $query->paginate(10);
        
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

    public function store(Request $request)
    {
        $table = 'users';
        $perm = $this->getPermission($table, 'create');
        abort_if(!$perm, 403);

        // Dynamic validation: just check required fields exist if you want, 
        // or rely on DB errors. For now, we'll try to use the allowed columns.
        // If specific validation is needed, it should be done here, but user asked for dynamic.
        
        $data = $request->only($perm['columns']);
        
        // Audit kolonları kullanıcıdan gelmemeli, model tarafından otomatik doldurulur
        $auditColumns = ['created_by', 'updated_by', 'created_at', 'updated_at'];
        foreach ($auditColumns as $auditCol) {
            unset($data[$auditCol]);
        }
        
        // Password handling needs to be explicit if it's in the data
        // (Assuming 'password' is in columns, though strictly it might be hidden in some views. 
        // However for creation it's usually needed).
        // Since we are using Eloquent's create, we need to make sure 'password' is treated correctly 
        // if it's passed.
        
        // If password is NOT in columns (e.g. security), we might not be able to set it.
        // But assuming admin has full access or 'create' perm includes it.
        
        // Let's rely on $data being what we pass to User::create.
        // Validation of unique email etc will be thrown by DB if we don't validate here.
        // User requested: "database kolonlarına göre yapalım" -> relying on Schema/DB.

        // We still use User::create to leverage model events/casting if any, 
        // but we filter by permission columns.
        
        // Special case: Roles (not a column in users table)
        // We'll extract roles separately if present in request, as it is not a DB column in 'users'.
        
        $user = User::create($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return response()->json([
            'message' => 'Kullanıcı başarıyla oluşturuldu.',
            'user' => $user
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $table = 'users';
        $perm = $this->getPermission($table, 'update');
        abort_if(!$perm, 403);

        // Filter data based on allowed columns
        $data = $request->only($perm['columns']);

        // Audit kolonları kullanıcıdan gelmemeli, model tarafından otomatik doldurulur
        $auditColumns = ['created_by', 'updated_by', 'created_at', 'updated_at'];
        foreach ($auditColumns as $auditCol) {
            unset($data[$auditCol]);
        }

        // Handle password update logic (if Empty, don't update)
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }
        
        // Update user with filtered data
        $user->update($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return response()->json([
            'message' => 'Kullanıcı başarıyla güncellendi.',
            'user' => $user
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Kullanıcı başarıyla silindi.']);
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
