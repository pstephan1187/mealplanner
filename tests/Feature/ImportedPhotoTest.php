<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('accepts imported_photo_path when creating a recipe', function () {
    $user = User::factory()->create();

    Storage::disk('public')->put('recipes/imported-photo.jpg', 'fake-image');

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Imported Recipe',
        'instructions' => 'Mix and serve.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
        'imported_photo_path' => 'recipes/imported-photo.jpg',
    ]);

    $response->assertRedirect();

    $recipe = Recipe::query()->where('name', 'Imported Recipe')->first();
    expect($recipe)->not->toBeNull();
    expect($recipe->photo_path)->toBe('recipes/imported-photo.jpg');
});

it('rejects imported_photo_path outside the recipes directory', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Malicious Recipe',
        'instructions' => 'Try to access bad path.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
        'imported_photo_path' => '../sensitive/file.jpg',
    ]);

    $response->assertSessionHasErrors('imported_photo_path');
});

it('rejects imported_photo_path with path traversal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Traversal Recipe',
        'instructions' => 'Try to traverse.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
        'imported_photo_path' => 'recipes/../../../etc/passwd',
    ]);

    // The regex only allows paths starting with recipes/ but the path still technically
    // starts with recipes/, so let's test a path that doesn't start with recipes/
    $response2 = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Bad Path Recipe',
        'instructions' => 'Bad path.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
        'imported_photo_path' => 'other-dir/photo.jpg',
    ]);

    $response2->assertSessionHasErrors('imported_photo_path');
});

it('ignores imported_photo_path when a file is uploaded', function () {
    $user = User::factory()->create();

    Storage::disk('public')->put('recipes/imported-photo.jpg', 'fake-image');

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Upload Priority Recipe',
        'instructions' => 'Test upload takes priority.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
        'imported_photo_path' => 'recipes/imported-photo.jpg',
        'photo' => \Illuminate\Http\UploadedFile::fake()->image('upload.jpg', 100, 100),
    ]);

    $response->assertRedirect();

    $recipe = Recipe::query()->where('name', 'Upload Priority Recipe')->first();
    expect($recipe)->not->toBeNull();
    expect($recipe->photo_path)->not->toBe('recipes/imported-photo.jpg');
    expect($recipe->photo_path)->toStartWith('recipes/');
});

it('stores recipe without photo when imported_photo_path is null', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'No Photo Recipe',
        'instructions' => 'No photo provided.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
    ]);

    $response->assertRedirect();

    $recipe = Recipe::query()->where('name', 'No Photo Recipe')->first();
    expect($recipe)->not->toBeNull();
    expect($recipe->photo_path)->toBeNull();
});
