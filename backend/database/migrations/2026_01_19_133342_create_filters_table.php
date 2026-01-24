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
        Schema::create('filters', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Filtre adı (ör: "Sadece Kendi Kayıtları")
            $table->text('description')->nullable(); // Açıklama
            $table->string('table_name'); // Hangi tablo için (ör: "users")
            $table->enum('filter_type', ['sql', 'json'])->default('json'); // Filtre tipi
            
            // SQL Tabanlı Filtre
            $table->text('sql_where_clause')->nullable(); // WHERE clause
            
            // JSON Tabanlı Filtre (Rule Builder)
            $table->json('json_rules')->nullable(); // Yapılandırılmış kurallar
            
            // Meta
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('table_name');
            $table->index('is_active');
            
            // Foreign Keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filters');
    }
};
