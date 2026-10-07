<?php

namespace App\Http\Resources;

use App\Models\Popup;
use App\Models\PopupItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública del pop-up.
 *
 * @mixin Popup
 */
class PopupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // image | slider | video
            'type' => $this->display_type,
            // El título solo se entrega si el encabezado está activo.
            'title' => $this->show_header ? $this->title : null,
            'show_header' => $this->show_header,
            'show_border' => $this->show_border,
            'link_url' => $this->link_url,
            'items' => $this->items->map(fn (PopupItem $item) => [
                // image | video | youtube
                'kind' => $item->kind(),
                'url' => $item->sourceUrl(),
                'embed_url' => $item->embedUrl(),
                'thumbnail_url' => $item->thumbnailUrl(),
            ])->values(),
            // Cambia cada vez que se guarda: sirve para volver a mostrar un pop-up que el visitante ya cerró.
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
