<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('uploads an image and returns the url', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/uploads/images', [
        'image' => UploadedFile::fake()->image('step-photo.jpg', 800, 600),
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['url']);

    $url = $response->json('url');
    expect($url)->toStartWith('/storage/recipe-images/');
});

it('rejects non-image files', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/uploads/images', [
        'image' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});

it('rejects images over 2MB', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/uploads/images', [
        'image' => UploadedFile::fake()->image('huge.jpg')->size(3000),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});

it('requires authentication', function () {
    $response = $this->postJson('/uploads/images', [
        'image' => UploadedFile::fake()->image('photo.jpg'),
    ]);

    $response->assertUnauthorized();
});
