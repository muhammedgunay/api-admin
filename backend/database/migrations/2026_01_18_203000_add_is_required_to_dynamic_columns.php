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
        // dynamic_columns tablosuna is_required ekle (eğer yoksa)
        if (!Schema::hasColumn('dynamic_columns', 'is_required')) {
            Schema::table('dynamic_columns', function (Blueprint $table) {
                $table->boolean('is_required')->default(false)->after('is_editable');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dynamic_columns', function (Blueprint $table) {
            if (Schema::hasColumn('dynamic_columns', 'is_required')) {
                $table->dropColumn('is_required');
            }
        });
    }
};
