<?php

use App\Models\User;

test('appearance page displays current theme', function () {
    $user = User::factory()->create(['theme' => 'blush-pink']);

    $response = $this
        ->actingAs($user)
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Appearance')
        ->has('currentTheme')
        ->where('currentTheme', 'blush-pink')
    );
});

test('appearance page displays default theme when user has no theme set', function () {
    $user = User::factory()->create(['theme' => null]);

    $response = $this
        ->actingAs($user)
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('currentTheme', 'default')
    );
});

test('user can update theme to blush-pink', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('appearance.update'), [
            'theme' => 'blush-pink',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    expect($user->refresh()->theme)->toBe('blush-pink');
});

test('user can update theme to default', function () {
    $user = User::factory()->create(['theme' => 'blush-pink']);

    $response = $this
        ->actingAs($user)
        ->patch(route('appearance.update'), [
            'theme' => 'default',
        ]);

    $response->assertSessionHasNoErrors();

    expect($user->refresh()->theme)->toBeNull();
});

test('theme update rejects invalid themes', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('appearance.update'), [
            'theme' => 'invalid-theme',
        ]);

    $response->assertSessionHasErrors('theme');
});

test('theme is required', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('appearance.update'), []);

    $response->assertSessionHasErrors('theme');
});

test('unauthenticated users cannot access appearance page', function () {
    $response = $this->get(route('appearance.edit'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot update theme', function () {
    $response = $this->patch(route('appearance.update'), [
        'theme' => 'blush-pink',
    ]);

    $response->assertRedirect(route('login'));
});
