<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Support\YouTube;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública de un proyecto. Con $detailed incluye la descripción
 * completa, las fases y el video; sin él, solo lo necesario para un listado.
 *
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    private bool $detailed = false;

    public function detailed(): static
    {
        $this->detailed = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category?->name,
            'category_slug' => $this->category?->slug,
            'location' => $this->location,
            'execution_percentage' => $this->execution_percentage,
            'year' => $this->year,
            'cover_url' => $this->coverUrl(),
            'excerpt' => $this->excerpt(),
        ];

        if (! $this->detailed) {
            return $data;
        }

        return $data + [
            // HTML ya sanitizado al guardar (negrita, cursiva, listas y enlaces).
            'description' => $this->description,
            'phases' => $this->phases->map(fn (ProjectPhase $phase) => [
                'image_url' => $phase->imageUrl(),
                'title' => $phase->title,
                'content' => $phase->content,
            ])->values(),
            'video' => $this->videoPayload(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function videoPayload(): ?array
    {
        $kind = $this->videoKind();

        if ($kind === null) {
            return null;
        }

        return [
            'type' => $kind,
            'url' => $this->videoUrl(),
            'embed_url' => $this->videoEmbedUrl(),
            'thumbnail_url' => $this->video_youtube_id && $kind === 'youtube' ? YouTube::thumbnailUrl($this->video_youtube_id) : null,
        ];
    }
}
