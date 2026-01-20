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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->longText('instructions');
            $table->unsignedInteger('servings')->default(1);
            $table->string('flavor_profile');
            $table->json('meal_types')->default(json_encode([]));
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('prep_time_minutes')->nullable();
            $table->unsignedInteger('cook_time_minutes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
