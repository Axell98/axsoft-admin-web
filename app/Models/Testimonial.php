<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $role Cargo de quien da el testimonio.
 * @property string $content
 * @property string|null $image_type file | image_url
 * @property int|null $media_file_id
 * @property string|null $external_url
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaFile|null $mediaFile
 */
#[Fillable(['name', 'role', 'content', 'image_type', 'media_file_id', 'external_url', 'is_published'])]
class Testimonial extends Model
{
    public const IMAGE_FILE = 'file';

    public const IMAGE_URL = 'image_url';

    /**
     * Valores por defecto, para que un testimonio recién creado ya los tenga sin recargarlo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_published' => true,
    ];

    /**
     * @return BelongsTo<MediaFile, $this>
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    /**
     * @param  Builder<Testimonial>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * URL de la imagen, o null si no tiene.
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
            'is_published' => 'boolean',
        ];
    }
}
