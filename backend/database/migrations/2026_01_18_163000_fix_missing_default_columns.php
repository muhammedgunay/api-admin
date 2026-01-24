<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mevcut tüm tabloları al
        $tables = DB::table('tables')->get();

        foreach ($tables as $table) {
            // Bu tablonun columns kaydında id, created_at, updated_at var mı kontrol et
            $existingColumns = DB::table('columns')
                ->where('table_id', $table->id)
                ->pluck('name')
                ->toArray();

            $defaultColumns = [];

            // id yoksa ekle
            if (!in_array('id', $existingColumns)) {
                $defaultColumns[] = [
                    'table_id' => $table->id,
                    'name' => 'id',
                    'display_name' => 'ID',
                    'type' => 'integer',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // created_at yoksa ekle
            if (!in_array('created_at', $existingColumns)) {
                $defaultColumns[] = [
                    'table_id' => $table->id,
                    'name' => 'created_at',
                    'display_name' => 'Oluşturulma Tarihi',
                    'type' => 'datetime',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // updated_at yoksa ekle
            if (!in_array('updated_at', $existingColumns)) {
                $defaultColumns[] = [
                    'table_id' => $table->id,
                    'name' => 'updated_at',
                    'display_name' => 'Güncellenme Tarihi',
                    'type' => 'datetime',
                    'is_visible' => true,
                    'is_editable' => false,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Eksik kolonları ekle
            if (!empty($defaultColumns)) {
                DB::table('columns')->insert($defaultColumns);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri alma işlemi yapmıyoruz çünkü bu bir düzeltme migration'ı
    }
};
