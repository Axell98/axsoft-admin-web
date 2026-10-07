<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private const DEFAULT_PER_PAGE = 12;

    private const MAX_PER_PAGE = 50;

    /**
     * Listado paginado de los proyectos visibles, del más reciente al más antiguo.
     * Con ?category={slug} se filtra por categoría.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', self::DEFAULT_PER_PAGE), 1), self::MAX_PER_PAGE);

        $projects = Project::query()
            ->published()
            ->with(['phases.mediaFile', 'category'])
            ->when($request->string('category')->trim()->toString(), fn ($query, string $slug) => $query->whereHas('category', fn ($category) => $category->where('slug', $slug)))
            ->latest()
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return ProjectResource::collection($projects)
            ->response()
            ->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * Detalle de un proyecto visible, buscado por su slug.
     */
    public function show(string $slug): JsonResponse
    {
        $project = Project::query()
            ->published()
            ->with(['phases.mediaFile', 'videoFile', 'category'])
            ->where('slug', $slug)
            ->first();

        if (! $project) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        return (new ProjectResource($project))
            ->detailed()
            ->response()
            ->header('Cache-Control', 'public, max-age=60');
    }
}
