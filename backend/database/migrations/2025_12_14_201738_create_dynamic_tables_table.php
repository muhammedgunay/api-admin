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
        Schema::create('dynamic_tables', function (Blueprint $table) {
            $table->id();
        
            // Gerçek DB tablo adı
            $table->string('name')->unique(); // users, orders, invoices
        
            // Frontend'de görünen isim
            $table->string('label'); // Kullanıcılar, Siparişler
        
            // Tablo aktif mi?
            $table->boolean('is_active')->default(true);
        
            // Menü sırası (drag & drop)
            $table->integer('order_index')->default(0);
        
            // Kim oluşturdu
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_tables');
    }
};
