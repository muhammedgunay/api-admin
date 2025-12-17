<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    // 🔒 Middleware
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // 📋 Listele
    public function index()
    {
        $tables = Table::with('columns')->get();
        return response()->json($tables);
    }

    // ➕ Ekle
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:tables',
            'display_name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $table = Table::create($validated);

        return response()->json([
            'message' => 'Tablo başarıyla oluşturuldu',
            'table' => $table
        ]);
    }

    // ✏️ Güncelle
    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'display_name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $table->update($validated);

        return response()->json([
            'message' => 'Tablo başarıyla güncellendi',
            'table' => $table
        ]);
    }

    // ❌ Sil
    public function destroy(Table $table)
    {
        $table->delete();

        return response()->json(['message' => 'Tablo silindi']);
    }
}
