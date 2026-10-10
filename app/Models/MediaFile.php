<?php

namespace App\Models;

use App\Support\Thumbnail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $media_folder_id
 * @property string $name
 * @property string $path
 * @property string $extension
 * @property string|null $mime_type
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['media_folder_id', 'name', 'path', 'extension', 'mime_type', 'size'])]
class MediaFile extends Model
{
    public const DISK = 'public';

    protected static function booted(): void
    {
        static::deleting(function (MediaFile $file): void {
            Storage::disk(self::DISK)->delete($file->path);
            Thumbnail::delete($file);
        });
    }

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    /**
     * Filtra por nombre. Escapa % y _ con «!» para tratarlos como texto
     * literal en cualquier base de datos.
     *
     * @param  Builder<MediaFile>  $query
     */
    #[Scope]
    protected function nameLike(Builder $query, string $term): void
    {
        $term = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term));

        $query->whereRaw("name LIKE ? ESCAPE '!'", ["%{$term}%"]);
    }

    public function url(): string
    {
        return Storage::disk(self::DISK)->url($this->path);
    }

    /**
     * URL de la miniatura para las vistas previas del panel. Si aún no existe, apunta a la ruta
     * que la crea; si no es una imagen o el servidor no puede crearlas, es la del original.
     */
    public function thumbnailUrl(): string
    {
        $path = Thumbnail::path($this);

        if ($path === null) {
            return $this->url();
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->url($path);
        }

        // Si las rutas del servidor están en caché y aún no incluyen la nueva, se usa el original.
        return Route::has('admin.files.thumbnail') ? route('admin.files.thumbnail', $this) : $this->url();
    }

    /**
     * Categoría del archivo según su extensión: image, video, pdf, word...
     */
    public function category(): string
    {
        foreach (config('files.types') as $category => $extensions) {
            if (in_array($this->extension, $extensions, true)) {
                return $category;
            }
        }

        return 'other';
    }

    public function isImage(): bool
    {
        return $this->category() === 'image';
    }

    /**
     * @return array<int, string>
     */
    public static function allowedExtensions(): array
    {
        return array_values(array_merge(...array_values(config('files.types'))));
    }

    /**
     * Tamaño máximo por archivo en KB: el menor entre la configuración
     * de la app y los límites de PHP (upload_max_filesize y post_max_size).
     */
    public static function maxUploadKb(): int
    {
        $configured = (int) config('files.max_size_kb');
        $limit = ($configured > 0 ? $configured : 81920) * 1024;

        // En PHP, 0 o -1 significan «sin límite».
        foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
            $bytes = self::iniToBytes((string) ini_get($setting));

            if ($bytes > 0) {
                $limit = min($limit, $bytes);
            }
        }

        return intdiv($limit, 1024);
    }

    private static function iniToBytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
