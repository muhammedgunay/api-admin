<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Column;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
            'is_required' => 'boolean',
            // Foreign key alanları
            'is_foreign_key' => 'boolean',
            'foreign_table' => 'nullable|string',
            'foreign_column' => 'nullable|string',
            'foreign_display_column' => 'nullable|string',
            'on_delete' => 'nullable|string|in:cascade,set null,restrict,no action',
            'on_update' => 'nullable|string|in:cascade,set null,restrict,no action',
        ]);

        // 1️⃣ Meta kolon kaydı oluştur
        $column = $table->columns()->create($validated);

        // 2️⃣ Gerçek veritabanı tablosuna kolon ekle
        if (Schema::hasTable($table->name)) {
            Schema::table($table->name, function (Blueprint $blueprint) use ($validated, $table) {
                $columnName = $validated['name'];
                $isRequired = $validated['is_required'] ?? false;
                $isForeignKey = $validated['is_foreign_key'] ?? false;
                
                // Tip eşleştirme
                $col = match ($validated['type']) {
                    'string', 'varchar', 'char' => $blueprint->string($columnName),
                    'text' => $blueprint->text($columnName),
                    'integer', 'int' => $blueprint->integer($columnName),
                    'bigint' => $blueprint->bigInteger($columnName),
                    'smallint' => $blueprint->smallInteger($columnName),
                    'decimal' => $blueprint->decimal($columnName, 10, 2),
                    'float' => $blueprint->float($columnName),
                    'double' => $blueprint->double($columnName),
                    'boolean', 'bool' => $blueprint->boolean($columnName)->default(false),
                    'date' => $blueprint->date($columnName),
                    'datetime', 'timestamp' => $blueprint->dateTime($columnName),
                    'time' => $blueprint->time($columnName),
                    'json' => $blueprint->json($columnName),
                    'email' => $blueprint->string($columnName),
                    default => $blueprint->string($columnName),
                };
                
                // Zorunluluk kontrolü (boolean için varsayılan değer varsa nullable olmasın)
                if (!$isRequired && $validated['type'] !== 'boolean') {
                    $col->nullable();
                }
                
                // 🔗 FOREIGN KEY CONSTRAINT OLUŞTUR
                if ($isForeignKey && !empty($validated['foreign_table']) && !empty($validated['foreign_column'])) {
                    $foreignTable = $validated['foreign_table'];
                    $foreignColumn = $validated['foreign_column'];
                    $onDelete = $validated['on_delete'] ?? 'restrict';
                    $onUpdate = $validated['on_update'] ?? 'cascade';
                    
                    // Foreign tablo var mı kontrol et
                    if (Schema::hasTable($foreignTable)) {
                        // Constraint adı oluştur
                        $constraintName = 'fk_' . $table->name . '_' . $columnName;
                        
                        // Foreign key constraint ekle
                        $blueprint->foreign($columnName, $constraintName)
                            ->references($foreignColumn)
                            ->on($foreignTable)
                            ->onDelete($onDelete)
                            ->onUpdate($onUpdate);
                    }
                }
            });
        }

        return response()->json([
            'message' => 'Kolon eklendi ve veritabanı tablosu güncellendi' . 
                        ($validated['is_foreign_key'] ?? false ? ' (Foreign key constraint oluşturuldu)' : ''),
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
        // 1️⃣ Gerçek veritabanı tablosundan kolon sil
        if (Schema::hasTable($table->name) && Schema::hasColumn($table->name, $column->name)) {
            Schema::table($table->name, function (Blueprint $blueprint) use ($column, $table) {
                // 🔗 Eğer foreign key ise, önce constraint'i sil
                if ($column->is_foreign_key) {
                    $constraintName = 'fk_' . $table->name . '_' . $column->name;
                    
                    try {
                        $blueprint->dropForeign($constraintName);
                    } catch (\Exception $e) {
                        // Constraint bulunamazsa devam et
                        // (Bazı durumlarda constraint adı farklı olabilir)
                    }
                }
                
                // Kolonu sil
                $blueprint->dropColumn($column->name);
            });
        }
        
        // 2️⃣ Meta kolon kaydını sil
        $column->delete();
        
        return response()->json(['message' => 'Kolon silindi ve veritabanı tablosu güncellendi']);
    }
}
