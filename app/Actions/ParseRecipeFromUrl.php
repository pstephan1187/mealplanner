<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use RuntimeException;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class ParseRecipeFromUrl
{
    public function __construct(protected ExtractRecipeImages $imageExtractor) {}

    /**
     * Fetch a URL, extract images, and use Prism to extract structured recipe data with HTML instructions.
     *
     * @return array<string, mixed>
     */
    public function __invoke(string $url): array
    {
        $html = $this->fetchHtml($url);
        $imageResult = ($this->imageExtractor)($html, $url);
        $cleanedText = $this->stripHtml($html);
        $prompt = $this->buildPrompt($cleanedText, $imageResult['all']);
        $schema = $this->buildSchema();

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'gpt-4o-mini')
            ->withSchema($schema)
            ->withProviderOptions(['schema' => ['strict' => true]])
            ->withSystemPrompt($this->systemPrompt())
            ->withPrompt($prompt)
            ->asStructured();

        $structured = $response->structured;

        if (empty($structured['name']) && empty($structured['instructions'])) {
            throw new RuntimeException('Could not extract meaningful recipe data from the provided URL.');
        }

        if (! empty($structured['instructions'])) {
            $structured['instructions'] = $this->sanitizeInstructions($structured['instructions']);
        }

        $photo = $this->processMainPhoto($imageResult['main']);
        $structured['photo_path'] = $photo['path'];
        $structured['photo_url'] = $photo['url'];

        return $structured;
    }

    /**
     * Fetch HTML content from the given URL.
     */
    protected function fetchHtml(string $url): string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; MealPlannerBot/1.0)',
        ])->timeout(15)->get($url);

        $response->throw();

        return $response->body();
    }

    /**
     * Strip non-content HTML elements and tags, then truncate.
     */
    protected function stripHtml(string $html): string
    {
        // Remove script, style, nav, footer, header, aside tags and their contents
        $patterns = [
            '/<script\b[^>]*>.*?<\/script>/si',
            '/<style\b[^>]*>.*?<\/style>/si',
            '/<nav\b[^>]*>.*?<\/nav>/si',
            '/<footer\b[^>]*>.*?<\/footer>/si',
            '/<header\b[^>]*>.*?<\/header>/si',
            '/<aside\b[^>]*>.*?<\/aside>/si',
        ];

        $cleaned = preg_replace($patterns, '', $html);
        $cleaned = strip_tags($cleaned);

        // Collapse whitespace
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);

        return mb_substr($cleaned, 0, 10000);
    }

    /**
     * Build the user prompt with cleaned text and available image URLs.
     *
     * @param  array<int, string>  $imageUrls
     */
    protected function buildPrompt(string $cleanedText, array $imageUrls): string
    {
        $prompt = $cleanedText;

        if (! empty($imageUrls)) {
            $prompt .= "\n\n---\nAvailable recipe images (use these exact URLs in <img> tags where relevant):\n";

            foreach ($imageUrls as $i => $url) {
                $prompt .= ($i + 1).'. '.$url."\n";
            }
        }

        return $prompt;
    }

    /**
     * Sanitize AI-generated HTML instructions using HTMLPurifier.
     */
    protected function sanitizeInstructions(string $html): string
    {
        return Purifier::clean($html);
    }

    /**
     * Process the main recipe image: crop to square, resize, and store in the recipes/ directory.
     *
     * @return array{path: string|null, url: string|null}
     */
    protected function processMainPhoto(?string $localUrl): array
    {
        if (! $localUrl) {
            return ['path' => null, 'url' => null];
        }

        try {
            $relativePath = str_replace('/storage/', '', $localUrl);
            $disk = Storage::disk('public');
            $contents = $disk->get($relativePath);

            if (! $contents) {
                return ['path' => null, 'url' => null];
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'recipe_photo_');
            file_put_contents($tmpFile, $contents);

            $image = Image::load($tmpFile);
            $size = min(2048, $image->getWidth(), $image->getHeight());
            $image->fit(Fit::Crop, $size, $size)->save();

            $ext = pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'jpg';
            $storedPath = 'recipes/'.Str::random(40).'.'.$ext;
            $disk->put($storedPath, file_get_contents($tmpFile), ['visibility' => 'public']);

            @unlink($tmpFile);

            return [
                'path' => $storedPath,
                'url' => $disk->url($storedPath),
            ];
        } catch (\Throwable $e) {
            Log::debug('Failed to process main recipe photo', ['error' => $e->getMessage()]);

            return ['path' => null, 'url' => null];
        }
    }

    /**
     * Build the Prism ObjectSchema for recipe extraction.
     */
    protected function buildSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'recipe',
            description: 'Structured recipe data extracted from a webpage',
            properties: [
                new StringSchema(
                    name: 'name',
                    description: 'The name/title of the recipe',
                    nullable: true,
                ),
                new StringSchema(
                    name: 'instructions',
                    description: 'Step-by-step cooking instructions formatted as HTML using tags: h2, h3, p, strong, em, ul, ol, li, img[src|alt]',
                    nullable: true,
                ),
                new NumberSchema(
                    name: 'servings',
                    description: 'Number of servings the recipe makes',
                    nullable: true,
                ),
                new StringSchema(
                    name: 'flavor_profile',
                    description: 'A brief description of the flavor profile (e.g., savory, sweet, spicy)',
                    nullable: true,
                ),
                new ArraySchema(
                    name: 'meal_types',
                    description: 'Meal types this recipe is suitable for. Only use: Breakfast, Lunch, Dinner',
                    items: new StringSchema(
                        name: 'meal_type',
                        description: 'A meal type: Breakfast, Lunch, or Dinner',
                    ),
                ),
                new NumberSchema(
                    name: 'prep_time_minutes',
                    description: 'Preparation time in minutes',
                    nullable: true,
                ),
                new NumberSchema(
                    name: 'cook_time_minutes',
                    description: 'Cooking time in minutes',
                    nullable: true,
                ),
                new ArraySchema(
                    name: 'ingredients',
                    description: 'List of ingredients required for the recipe',
                    items: new ObjectSchema(
                        name: 'ingredient',
                        description: 'A single ingredient with its details',
                        properties: [
                            new StringSchema(
                                name: 'name',
                                description: 'The ingredient name, normalized (e.g., "chicken breast" not "2 lbs chicken breast")',
                            ),
                            new StringSchema(
                                name: 'quantity',
                                description: 'The numeric quantity as a string (e.g., "2", "1/2")',
                                nullable: true,
                            ),
                            new StringSchema(
                                name: 'unit',
                                description: 'The unit of measurement, normalized (e.g., "cup", "tbsp", "oz")',
                                nullable: true,
                            ),
                            new StringSchema(
                                name: 'note',
                                description: 'Any additional notes (e.g., "diced", "room temperature")',
                                nullable: true,
                            ),
                        ],
                        requiredFields: ['name', 'quantity', 'unit', 'note'],
                    ),
                ),
            ],
            requiredFields: ['name', 'instructions', 'servings', 'flavor_profile', 'meal_types', 'prep_time_minutes', 'cook_time_minutes', 'ingredients'],
        );
    }

    /**
     * The system prompt for recipe extraction.
     */
    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a recipe extraction assistant. Your task is to extract structured recipe data from HTML webpage content.

Guidelines:
- Extract the recipe name, instructions, servings, flavor profile, meal types, prep time, cook time, and ingredients.
- Format instructions as clean HTML using these tags: h2, h3, p, strong, em, ul, ol, li, img.
- Structure instructions logically — use headings for recipe sections (e.g., "For the Sauce"), ordered lists for sequential steps, and paragraphs for tips or notes.
- If image URLs are provided below the recipe text, embed them as <img> tags at relevant points in the instructions. Use the exact URLs provided. Do not invent image URLs.
- Normalize measurement units (e.g., "tablespoons" → "tbsp", "teaspoons" → "tsp", "ounces" → "oz", "pounds" → "lb").
- For meal_types, only use these values: Breakfast, Lunch, Dinner. Choose whichever apply.
- For ingredients, separate the name from the quantity, unit, and any preparation notes.
- Return null for any fields that cannot be determined from the content.
- If the content does not appear to contain a recipe, return null for name and instructions.
PROMPT;
    }
}
