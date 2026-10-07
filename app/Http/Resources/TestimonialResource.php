<?php

namespace App\Http\Resources;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública de un testimonio.
 *
 * @mixin Testimonial
 */
class TestimonialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
            // Texto plano con saltos de línea: el sitio debe escaparlo al mostrarlo.
            'content' => $this->content,
            'image_url' => $this->imageUrl(),
        ];
    }
}
