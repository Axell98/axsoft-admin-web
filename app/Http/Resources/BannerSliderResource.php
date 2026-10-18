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
        $isVideo = $this->isVideo();

        // Filtra los slides según el tipo activo: si es video, solo el video; si es slider, solo las imágenes.
        $filteredSlides = $this->slides->filter(function (BannerSlide $slide) use ($isVideo) {
            $isSlideVideo = $slide->kind() === 'video' || $slide->kind() === 'youtube';
            return $isVideo ? $isSlideVideo : ! $isSlideVideo;
        });

        return [
            // slider (varias imágenes) | video (un solo video)
            'type' => $this->display_type,
            'screen_percentage' => $this->screen_percentage,
            'show_arrows' => ! $isVideo && $this->show_arrows,
            'show_indicators' => ! $isVideo && $this->show_indicators,
            'slides' => $filteredSlides->map(fn (BannerSlide $slide) => [
                'type' => $slide->kind(),
                'url' => $slide->sourceUrl(),
                'embed_url' => $slide->embedUrl(),
                'thumbnail_url' => $slide->youtube_id ? YouTube::thumbnailUrl($slide->youtube_id) : null,
                'title' => ! $isVideo ? $slide->title : null,
                'description' => ! $isVideo ? $slide->description : null,
                'link_url' => $slide->link_url,
            ])->values(),
        ];
    }
}