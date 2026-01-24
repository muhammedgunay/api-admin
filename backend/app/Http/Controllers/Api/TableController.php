<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TableController extends Controller
{
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

        // Tablo adını snake_case formatına çevir ve geçerli hale getir
        $tableName = $validated['name'];
        
        // Tablo adının geçerli olduğunu kontrol et (sadece harf, rakam ve alt çizgi)
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $tableName)) {
            return response()->json([
                'message' => 'Tablo adı geçersiz. Sadece küçük harf, rakam ve alt çizgi kullanılabilir.',
                'errors' => ['name' => ['Tablo adı geçersiz format']]
            ], 422);
        }

        // 1️⃣ Meta tablo kaydı oluştur
        $table = Table::create($validated);

        // 2️⃣ Gerçek veritabanı tablosunu oluştur
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->unsignedBigInteger('created_by')->nullable(); // Kaydı oluşturan
                $blueprint->unsignedBigInteger('updated_by')->nullable(); // Son güncelleyen
                $blueprint->timestamps();
            });

            // 3️⃣ Otomatik eklenen kolonları 'columns' tablosuna da kaydet
            $defaultColumns = [
                [
                    'table_id' => $table->id,
                    'name' => 'id',
                    'display_name' => 'ID',
                    'type' => 'integer',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'table_id' => $table->id,
                    'name' => 'created_by',
                    'display_name' => 'Oluşturan',
                    'type' => 'integer',
                    'is_visible' => false, // Listede varsayılan gizli olabilir
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'table_id' => $table->id,
                    'name' => 'updated_by',
                    'display_name' => 'Güncelleyen',
                    'type' => 'integer',
                    'is_visible' => false,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'table_id' => $table->id,
                    'name' => 'created_at',
                    'display_name' => 'Oluşturulma Tarihi',
                    'type' => 'datetime',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'table_id' => $table->id,
                    'name' => 'updated_at',
                    'display_name' => 'Güncellenme Tarihi',
                    'type' => 'datetime',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            \DB::table('columns')->insert($defaultColumns);
        }

        return response()->json([
            'message' => 'Tablo başarıyla oluşturuldu',
            'table' => $table->load('columns') // Kolonlarla birlikte döndür
        ], 201);
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

    // 🔧 Tabloyu düzelt (veritabanı tablosunu oluştur)
    public function fix(Table $table)
    {
        if (!Schema::hasTable($table->name)) {
            Schema::create($table->name, function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->unsignedBigInteger('created_by')->nullable();
                $blueprint->unsignedBigInteger('updated_by')->nullable();
                $blueprint->timestamps();
            });

            // Otomatik eklenen kolonları 'columns' tablosuna da kaydet
            $defaultColumns = [
                [
                    'table_id' => $table->id,
                    'name' => 'id',
                    'display_name' => 'ID',
                    'type' => 'integer',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'table_id' => $table->id,
                    'name' => 'created_by',
                    'display_name' => 'Oluşturan',
                    'type' => 'integer',
                    'is_visible' => false,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'table_id' => $table->id,
                    'name' => 'updated_by',
                    'display_name' => 'Güncelleyen',
                    'type' => 'integer',
                    'is_visible' => false,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'table_id' => $table->id,
                    'name' => 'created_at',
                    'display_name' => 'Oluşturulma Tarihi',
                    'type' => 'datetime',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'table_id' => $table->id,
                    'name' => 'updated_at',
                    'display_name' => 'Güncellenme Tarihi',
                    'type' => 'datetime',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            \DB::table('columns')->insert($defaultColumns);
            
            return response()->json([
                'message' => 'Veritabanı tablosu oluşturuldu',
                'table' => $table->load('columns')
            ]);
        }
        
        return response()->json([
            'message' => 'Tablo zaten mevcut',
            'table' => $table
        ]);
    }

    // ❌ Sil
    public function destroy(Table $table)
    {
        // Gerçek veritabanı tablosunu da sil
        if (Schema::hasTable($table->name)) {
            Schema::dropIfExists($table->name);
        }

        // Meta kaydı sil (cascade ile kolonlar da silinecek)
        $table->delete();

        return response()->json(['message' => 'Tablo silindi']);
    }
}
