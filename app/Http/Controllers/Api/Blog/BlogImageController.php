<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogImageController extends Controller
{
    /**
     * Upload image for blog content (CKEditor)
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension();
            $storedName = $filename . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            $path = $file->storeAs('blog/images', $storedName, 'public');
            
            $url = Storage::url($path);
            
            return response()->json([
                'uploaded' => true,
                'url' => $url
            ]);
        }

        return response()->json([
            'uploaded' => false,
            'error' => ['message' => 'Could not upload file']
        ], 400);
    }

    /**
     * Upload cover image
     */
    public function uploadCover(Request $request): JsonResponse
    {
        $request->validate([
            'cover_image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension();
            $storedName = $filename . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            $path = $file->storeAs('blog/covers', $storedName, 'public');
            
            $url = Storage::url($path);
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file uploaded'
        ], 400);
    }

    public function uploadEditorImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('image');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $storedName = $filename . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('editor-images', $storedName, 'public');
        $url = Storage::url($path);

        return response()->json([
            'success' => true,
            'url' => $url,
            'message' => 'Image uploaded successfully'
        ]);
    }
}