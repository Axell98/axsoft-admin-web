<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StaffMemberResource;
use App\Models\StaffMember;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    /**
     * Equipo de la empresa, en el orden definido en el panel.
     */
    public function index(): JsonResponse
    {
        $members = StaffMember::query()->with('mediaFile')->ordered()->get();

        return StaffMemberResource::collection($members)
            ->response()
            ->header('Cache-Control', 'public, max-age=60');
    }
}
