<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug'])]
class ProjectCategory extends Model
{
    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Indica si ya existe una categoría con ese nombre (se compara por slug, sin importar
     * mayúsculas ni tildes). Con $ignoreId se omite la categoría que se está editando.
     */
    public static function nameTaken(string $name, ?int $ignoreId = null): bool
    {
        return static::query()
            ->where('slug', Str::slug($name))
            ->when($ignoreId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->exists();
    }
}
