<?php

namespace App\Http\Resources;

use App\Models\PageCover;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública de la portada de una página interna.
 *
 * @mixin PageCover
 */
class PageCoverResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Clave de la página interna: nosotros, servicios...
            'page' => $this->page,
            'title' => $this->title,
            'image_url' => $this->imageUrl(),
        ];
    }
}
