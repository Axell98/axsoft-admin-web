<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    public function __invoke(MediaFile $mediaFile): StreamedResponse
    {
        abort_unless(Storage::disk(MediaFile::DISK)->exists($mediaFile->path), 404);

        return Storage::disk(MediaFile::DISK)->download($mediaFile->path, $mediaFile->name);
    }
}
