<?php

namespace App\Http\Resources;

use App\Models\BannerSlide;
use App\Models\BannerSlider;
use App\Support\YouTube;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública del slider de banners.
 *
 * @mixin BannerSlider
 */
class BannerSliderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'screen_percentage' => $this->screen_percentage,
            'show_arrows' => $this->show_arrows,
            'show_indicators' => $this->show_indicators,
            'slides' => $this->slides->map(fn (BannerSlide $slide) => [
                'type' => $slide->kind(),
                'url' => $slide->sourceUrl(),
                'embed_url' => $slide->embedUrl(),
                'thumbnail_url' => $slide->youtube_id ? YouTube::thumbnailUrl($slide->youtube_id) : null,
                // El texto y la descripción solo se muestran con los indicadores activos.
                'title' => $this->show_indicators ? $slide->title : null,
                'description' => $this->show_indicators ? $slide->description : null,
                'link_url' => $slide->link_url,
            ])->values(),
        ];
    }
}
