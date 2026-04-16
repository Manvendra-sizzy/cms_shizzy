<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicFilesController extends Controller
{
    public function show(Request $request, string $path)
    {
        $path = ltrim($path, '/');

        if ($path === '' || Str::contains($path, ['..', "\0"])) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        // Some hosts disable stream functions (e.g. fpassthru), so serve from memory.
        // Files are capped by existing upload validation (5–10MB).
        $contents = $disk->get($path);
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $filename = basename($path);

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}

