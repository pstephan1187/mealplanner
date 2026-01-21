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
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->foreignId('grocery_store_id')->nullable()->after('ingredient_id')->constrained()->nullOnDelete();
            $table->foreignId('grocery_store_section_id')->nullable()->after('grocery_store_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grocery_store_section_id');
            $table->dropConstrainedForeignId('grocery_store_id');
        });
    }
};
