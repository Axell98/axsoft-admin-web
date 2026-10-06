<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\CompanyProfile;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    /**
     * Datos públicos de la empresa para el sitio web del cliente.
     */
    public function show(): JsonResponse
    {
        $profile = CompanyProfile::query()->first();

        if (! $profile) {
            return response()->json(['message' => 'Los datos de la empresa aún no han sido configurados.'], 404);
        }

        return (new CompanyResource($profile))
            ->response()
            ->header('Cache-Control', 'public, max-age=60');
    }
}
