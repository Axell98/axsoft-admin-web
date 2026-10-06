<?php

namespace App\Http\Resources;

use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública de los datos de la empresa.
 *
 * @mixin CompanyProfile
 */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'ruc' => $this->ruc,
            'slogan' => $this->slogan,
            'description' => $this->description,
            'logo_url' => $this->logoUrl(),
            'favicon_url' => $this->faviconUrl(),
            'contact' => [
                'address' => $this->address,
                'maps_url' => $this->maps_url,
                'business_hours' => $this->business_hours,
                'email' => $this->email,
                'email_2' => $this->email_2,
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
                'whatsapp_2' => $this->whatsapp_2,
            ],
            'social' => [
                'website' => $this->website,
                'facebook' => $this->facebook,
                'instagram' => $this->instagram,
                'youtube' => $this->youtube,
                'tiktok' => $this->tiktok,
                'linkedin' => $this->linkedin,
                'behance' => $this->behance,
            ],
            'seo' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
            ],
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
