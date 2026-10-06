<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Una fase del proyecto: una imagen con su propio título y contenido.
 *
 * @property int $id
 * @property int $project_id
 * @property string $type file | image_url
 * @property int|null $media_file_id
 * @property string|null $external_url
 * @property string|null $title
 * @property string|null $content HTML ya sanitizado.
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaFile|null $mediaFile
 */
#[Fillable(['type', 'media_file_id', 'external_url', 'title', 'content', 'position'])]
class ProjectPhase extends Model
{
    public const TYPE_FILE = 'file';

    public const TYPE_IMAGE_URL = 'image_url';

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<MediaFile, $this>
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    public function imageUrl(): ?string
    {
        return $this->type === self::TYPE_FILE ? $this->mediaFile?->url() : $this->external_url;
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
