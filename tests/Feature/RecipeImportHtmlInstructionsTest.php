<?php

use App\Actions\ParseRecipeFromUrl;
use App\Models\User;
use Mews\Purifier\Facades\Purifier;

it('passes html instructions through to the response intact', function () {
    $user = User::factory()->create();

    $htmlInstructions = '<h2>Pasta</h2><ol><li>Boil water.</li><li>Cook pasta for <strong>8 minutes</strong>.</li></ol><p><em>Tip: Salt the water generously.</em></p><img src="/storage/recipe-images/imported/abc123.jpg" alt="Pasta">';

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Pasta Recipe',
            'instructions' => $htmlInstructions,
            'servings' => 2,
            'flavor_profile' => 'Savory',
            'meal_types' => ['Dinner'],
            'prep_time_minutes' => 5,
            'cook_time_minutes' => 10,
            'ingredients' => [],
            'photo_path' => null,
            'photo_url' => null,
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/pasta',
    ]);

    $response->assertSuccessful();
    expect($response->json('instructions'))->toBe($htmlInstructions);
});

it('sanitizes script tags from html instructions via purifier', function () {
    $dirty = '<ol><li>Step one.</li></ol><script>alert("xss")</script><p>Final step.</p>';

    $clean = Purifier::clean($dirty);

    expect($clean)->not->toContain('<script>');
    expect($clean)->toContain('<ol>');
    expect($clean)->toContain('Final step.');
});

it('imports successfully with no images extracted', function () {
    $user = User::factory()->create();

    $this->mock(ParseRecipeFromUrl::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn([
            'name' => 'Simple Salad',
            'instructions' => '<ol><li>Chop vegetables.</li><li>Toss with dressing.</li></ol>',
            'servings' => 2,
            'flavor_profile' => 'Fresh',
            'meal_types' => ['Lunch'],
            'prep_time_minutes' => 10,
            'cook_time_minutes' => null,
            'ingredients' => [
                ['name' => 'Lettuce', 'quantity' => '1', 'unit' => 'head', 'note' => null],
            ],
            'photo_path' => null,
            'photo_url' => null,
        ]);

    $response = $this->actingAs($user)->postJson(route('recipes.import'), [
        'url' => 'https://example.com/salad',
    ]);

    $response->assertSuccessful();
    expect($response->json('name'))->toBe('Simple Salad');
    expect($response->json('instructions'))->not->toContain('<img');
    expect($response->json('ingredients'))->toHaveCount(1);
});
