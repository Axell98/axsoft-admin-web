<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Portada (imagen de cabecera) de una página interna del sitio web. Hay una por página.
 *
 * @property int $id
 * @property string $page Clave de la página interna (ver config/covers.php).
 * @property string|null $title
 * @property string|null $image_type file | image_url
 * @property int|null $media_file_id
 * @property string|null $external_url
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaFile|null $mediaFile
 */
#[Fillable(['page', 'title', 'image_type', 'media_file_id', 'external_url', 'is_active'])]
class PageCover extends Model
{
    public const IMAGE_FILE = 'file';

    public const IMAGE_URL = 'image_url';

    /**
     * Valores por defecto, para que una portada recién creada ya los tenga sin recargarla.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Páginas internas que pueden tener portada: clave => nombre.
     *
     * @return array<string, string>
     */
    public static function pages(): array
    {
        return config('covers.pages', []);
    }

    /**
     * @return BelongsTo<MediaFile, $this>
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    /**
     * @param  Builder<PageCover>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * URL de la imagen, o null si no tiene (o si su archivo fue eliminado).
     */
    public function imageUrl(): ?string
    {
        return match ($this->image_type) {
            self::IMAGE_FILE => $this->mediaFile?->url(),
            self::IMAGE_URL => $this->external_url,
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
