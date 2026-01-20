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
        Schema::table('recipes', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('meal_plans', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('shopping_lists', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->index('ingredient_id');
        });

        Schema::table('meal_plan_recipes', function (Blueprint $table) {
            $table->index('recipe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('meal_plans', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('shopping_lists', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->dropIndex(['ingredient_id']);
        });

        Schema::table('meal_plan_recipes', function (Blueprint $table) {
            $table->dropIndex(['recipe_id']);
        });
    }
};
