<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('stores a recipe with a photo from a URL', function () {
    Config::set('filesystems.default', 'local');
    Storage::fake('local');
    Storage::fake('public');

    $fakeImage = UploadedFile::fake()->image('remote.jpg', 800, 600);

    Http::fake([
        'https://example.com/photo.jpg' => Http::response(
            file_get_contents($fakeImage->getPathname()),
            200,
            ['Content-Type' => 'image/jpeg']
        ),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/recipes', [
        'name' => 'URL Photo Recipe',
        'instructions' => 'Test instructions.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'photo_url' => 'https://example.com/photo.jpg',
    ]);

    $recipe = Recipe::firstOrFail();

    $response->assertRedirect(route('recipes.show', $recipe));
    expect($recipe->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($recipe->photo_path);
});

it('updates a recipe photo from a URL', function () {
    Config::set('filesystems.default', 'local');
    Storage::fake('local');
    Storage::fake('public');

    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create([
        'photo_path' => 'recipes/old.jpg',
    ]);

    Storage::disk('public')->put($recipe->photo_path, 'old');

    $fakeImage = UploadedFile::fake()->image('new-remote.jpg', 800, 600);

    Http::fake([
        'https://example.com/new-photo.jpg' => Http::response(
            file_get_contents($fakeImage->getPathname()),
            200,
            ['Content-Type' => 'image/jpeg']
        ),
    ]);

    $response = $this->actingAs($user)->patch(route('recipes.update', $recipe), [
        'name' => $recipe->name,
        'instructions' => $recipe->instructions,
        'servings' => $recipe->servings,
        'flavor_profile' => $recipe->flavor_profile,
        'photo_url' => 'https://example.com/new-photo.jpg',
    ]);

    $recipe->refresh();

    $response->assertRedirect(route('recipes.show', $recipe));
    expect($recipe->photo_path)->not->toBe('recipes/old.jpg');
    Storage::disk('public')->assertMissing('recipes/old.jpg');
    Storage::disk('public')->assertExists($recipe->photo_path);
});

it('rejects submitting both photo file and photo_url', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/recipes', [
        'name' => 'Both Photo Fields',
        'instructions' => 'Test instructions.',
        'servings' => 2,
        'flavor_profile' => 'Savory',
        'photo' => UploadedFile::fake()->image('local.jpg', 200, 200),
        'photo_url' => 'https://example.com/photo.jpg',
    ]);

    $response->assertSessionHasErrors(['photo', 'photo_url']);
});
