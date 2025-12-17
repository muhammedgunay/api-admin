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
        Schema::create('dynamic_columns', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('dynamic_table_id')
                ->constrained('dynamic_tables')
                ->cascadeOnDelete();
        
            // Gerçek kolon adı
            $table->string('name'); // email, price, status
        
            // Frontend label
            $table->string('label'); // E-posta, Fiyat
        
            // Veri tipi (senin 2. madden)
            $table->string('type'); 
            // string, integer, boolean, text, date, json ...
        
            // Form & liste kontrolü
            $table->boolean('is_nullable')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_sortable')->default(false);
            $table->boolean('is_filterable')->default(false);
        
            // Kolon sırası (drag & drop)
            $table->integer('order_index')->default(0);
        
            // Ek ayarlar (default, enum, length, relation vs.)
            $table->json('meta')->nullable();
        
            $table->timestamps();
        
            $table->unique(['dynamic_table_id', 'name']);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_columns');
    }
};
