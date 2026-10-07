<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;

class TestimonialController extends Controller
{
    /**
     * Testimonios visibles, del más reciente al más antiguo.
     */
    public function index(): JsonResponse
    {
        $testimonials = Testimonial::query()
            ->published()
            ->with('mediaFile')
            ->latest()
            ->latest('id')
            ->get();

        return TestimonialResource::collection($testimonials)
            ->response()
            ->header('Cache-Control', 'public, max-age=60');
    }
}
