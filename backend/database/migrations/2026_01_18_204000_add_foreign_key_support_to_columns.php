<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('columns', function (Blueprint $table) {
            // Foreign key ilişkisi var mı?
            $table->boolean('is_foreign_key')->default(false)->after('type');
            
            // Hangi tabloya referans veriyor?
            $table->string('foreign_table')->nullable()->after('is_foreign_key');
            
            // Hangi kolona referans veriyor? (genelde 'id')
            $table->string('foreign_column')->nullable()->after('foreign_table');
            
            // Gösterilecek kolon (örn: users tablosundan 'name' göster)
            $table->string('foreign_display_column')->nullable()->after('foreign_column');
            
            // Silme davranışı: cascade, set null, restrict
            $table->enum('on_delete', ['cascade', 'set null', 'restrict', 'no action'])
                ->default('restrict')
                ->after('foreign_display_column');
            
            // Güncelleme davranışı
            $table->enum('on_update', ['cascade', 'set null', 'restrict', 'no action'])
                ->default('cascade')
                ->after('on_delete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('columns', function (Blueprint $table) {
            $table->dropColumn([
                'is_foreign_key',
                'foreign_table',
                'foreign_column',
                'foreign_display_column',
                'on_delete',
                'on_update'
            ]);
        });
    }
};
