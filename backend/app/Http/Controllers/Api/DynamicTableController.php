<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\DynamicTable;
use Spatie\Permission\Models\Permission;

class DynamicTableController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|alpha_dash|unique:dynamic_tables,name',
            'label' => 'required|string'
        ]);

        // 1️⃣ meta tablo kaydı
        $table = DynamicTable::create([
            'name' => $request->name,
            'label' => $request->label,
            'created_by' => auth()->id()
        ]);

        // 2️⃣ gerçek DB tablosu
        Schema::create($request->name, function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        // 3️⃣ permission üret
        $this->createPermissions($request->name);

        return response()->json($table, 201);
    }

    private function createPermissions(string $table)
    {
        $actions = ['list', 'view', 'create', 'update', 'delete'];

        foreach ($actions as $action) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => "{$table}.{$action}",
                'guard_name' => 'web'
            ]);
        }
    }
}

