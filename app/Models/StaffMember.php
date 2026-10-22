<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Integrante del equipo de la empresa.
 *
 * @property int $id
 * @property string $name
 * @property string|null $role Cargo.
 * @property int $sort_order Posición en la web, de menor a mayor.
 * @property string|null $image_type file | image_url
 * @property int|null $media_file_id
 * @property string|null $external_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaFile|null $mediaFile
 */
#[Fillable(['name', 'role', 'sort_order', 'image_type', 'media_file_id', 'external_url'])]
class StaffMember extends Model
{
    public const IMAGE_FILE = 'file';

    public const IMAGE_URL = 'image_url';

    public const MAX_ORDER = 9999;

    /**
     * @return BelongsTo<MediaFile, $this>
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    /**
     * Del primero al último según el campo de orden; a igual orden, el más antiguo primero.
     *
     * @param  Builder<StaffMember>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Siguiente número de orden libre: el mayor actual más uno.
     */
    public static function nextOrder(): int
    {
        $max = static::query()->max('sort_order');

        return $max === null ? 1 : min((int) $max + 1, self::MAX_ORDER);
    }

    /**
     * URL de la foto, o null si no tiene.
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
            'sort_order' => 'integer',
        ];
    }
}
