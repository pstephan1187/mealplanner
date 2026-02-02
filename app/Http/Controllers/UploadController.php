<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __invoke(StoreImageRequest $request): JsonResponse
    {
        $path = $request->file('image')->store(
            'recipe-images',
            ['disk' => 'public', 'visibility' => 'public']
        );

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
