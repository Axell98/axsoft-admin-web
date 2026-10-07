<?php

namespace App\Models;

use App\Support\RichText;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description HTML ya sanitizado.
 * @property string|null $location
 * @property int|null $project_category_id
 * @property int|null $execution_percentage
 * @property int|null $year
 * @property string|null $video_type file | youtube
 * @property int|null $video_media_file_id
 * @property string|null $video_youtube_id
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaFile|null $videoFile
 * @property-read ProjectCategory|null $category
 */
#[Fillable([
    'title',
    'slug',
    'description',
    'location',
    'project_category_id',
    'execution_percentage',
    'year',
    'video_type',
    'video_media_file_id',
    'video_youtube_id',
    'is_published',
])]
class Project extends Model
{
    public const VIDEO_FILE = 'file';

    public const VIDEO_YOUTUBE = 'youtube';

    public const MAX_PHASES = 30;

    /**
     * Valores por defecto, para que un proyecto recién creado ya los tenga sin recargarlo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_published' => true,
    ];

    /**
     * @return HasMany<ProjectPhase, $this>
     */
    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsTo<ProjectCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    /**
     * @return BelongsTo<MediaFile, $this>
     */
    public function videoFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'video_media_file_id');
    }

    /**
     * @param  Builder<Project>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Genera un slug único a partir de un texto: «Mi proyecto» → «mi-proyecto», «mi-proyecto-2»...
     */
    public static function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($text), 200, '');
        $base = $base !== '' ? $base : 'proyecto';

        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->when($ignoreId, fn ($query, $id) => $query->where('id', '!=', $id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Imagen de portada: la imagen de la primera fase.
     */
    public function coverUrl(): ?string
    {
        $first = $this->relationLoaded('phases') ? $this->phases->first() : $this->phases()->with('mediaFile')->first();

        return $first?->imageUrl();
    }

    public function videoKind(): ?string
    {
        return match ($this->video_type) {
            self::VIDEO_YOUTUBE => $this->video_youtube_id ? 'youtube' : null,
            self::VIDEO_FILE => $this->videoFile ? 'video' : null,
            default => null,
        };
    }

    public function videoUrl(): ?string
    {
        return $this->video_type === self::VIDEO_FILE ? $this->videoFile?->url() : null;
    }

    public function videoEmbedUrl(): ?string
    {
        return $this->video_type === self::VIDEO_YOUTUBE && $this->video_youtube_id
            ? YouTube::embedUrl($this->video_youtube_id)
            : null;
    }

    /**
     * Resumen en texto plano de la descripción.
     */
    public function excerpt(int $limit = 160): string
    {
        return RichText::plain($this->description, $limit);
    }

    protected function casts(): array
    {
        return [
            'execution_percentage' => 'integer',
            'year' => 'integer',
            'is_published' => 'boolean',
        ];
    }
}
