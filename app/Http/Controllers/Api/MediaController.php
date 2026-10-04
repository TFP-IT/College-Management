<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MediaFile;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension() ?: $file->guessExtension();
            $originalName = $file->getClientOriginalName();
            $title = pathinfo($originalName, PATHINFO_FILENAME);
            $slug = Str::slug($title) . '-' . uniqid();
            
            $filePath = $file->store('media', 'public');

            $media = MediaFile::create([
                'user_id' => auth()->id(), // nullable
                'title' => $title,
                'slug' => $slug,
                'file_path' => $filePath,
                'extension' => $extension,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $media->id,
                    'file_path' => $media->file_path,
                    'url' => asset('storage/' . $media->file_path),
                ]
            ], 201);
        }

        return response()->json(['status' => 'error', 'message' => 'File upload failed'], 400);
    }
}
