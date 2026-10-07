<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectCategory;
use Illuminate\Http\JsonResponse;

class ProjectCategoryController extends Controller
{
    /**
     * Categorías que tienen al menos un proyecto visible, por orden alfabético.
     * El `slug` sirve para filtrar el listado: /api/v1/projects?category={slug}.
     */
    public function index(): JsonResponse
    {
        $categories = ProjectCategory::query()
            ->whereHas('projects', fn ($query) => $query->published())
            ->withCount(['projects as projects_count' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get()
            ->map(fn (ProjectCategory $category) => [
                'name' => $category->name,
                'slug' => $category->slug,
                'projects_count' => (int) $category->getAttribute('projects_count'),
            ]);

        return response()
            ->json(['data' => $categories])
            ->header('Cache-Control', 'public, max-age=60');
    }
}
