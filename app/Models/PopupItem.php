<?php

namespace App\Models;

use App\Support\YouTube;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $popup_id
 * @property string $type file | image_url | youtube
 * @property int|null $media_file_id
 * @property string|null $external_url
 * @property string|null $youtube_id
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaFile|null $mediaFile
 */
#[Fillable(['type', 'media_file_id', 'external_url', 'youtube_id', 'position'])]
class PopupItem extends Model
{
    public const TYPE_FILE = 'file';

    public const TYPE_IMAGE_URL = 'image_url';

    public const TYPE_YOUTUBE = 'youtube';

    /**
     * @return BelongsTo<Popup, $this>
     */
    public function popup(): BelongsTo
    {
        return $this->belongsTo(Popup::class);
    }

    /**
     * @return BelongsTo<MediaFile, $this>
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    /**
     * Tipo de contenido a mostrar: image, video o youtube.
     */
    public function kind(): string
    {
        return match ($this->type) {
            self::TYPE_YOUTUBE => 'youtube',
            self::TYPE_FILE => $this->mediaFile?->category() === 'video' ? 'video' : 'image',
            default => 'image',
        };
    }

    /**
     * URL del recurso (imagen o video). Null para YouTube.
     */
    public function sourceUrl(): ?string
    {
        return match ($this->type) {
            self::TYPE_FILE => $this->mediaFile?->url(),
            self::TYPE_IMAGE_URL => $this->external_url,
            default => null,
        };
    }

    public function embedUrl(): ?string
    {
        return $this->youtube_id ? YouTube::embedUrl($this->youtube_id) : null;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->youtube_id ? YouTube::thumbnailUrl($this->youtube_id) : null;
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
