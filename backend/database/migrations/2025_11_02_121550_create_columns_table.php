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
        Schema::create('columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            $table->string('name'); // örn: price
            $table->string('display_name'); // örn: Fiyat
            $table->string('type')->default('string'); // string, integer, boolean, date, vs.
            $table->boolean('is_visible')->default(true); // kullanıcıya gösterilsin mi?
            $table->boolean('is_editable')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('columns');
    }
};
