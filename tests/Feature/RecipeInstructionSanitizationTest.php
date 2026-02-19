<?php

use App\Models\User;

it('strips script tags from instructions on create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Test Recipe',
        'instructions' => '<p>Step one</p><script>alert("xss")</script><p>Step two</p>',
        'servings' => 4,
        'flavor_profile' => 'Savory',
    ]);

    $recipe = $user->recipes()->latest()->first();
    expect($recipe->instructions)->toBe('<p>Step one</p><p>Step two</p>');
});

it('allows supported HTML tags in instructions', function () {
    $user = User::factory()->create();

    $html = '<h2>Sauce</h2><p><strong>Bold</strong> and <em>italic</em> and <u>underline</u></p><ul><li>Item</li></ul><ol><li>Step</li></ol><blockquote>Tip</blockquote><hr>';

    $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'HTML Recipe',
        'instructions' => $html,
        'servings' => 2,
        'flavor_profile' => 'Sweet',
    ]);

    $recipe = $user->recipes()->latest()->first();
    expect($recipe->instructions)
        ->toContain('<strong>Bold</strong>')
        ->toContain('<em>italic</em>')
        ->toContain('<u>underline</u>')
        ->toContain('<ul>')
        ->toContain('<ol>')
        ->toContain('<blockquote>')
        ->toContain('<h2>');
});

it('strips script tags from instructions on update', function () {
    $user = User::factory()->create();
    $recipe = $user->recipes()->create([
        'name' => 'Old Recipe',
        'instructions' => '<p>Original</p>',
        'servings' => 2,
        'flavor_profile' => 'Savory',
    ]);

    $this->actingAs($user)->patch(route('recipes.update', $recipe), [
        'instructions' => '<p>Updated</p><script>alert("xss")</script>',
    ]);

    expect($recipe->fresh()->instructions)->toBe('<p>Updated</p>');
});
