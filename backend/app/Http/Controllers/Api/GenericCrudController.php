<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\DynamicTable;

class GenericCrudController extends Controller
{
    private function getTable(string $name): DynamicTable
    {
        return DynamicTable::with('columns')
            ->where('name', $name)
            ->firstOrFail();
    }

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
    

    // 📄 LIST
    public function index(Request $request, string $table)
    {
        $perm = $this->getPermission($table, 'list');
        abort_if(!$perm, 403);
    
        return DB::table($table)
            ->select($perm['columns'])
            ->paginate(10);
    }
    

    // ➕ CREATE
    public function store(Request $request, string $table)
    {
        $perm = $this->getPermission($table, 'create');
        abort_if(!$perm, 403);
    
        $data = $request->only($perm['columns']);
        
        // GÜVENLİK: created_by ve updated_by alanlarını request'ten çıkar
        // (Kullanıcı bunları manipüle edemez, backend otomatik doldurur)
        unset($data['created_by'], $data['updated_by'], $data['created_at'], $data['updated_at']);
        
        // created_by alanını backend'de doldur
        if (Schema::hasColumn($table, 'created_by')) {
            $data['created_by'] = auth()->id();
        }
        
        // updated_by alanını backend'de doldur
        if (Schema::hasColumn($table, 'updated_by')) {
            $data['updated_by'] = auth()->id();
        }
        
        // created_at alanını backend'de doldur
        if (Schema::hasColumn($table, 'created_at')) {
            $data['created_at'] = now();
        }
        
        // updated_at alanını backend'de doldur
        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = now();
        }
    
        $id = DB::table($table)->insertGetId($data);
    
        return DB::table($table)->find($id);
    }
    

    // 👁 VIEW
    public function show(string $table, int $id)
    {
        $perm = $this->getPermission($table, 'view');
        abort_if(!$perm, 403);

        return DB::table($table)->find($id);
    }

    // ✏️ UPDATE
    public function update(Request $request, string $table, int $id)
    {
        $perm = $this->getPermission($table, 'update');
        abort_if(!$perm, 403);
    
        $data = $request->only($perm['columns']);
        
        // GÜVENLİK: created_by ve updated_by alanlarını request'ten çıkar
        unset($data['created_by'], $data['updated_by'], $data['created_at'], $data['updated_at']);
        
        // updated_by alanını backend'de doldur
        if (Schema::hasColumn($table, 'updated_by')) {
            $data['updated_by'] = auth()->id();
        }
        
        // updated_at alanını backend'de doldur
        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = now();
        }
    
        DB::table($table)->where('id', $id)->update($data);
    
        return DB::table($table)->find($id);
    }
    

    // 🗑 DELETE
    public function destroy(string $table, int $id)
    {
        $perm = $this->getPermission($table, 'delete');
        abort_if(!$perm, 403);
    
        DB::table($table)->where('id', $id)->delete();
    
        return response()->json(['deleted' => true]);
    }
    
}
