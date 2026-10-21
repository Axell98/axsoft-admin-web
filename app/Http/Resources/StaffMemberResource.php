<?php

namespace App\Http\Resources;

use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública de un integrante del equipo.
 *
 * @mixin StaffMember
 */
class StaffMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
            'image_url' => $this->imageUrl(),
        ];
    }
}
