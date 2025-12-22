<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Column;
use App\Models\Table;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    // 📋 Tüm kolonları getir (tablo bilgileri ile)
    public function all()
    {
        $columns = Column::with('table')->get();
        return response()->json($columns);
    }

    // 📋 Belirli bir tablonun kolonlarını getir
    public function index(Table $table)
    {
        return response()->json($table->columns);
    }

    // ➕ Kolon ekle
    public function store(Request $request, Table $table)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'display_name' => 'required|string',
            'type' => 'required|string',
            'is_visible' => 'boolean',
            'is_editable' => 'boolean',
        ]);

        $column = $table->columns()->create($validated);

        return response()->json([
            'message' => 'Kolon eklendi',
            'column' => $column
        ]);
    }

    // ✏️ Kolon güncelle
    public function update(Request $request, Table $table, Column $column)
    {
        $validated = $request->validate([
            'display_name' => 'required|string',
            'type' => 'required|string',
            'is_visible' => 'boolean',
            'is_editable' => 'boolean',
        ]);

        $column->update($validated);

        return response()->json([
            'message' => 'Kolon güncellendi',
            'column' => $column
        ]);
    }

    // ❌ Kolon sil
    public function destroy(Table $table, Column $column)
    {
        $column->delete();
        return response()->json(['message' => 'Kolon silindi']);
    }
}
