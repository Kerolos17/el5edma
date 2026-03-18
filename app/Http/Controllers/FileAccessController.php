<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use League\Flysystem\StorageAttributes;

class FileAccessController extends Controller
{
    public function show($path)
    {
        // Decode the path
        $decodedPath = base64_decode($path);

        // Check if the file exists in the 'private' disk
        if (!Storage::disk('private')->exists($decodedPath)) {
            abort(404);
        }

        // Get the file contents
        $file = Storage::disk('private')->get($decodedPath);
        $type = Storage::disk('private')->mimeType($decodedPath);

        // Return the file with the correct content type
        return Response::make($file, 200)->header("Content-Type", $type);
    }
}
