<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class MediaFolder extends Model
{
    protected static function booted(): void
    {
        // Elimina uno a uno para que cada archivo borre también su fichero físico.
        static::deleting(function (MediaFolder $folder): void {
            $folder->files()->get()->each->delete();
        });
    }

    /**
     * @return HasMany<MediaFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }
}
