<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;

it('center-crops a non-square recipe photo to a square when storing', function () {
    Config::set('filesystems.default', 'local');
    Storage::fake('local');
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/recipes', [
        'name' => 'Sheet Pan Chicken',
        'instructions' => 'Roast until done.',
        'servings' => 4,
        'flavor_profile' => 'Savory',
        'meal_types' => ['Dinner'],
        'photo' => UploadedFile::fake()->image('chicken.jpg', 1600, 900),
    ]);

    $recipe = Recipe::firstOrFail();

    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($recipe->photo_path);

    $storedPath = Storage::disk('public')->path($recipe->photo_path);
    $image = Image::load($storedPath);

    expect($image->getWidth())->toBe($image->getHeight());
    expect($image->getWidth())->toBe(900);
});
