<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Support\Thumbnail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class FileThumbnailController extends Controller
{
    /**
     * Crea la miniatura de una imagen la primera vez que se pide y redirige a ella.
     * Si no se puede crear (no es una imagen, es demasiado grande, el servidor no
     * tiene GD...), redirige al archivo original.
     */
    public function __invoke(MediaFile $mediaFile): RedirectResponse
    {
        $path = Thumbnail::ensure($mediaFile);

        return redirect()->to(
            $path !== null ? Storage::disk(MediaFile::DISK)->url($path) : $mediaFile->url(),
        );
    }
}
