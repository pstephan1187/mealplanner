<?php

declare(strict_types=1);

namespace App\Actions;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExtractRecipeImages
{
    protected const MAX_IMAGES = 10;

    protected const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    protected const DOWNLOAD_TIMEOUT = 10;

    protected const MIN_IMAGE_DIMENSION = 50;

    /**
     * Extract recipe images from HTML, download them, and return structured result.
     *
     * @return array{main: string|null, all: array<int, string>}
     */
    public function __invoke(string $html, string $sourceUrl): array
    {
        $imageUrls = $this->extractImageUrls($html, $sourceUrl);

        if (empty($imageUrls)) {
            return ['main' => null, 'all' => []];
        }

        $stored = $this->downloadAndStoreImages($imageUrls);

        return [
            'main' => $stored[0] ?? null,
            'all' => $stored,
        ];
    }

    /**
     * Extract image URLs using a priority-based fallback chain.
     *
     * @return array<int, string>
     */
    protected function extractImageUrls(string $html, string $sourceUrl): array
    {
        $urls = $this->extractFromJsonLd($html);

        if (empty($urls)) {
            $urls = $this->extractFromOgImage($html);
        }

        if (empty($urls)) {
            $urls = $this->extractFromImgTags($html);
        }

        $urls = $this->resolveUrls($urls, $sourceUrl);
        $urls = array_unique($urls);

        return array_slice($urls, 0, self::MAX_IMAGES);
    }

    /**
     * Extract image URLs from JSON-LD Recipe schema.
     *
     * @return array<int, string>
     */
    protected function extractFromJsonLd(string $html): array
    {
        $urls = [];

        if (! preg_match_all('/<script\s+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $jsonString) {
            $data = json_decode($jsonString, true);

            if (! is_array($data)) {
                continue;
            }

            $recipes = $this->findRecipeNodes($data);

            foreach ($recipes as $recipe) {
                if (isset($recipe['image'])) {
                    $urls = array_merge($urls, $this->normalizeImageField($recipe['image']));
                }

                foreach ($recipe['recipeInstructions'] ?? [] as $instruction) {
                    if (is_array($instruction) && isset($instruction['image'])) {
                        $urls = array_merge($urls, $this->normalizeImageField($instruction['image']));
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Find Recipe nodes in JSON-LD data, handling @graph arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function findRecipeNodes(array $data): array
    {
        if ($this->isRecipeType($data)) {
            return [$data];
        }

        $recipes = [];

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $node) {
                if (is_array($node) && $this->isRecipeType($node)) {
                    $recipes[] = $node;
                }
            }
        }

        return $recipes;
    }

    /**
     * Check if a JSON-LD node is a Recipe type, handling both string and array @type.
     */
    protected function isRecipeType(array $data): bool
    {
        $type = $data['@type'] ?? null;

        if ($type === 'Recipe') {
            return true;
        }

        if (is_array($type) && in_array('Recipe', $type)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize a JSON-LD image field to an array of URL strings.
     *
     * @return array<int, string>
     */
    protected function normalizeImageField(mixed $image): array
    {
        if (is_string($image)) {
            return [$image];
        }

        if (is_array($image)) {
            if (isset($image['url'])) {
                return [(string) $image['url']];
            }

            return array_values(array_filter(
                array_map(function ($item) {
                    if (is_string($item)) {
                        return $item;
                    }

                    if (is_array($item) && isset($item['url'])) {
                        return (string) $item['url'];
                    }

                    return null;
                }, $image)
            ));
        }

        return [];
    }

    /**
     * Extract image URL from og:image meta tag.
     *
     * @return array<int, string>
     */
    protected function extractFromOgImage(string $html): array
    {
        if (preg_match('/<meta\s+[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\'][^>]*\/?>/si', $html, $match)) {
            return [$match[1]];
        }

        if (preg_match('/<meta\s+[^>]*content=["\']([^"\']+)["\'][^>]*property=["\']og:image["\'][^>]*\/?>/si', $html, $match)) {
            return [$match[1]];
        }

        return [];
    }

    /**
     * Extract image URLs from <img> tags in body content, skipping icons/tracking pixels.
     *
     * @return array<int, string>
     */
    protected function extractFromImgTags(string $html): array
    {
        libxml_use_internal_errors(true);

        $doc = new DOMDocument;
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);

        $xpath = new DOMXPath($doc);
        $imgs = $xpath->query('//img[@src]');

        if ($imgs === false) {
            return [];
        }

        $urls = [];

        foreach ($imgs as $img) {
            $src = $img->getAttribute('src');

            if ($this->isLikelyContentImage($img, $src)) {
                $urls[] = $src;
            }
        }

        libxml_clear_errors();

        return $urls;
    }

    /**
     * Determine if an img element is likely a content image (not icon/pixel).
     */
    protected function isLikelyContentImage(\DOMElement $img, string $src): bool
    {
        if (empty($src) || str_starts_with($src, 'data:')) {
            return false;
        }

        $ext = strtolower(pathinfo(parse_url($src, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if (in_array($ext, ['gif', 'svg', 'ico'])) {
            return false;
        }

        $width = $img->getAttribute('width');
        $height = $img->getAttribute('height');

        if ($width && (int) $width < self::MIN_IMAGE_DIMENSION) {
            return false;
        }

        if ($height && (int) $height < self::MIN_IMAGE_DIMENSION) {
            return false;
        }

        return true;
    }

    /**
     * Resolve relative URLs against the source URL.
     *
     * @param  array<int, string>  $urls
     * @return array<int, string>
     */
    protected function resolveUrls(array $urls, string $sourceUrl): array
    {
        $baseParts = parse_url($sourceUrl);
        $baseScheme = $baseParts['scheme'] ?? 'https';
        $baseHost = $baseParts['host'] ?? '';
        $baseOrigin = "{$baseScheme}://{$baseHost}";

        return array_map(function (string $url) use ($baseOrigin, $sourceUrl): string {
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                return $url;
            }

            if (str_starts_with($url, '//')) {
                return 'https:'.$url;
            }

            if (str_starts_with($url, '/')) {
                return $baseOrigin.$url;
            }

            $basePath = substr($sourceUrl, 0, (int) strrpos($sourceUrl, '/') + 1);

            return $basePath.$url;
        }, $urls);
    }

    /**
     * Download images and store them locally, returning the local URLs.
     *
     * @param  array<int, string>  $urls
     * @return array<int, string>
     */
    protected function downloadAndStoreImages(array $urls): array
    {
        $stored = [];

        foreach ($urls as $url) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; MealPlannerBot/1.0)',
                ])->timeout(self::DOWNLOAD_TIMEOUT)->get($url);

                if (! $response->successful()) {
                    continue;
                }

                $contentType = $response->header('Content-Type') ?? '';
                if (! str_starts_with($contentType, 'image/')) {
                    continue;
                }

                $body = $response->body();
                if (strlen($body) > self::MAX_FILE_SIZE) {
                    continue;
                }

                $ext = $this->extensionFromContentType($contentType);
                $hash = md5($body);
                $path = "recipe-images/imported/{$hash}.{$ext}";

                Storage::disk('public')->put($path, $body);

                $stored[] = '/storage/'.$path;
            } catch (\Throwable $e) {
                Log::debug('Failed to download recipe image', ['url' => $url, 'error' => $e->getMessage()]);

                continue;
            }
        }

        return $stored;
    }

    /**
     * Map Content-Type to a file extension.
     */
    protected function extensionFromContentType(string $contentType): string
    {
        return match (true) {
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'gif') => 'gif',
            str_contains($contentType, 'webp') => 'webp',
            str_contains($contentType, 'svg') => 'svg',
            default => 'jpg',
        };
    }
}
