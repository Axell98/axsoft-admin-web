<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Ventana emergente del sitio web. Solo hay una (la principal).
 *
 * @property int $id
 * @property string|null $title
 * @property string $display_type image | slider | video
 * @property bool $is_visible
 * @property bool $show_header
 * @property bool $show_border
 * @property string|null $link_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'display_type', 'is_visible', 'show_header', 'show_border', 'link_url'])]
class Popup extends Model
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_SLIDER = 'slider';

    public const TYPE_VIDEO = 'video';

    public const MAX_ITEMS = 10;

    /**
     * Valores por defecto, para que un pop-up nuevo ya los tenga sin guardarlo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'display_type' => self::TYPE_IMAGE,
        'is_visible' => false,
        'show_header' => false,
        'show_border' => false,
    ];

    /**
     * Devuelve el pop-up principal. Si aún no existe devuelve uno nuevo sin guardar,
     * para que solo visitar la página no escriba en la base de datos.
     */
    public static function main(): self
    {
        return self::query()->first() ?? new self;
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [self::TYPE_IMAGE, self::TYPE_SLIDER, self::TYPE_VIDEO];
    }

    /**
     * @return HasMany<PopupItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PopupItem::class)->orderBy('position')->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'show_header' => 'boolean',
            'show_border' => 'boolean',
        ];
    }
}
