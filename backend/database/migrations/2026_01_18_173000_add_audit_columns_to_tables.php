<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tüm tabloları 'tables' tablosundan al
        $tables = DB::table('tables')->get();

        foreach ($tables as $table) {
            $tableName = $table->name;

            // 1. Gerçek tabloya kolonları ekle (eğer yoksa)
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $blueprint) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'created_by')) {
                        $blueprint->unsignedBigInteger('created_by')->nullable()->after('id');
                    }
                    if (!Schema::hasColumn($tableName, 'updated_by')) {
                        $blueprint->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                    }
                });
            }

            // 2. 'columns' tablosuna meta verileri ekle
            $existingColumns = DB::table('columns')
                ->where('table_id', $table->id)
                ->pluck('name')
                ->toArray();

            $columnsToAdd = [];

            if (!in_array('created_by', $existingColumns)) {
                $columnsToAdd[] = [
                    'table_id' => $table->id,
                    'name' => 'created_by',
                    'display_name' => 'Oluşturan',
                    'type' => 'integer',
                    'is_visible' => false,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!in_array('updated_by', $existingColumns)) {
                $columnsToAdd[] = [
                    'table_id' => $table->id,
                    'name' => 'updated_by',
                    'display_name' => 'Güncelleyen',
                    'type' => 'integer',
                    'is_visible' => false,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($columnsToAdd)) {
                DB::table('columns')->insert($columnsToAdd);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri alma işlemi karmaşık olabilir ve veri kaybına yol açabilir, 
        // bu yüzden basit tutuyoruz. İstenirse kolonlar silinebilir.
        // Amaç ileriye dönük özellik eklemek.
    }
};
