<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerSliderResource;
use App\Models\BannerSlider;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    /**
     * Slider de banners de la portada: opciones y banners en orden.
     */
    public function show(): JsonResponse
    {
        $slider = BannerSlider::query()->first();

        if ($slider) {
            $slider->load('slides.mediaFile');
        } else {
            // Sin configuración aún: valores por defecto y ningún banner (sin escribir en la base de datos).
            $slider = new BannerSlider([
                'name' => 'Principal',
                'display_type' => BannerSlider::TYPE_SLIDER,
                'screen_percentage' => 100,
                'show_arrows' => true,
                'show_indicators' => true,
            ]);
            $slider->setRelation('slides', collect());
        }

        return (new BannerSliderResource($slider))
            ->response()
            ->header('Cache-Control', 'public, max-age=60');
    }
}
