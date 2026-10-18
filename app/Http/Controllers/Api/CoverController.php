<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageCoverResource;
use App\Models\PageCover;
use Illuminate\Http\JsonResponse;

class CoverController extends Controller
{
    /**
     * Portadas de las páginas internas, en el orden de config/covers.php.
     */
    public function index(): JsonResponse
    {
        $order = array_flip(array_map('strval', array_keys(PageCover::pages())));

        $covers = PageCover::query()
            ->whereIn('page', array_map('strval', array_keys(PageCover::pages())))
            ->with('mediaFile')
            ->get()
            ->sortBy(fn (PageCover $cover) => $order[$cover->page] ?? PHP_INT_MAX)
            ->values();

        return PageCoverResource::collection($covers)
            ->response()
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Portada de una página interna por su clave.
     */
    public function show(string $page): JsonResponse
    {
        $cover = PageCover::query()
            ->where('page', $page)
            ->whereIn('page', array_map('strval', array_keys(PageCover::pages())))
            ->with('mediaFile')
            ->first();

        $data = $cover
            ? (new PageCoverResource($cover))->resolve()
            : null;

        return response()
            ->json(['data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
