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
        Schema::create('permission_set_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_set_id');
            $table->unsignedBigInteger('filter_id');
            $table->string('table_name'); // Hangi tablo için geçerli
            $table->string('action'); // Hangi action için (list, create, update, delete)
            $table->integer('priority')->default(0); // Filtre önceliği (yüksek önce uygulanır)
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('permission_set_id')
                ->references('id')
                ->on('permission_sets')
                ->onDelete('cascade');
                
            $table->foreign('filter_id')
                ->references('id')
                ->on('filters')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['permission_set_id', 'table_name', 'action']);
            $table->index('filter_id');
            
            // Unique constraint - aynı permission_set'te aynı filtre bir kez
            $table->unique(['permission_set_id', 'filter_id', 'table_name', 'action'], 'unique_permission_filter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_set_filters');
    }
};
