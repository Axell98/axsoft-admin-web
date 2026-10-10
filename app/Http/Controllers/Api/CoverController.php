<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageCoverResource;
use App\Models\PageCover;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class CoverController extends Controller
{
    /**
     * Portadas activas de las páginas internas, en el orden de config/covers.php.
     */
    public function index(): JsonResponse
    {
        $order = array_flip(array_map('strval', array_keys(PageCover::pages())));

        $covers = $this->visible()
            ->get()
            ->filter(fn (PageCover $cover) => $cover->imageUrl() !== null)
            ->sortBy(fn (PageCover $cover) => $order[$cover->page] ?? PHP_INT_MAX)
            ->values();

        return PageCoverResource::collection($covers)
            ->response()
            ->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * Portada de una página interna. Devuelve `data: null` cuando no hay ninguna que mostrar
     * (no existe, está inactiva o se quedó sin imagen), para que la web use su portada por defecto.
     */
    public function show(string $page): JsonResponse
    {
        $cover = $this->visible()->where('page', $page)->first();

        $data = $cover && $cover->imageUrl() !== null
            ? (new PageCoverResource($cover))->resolve()
            : null;

        return response()
            ->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * Portadas activas de las páginas que siguen configuradas.
     *
     * @return Builder<PageCover>
     */
    private function visible(): Builder
    {
        return PageCover::query()
            ->active()
            ->whereIn('page', array_map('strval', array_keys(PageCover::pages())))
            ->with('mediaFile');
    }
}
