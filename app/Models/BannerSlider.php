<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Configuración de un slider de banners. Por ahora solo se usa el slider
 * principal, pero la tabla permite tener varios más adelante.
 *
 * @property int $id
 * @property string $name
 * @property int $screen_percentage Porcentaje de la altura de la pantalla que ocupa el banner (10-100).
 * @property bool $show_arrows
 * @property bool $show_indicators
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'screen_percentage', 'show_arrows', 'show_indicators'])]
class BannerSlider extends Model
{
    public const MIN_PERCENTAGE = 10;

    public const MAX_PERCENTAGE = 100;

    public const MAX_SLIDES = 20;

    /**
     * Devuelve el slider principal, creándolo si aún no existe.
     */
    public static function main(): self
    {
        return static::query()->first() ?? static::create([
            'name' => 'Principal',
            'screen_percentage' => self::MAX_PERCENTAGE,
            'show_arrows' => true,
            'show_indicators' => true,
        ]);
    }

    /**
     * @return HasMany<BannerSlide, $this>
     */
    public function slides(): HasMany
    {
        return $this->hasMany(BannerSlide::class)->orderBy('position')->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'screen_percentage' => 'integer',
            'show_arrows' => 'boolean',
            'show_indicators' => 'boolean',
        ];
    }
}
