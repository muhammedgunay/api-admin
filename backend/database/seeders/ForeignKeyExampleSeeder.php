<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ForeignKeyExampleSeeder extends Seeder
{
    /**
     * Örnek Foreign Key ilişkisi oluştur
     * 
     * Bu seeder:
     * 1. posts tablosu oluşturur
     * 2. tables'a posts kaydı ekler
     * 3. columns'a kolonları ekler (UI'da kullanılan tablo)
     * 4. user_id kolonunu foreign key olarak tanımlar
     */
    public function run(): void
    {
        // 1. Posts tablosunu oluştur (eğer yoksa)
        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function ($table) {
                $table->id();
                $table->string('title');
                $table->text('content')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                
                // Foreign key constraint ekle
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
            
            echo "✅ posts tablosu oluşturuldu\n";
        }

        // 2. tables'a posts kaydı ekle (UI'da kullanılan tablo)
        $tableId = DB::table('tables')->insertGetId([
            'name' => 'posts',
            'display_name' => 'Yazılar',
            'description' => 'Blog yazıları',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✅ tables'a posts kaydı eklendi (ID: $tableId)\n";

        // 3. Kolonları ekle (columns tablosuna - UI'da kullanılan)
        $columns = [
            [
                'name' => 'id',
                'display_name' => 'ID',
                'type' => 'bigint',
                'is_visible' => true,
                'is_editable' => false,
                'is_required' => false,
                'is_foreign_key' => false,
            ],
            [
                'name' => 'title',
                'display_name' => 'Başlık',
                'type' => 'string',
                'is_visible' => true,
                'is_editable' => true,
                'is_required' => true,
                'is_foreign_key' => false,
            ],
            [
                'name' => 'content',
                'display_name' => 'İçerik',
                'type' => 'text',
                'is_visible' => true,
                'is_editable' => true,
                'is_required' => false,
                'is_foreign_key' => false,
            ],
            [
                'name' => 'user_id',
                'display_name' => 'Yazar',
                'type' => 'bigint',
                'is_visible' => true,
                'is_editable' => true,
                'is_required' => true,
                'is_foreign_key' => true, // 🔗 FOREIGN KEY
                'foreign_table' => 'users',
                'foreign_column' => 'id',
                'foreign_display_column' => 'name',
                'on_delete' => 'cascade',
                'on_update' => 'cascade',
            ],
            [
                'name' => 'created_at',
                'display_name' => 'Oluşturulma Tarihi',
                'type' => 'timestamp',
                'is_visible' => true,
                'is_editable' => false,
                'is_required' => false,
                'is_foreign_key' => false,
            ],
            [
                'name' => 'updated_at',
                'display_name' => 'Güncellenme Tarihi',
                'type' => 'timestamp',
                'is_visible' => false,
                'is_editable' => false,
                'is_required' => false,
                'is_foreign_key' => false,
            ],
        ];

        foreach ($columns as $column) {
            DB::table('columns')->insert([
                'table_id' => $tableId,
                'name' => $column['name'],
                'display_name' => $column['display_name'],
                'type' => $column['type'],
                'is_visible' => $column['is_visible'],
                'is_editable' => $column['is_editable'],
                'is_required' => $column['is_required'],
                'is_foreign_key' => $column['is_foreign_key'],
                'foreign_table' => $column['foreign_table'] ?? null,
                'foreign_column' => $column['foreign_column'] ?? null,
                'foreign_display_column' => $column['foreign_display_column'] ?? null,
                'on_delete' => $column['on_delete'] ?? 'restrict',
                'on_update' => $column['on_update'] ?? 'cascade',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        echo "✅ " . count($columns) . " kolon eklendi\n";
        
        // 4. Örnek veri ekle
        $users = DB::table('users')->limit(3)->pluck('id');
        
        if ($users->count() > 0) {
            foreach ($users as $userId) {
                DB::table('posts')->insert([
                    'title' => 'Örnek Yazı - Kullanıcı ' . $userId,
                    'content' => 'Bu bir örnek blog yazısıdır.',
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
            
            echo "✅ " . $users->count() . " örnek yazı eklendi\n";
        }
        
        echo "\n🎉 Foreign Key örneği başarıyla oluşturuldu!\n";
        echo "👉 Frontend'de /posts sayfasını ziyaret edin\n";
        echo "👉 Yeni yazı eklerken 'Yazar' alanı dropdown olarak görünecek\n";
    }
}
