<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recipe_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->longText('instructions')->nullable();
            $table->timestamps();

            $table->index(['recipe_id', 'sort_order']);
        });

        // SQLite-safe pivot restructure: rename → create → copy → drop
        Schema::rename('ingredient_recipe', 'ingredient_recipe_old');

        Schema::create('ingredient_recipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_section_id')->nullable()->constrained('recipe_sections')->nullOnDelete();
            $table->decimal('quantity', 8, 2);
            $table->string('unit');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        DB::table('ingredient_recipe')->insertUsing(
            ['recipe_id', 'ingredient_id', 'quantity', 'unit', 'note', 'created_at', 'updated_at'],
            DB::table('ingredient_recipe_old')->select(
                'recipe_id', 'ingredient_id', 'quantity', 'unit', 'note', 'created_at', 'updated_at'
            )
        );

        Schema::dropIfExists('ingredient_recipe_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate old pivot structure
        Schema::rename('ingredient_recipe', 'ingredient_recipe_new');

        Schema::create('ingredient_recipe', function (Blueprint $table) {
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2);
            $table->string('unit');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->primary(['recipe_id', 'ingredient_id']);
        });

        // Copy data back (duplicates for same ingredient will be lost)
        DB::statement('
            INSERT INTO ingredient_recipe (recipe_id, ingredient_id, quantity, unit, note, created_at, updated_at)
            SELECT recipe_id, ingredient_id, quantity, unit, note, created_at, updated_at
            FROM ingredient_recipe_new
            GROUP BY recipe_id, ingredient_id
        ');

        Schema::dropIfExists('ingredient_recipe_new');
        Schema::dropIfExists('recipe_sections');
    }
};
