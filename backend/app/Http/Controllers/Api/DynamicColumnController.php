<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DynamicColumnController extends Controller
{
    public function store(Request $request, $tableId)
    {
        $request->validate([
            'name'  => 'required|string|alpha_dash',
            'label' => 'required|string',
            'type'  => 'required|string'
        ]);
    
        $table = DynamicTable::findOrFail($tableId);
    
        // 1️⃣ meta kolon kaydı
        $column = $table->columns()->create($request->all());
    
        // 2️⃣ DB kolon ekleme
        Schema::table($table->name, function (Blueprint $blueprint) use ($request) {
            match ($request->type) {
                'string'  => $blueprint->string($request->name)->nullable(),
                'integer' => $blueprint->integer($request->name)->nullable(),
                'boolean' => $blueprint->boolean($request->name)->default(false),
                'text'    => $blueprint->text($request->name)->nullable(),
                'date'    => $blueprint->date($request->name)->nullable(),
                default   => $blueprint->string($request->name)->nullable(),
            };
        });
    
        return response()->json($column, 201);
    }
    
}
