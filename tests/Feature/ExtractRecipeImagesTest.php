<?php

use App\Actions\ExtractRecipeImages;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Http::fake([
        'https://example.com/photo1.jpg' => Http::response('fake-image-content-1', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/photo2.jpg' => Http::response('fake-image-content-2', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/photo3.jpg' => Http::response('fake-image-content-3', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/step1.jpg' => Http::response('fake-step-image-1', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/step2.jpg' => Http::response('fake-step-image-2', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/og-image.jpg' => Http::response('fake-og-image', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/big.jpg' => Http::response('fake-big-image', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/content-img.jpg' => Http::response('fake-content-image', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/images/relative.jpg' => Http::response('fake-relative-image', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/not-image.txt' => Http::response('not an image', 200, ['Content-Type' => 'text/plain']),
    ]);
});

it('returns structured result with main and all keys', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {"@type": "Recipe", "name": "Test", "image": "https://example.com/photo1.jpg"}
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result)->toHaveKeys(['main', 'all']);
    expect($result['main'])->toContain('/storage/recipe-images/imported/');
    expect($result['all'])->toHaveCount(1);
});

it('extracts images from json-ld recipe schema', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "image": ["https://example.com/photo1.jpg", "https://example.com/photo2.jpg"],
        "recipeInstructions": [
            {"@type": "HowToStep", "text": "Step 1", "image": "https://example.com/step1.jpg"},
            {"@type": "HowToStep", "text": "Step 2", "image": "https://example.com/step2.jpg"}
        ]
    }
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(4);
    expect($result['main'])->toContain('/storage/recipe-images/imported/');

    Storage::disk('public')->assertExists('recipe-images/imported/'.md5('fake-image-content-1').'.jpg');
    Storage::disk('public')->assertExists('recipe-images/imported/'.md5('fake-image-content-2').'.jpg');
    Storage::disk('public')->assertExists('recipe-images/imported/'.md5('fake-step-image-1').'.jpg');
    Storage::disk('public')->assertExists('recipe-images/imported/'.md5('fake-step-image-2').'.jpg');
});

it('extracts images from json-ld graph format', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {
        "@graph": [
            {"@type": "WebPage", "name": "My Site"},
            {"@type": "Recipe", "name": "Test", "image": "https://example.com/photo1.jpg"}
        ]
    }
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(1);
    Storage::disk('public')->assertExists('recipe-images/imported/'.md5('fake-image-content-1').'.jpg');
});

it('handles @type as array containing Recipe', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {"@type": ["Recipe"], "name": "Test", "image": "https://example.com/photo1.jpg"}
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(1);
    expect($result['main'])->toContain('/storage/recipe-images/imported/');
});

it('handles @type as array in graph format', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {
        "@graph": [
            {"@type": "WebPage", "name": "My Site"},
            {"@type": ["Recipe"], "name": "Test", "image": "https://example.com/photo1.jpg"}
        ]
    }
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(1);
});

it('falls back to og:image when no json-ld', function () {
    $html = <<<'HTML'
    <html><head>
    <meta property="og:image" content="https://example.com/og-image.jpg" />
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(1);
    Storage::disk('public')->assertExists('recipe-images/imported/'.md5('fake-og-image').'.jpg');
});

it('falls back to content img tags', function () {
    $html = <<<'HTML'
    <html><head></head><body>
    <img src="https://example.com/content-img.jpg" width="800" height="600" />
    </body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(1);
    Storage::disk('public')->assertExists('recipe-images/imported/'.md5('fake-content-image').'.jpg');
});

it('limits to max 10 images', function () {
    $fakes = [];
    $images = [];
    for ($i = 1; $i <= 15; $i++) {
        $url = "https://example.com/img{$i}.jpg";
        $images[] = $url;
        $fakes[$url] = Http::response("fake-img-{$i}", 200, ['Content-Type' => 'image/jpeg']);
    }
    Http::fake($fakes);

    $imageJson = json_encode($images);

    $html = <<<HTML
    <html><head>
    <script type="application/ld+json">
    {"@type": "Recipe", "name": "Test", "image": {$imageJson}}
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(10);
});

it('deduplicates image urls', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test",
        "image": ["https://example.com/photo1.jpg", "https://example.com/photo1.jpg", "https://example.com/photo2.jpg"]
    }
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(2);
});

it('handles download failures gracefully', function () {
    Http::fake([
        'https://example.com/good.jpg' => Http::response('good-image', 200, ['Content-Type' => 'image/jpeg']),
        'https://example.com/fail.jpg' => Http::response('', 500),
    ]);

    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {"@type": "Recipe", "name": "Test", "image": ["https://example.com/fail.jpg", "https://example.com/good.jpg"]}
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toHaveCount(1);
    Storage::disk('public')->assertExists('recipe-images/imported/'.md5('good-image').'.jpg');
});

it('rejects non-image content type responses', function () {
    Http::fake([
        'https://example.com/not-image.txt' => Http::response('not an image', 200, ['Content-Type' => 'text/plain']),
    ]);

    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {"@type": "Recipe", "name": "Test", "image": "https://example.com/not-image.txt"}
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toBeEmpty();
    expect($result['main'])->toBeNull();
});

it('rejects oversized files', function () {
    Http::fake([
        'https://example.com/huge.jpg' => Http::response(str_repeat('x', 6 * 1024 * 1024), 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {"@type": "Recipe", "name": "Test", "image": "https://example.com/huge.jpg"}
    </script>
    </head><body></body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toBeEmpty();
    expect($result['main'])->toBeNull();
});

it('resolves relative urls against source url', function () {
    $html = <<<'HTML'
    <html><head></head><body>
    <img src="/images/relative.jpg" width="800" height="600" />
    </body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipes/my-recipe');

    expect($result['all'])->toHaveCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://example.com/images/relative.jpg');
});

it('returns empty structured result for imageless html', function () {
    $html = '<html><head></head><body><p>No images here.</p></body></html>';

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toBeEmpty();
    expect($result['main'])->toBeNull();
});

it('skips small images likely to be icons or tracking pixels', function () {
    $html = <<<'HTML'
    <html><head></head><body>
    <img src="https://example.com/pixel.jpg" width="1" height="1" />
    <img src="https://example.com/icon.jpg" width="32" height="32" />
    </body></html>
    HTML;

    $result = app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    expect($result['all'])->toBeEmpty();
    expect($result['main'])->toBeNull();
});

it('sends user-agent header when downloading images', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {"@type": "Recipe", "name": "Test", "image": "https://example.com/photo1.jpg"}
    </script>
    </head><body></body></html>
    HTML;

    app(ExtractRecipeImages::class)($html, 'https://example.com/recipe');

    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', 'Mozilla/5.0 (compatible; MealPlannerBot/1.0)'));
});
