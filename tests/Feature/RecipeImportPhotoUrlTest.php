<?php

use App\Actions\ParseRecipeFromUrl;

it('extracts og:image from html with property before content', function () {
    $action = new ParseRecipeFromUrl;
    $method = new ReflectionMethod($action, 'extractOgImage');

    $html = '<html><head><meta property="og:image" content="https://example.com/photo.jpg"></head><body></body></html>';

    expect($method->invoke($action, $html))->toBe('https://example.com/photo.jpg');
});

it('extracts og:image from html with content before property', function () {
    $action = new ParseRecipeFromUrl;
    $method = new ReflectionMethod($action, 'extractOgImage');

    $html = '<html><head><meta content="https://example.com/photo.jpg" property="og:image"></head><body></body></html>';

    expect($method->invoke($action, $html))->toBe('https://example.com/photo.jpg');
});

it('returns null when no og:image is present', function () {
    $action = new ParseRecipeFromUrl;
    $method = new ReflectionMethod($action, 'extractOgImage');

    $html = '<html><head><title>No image</title></head><body></body></html>';

    expect($method->invoke($action, $html))->toBeNull();
});
