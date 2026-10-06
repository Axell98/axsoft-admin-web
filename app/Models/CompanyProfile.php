<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de la empresa del cliente. Cada cliente tiene su propia base de datos,
 * por lo que la tabla contiene un único registro.
 *
 * @property int $id
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $ruc
 * @property string|null $slogan
 * @property string|null $address
 * @property string|null $maps_url
 * @property string|null $business_hours
 * @property string|null $email
 * @property string|null $email_2
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $whatsapp_2
 * @property string|null $website
 * @property string|null $facebook
 * @property string|null $instagram
 * @property string|null $youtube
 * @property string|null $tiktok
 * @property string|null $linkedin
 * @property string|null $behance
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'legal_name',
    'ruc',
    'slogan',
    'address',
    'maps_url',
    'business_hours',
    'email',
    'email_2',
    'phone',
    'whatsapp',
    'whatsapp_2',
    'website',
    'facebook',
    'instagram',
    'youtube',
    'tiktok',
    'linkedin',
    'behance',
    'description',
    'meta_title',
    'meta_description',
    'logo_path',
    'favicon_path',
])]
class CompanyProfile extends Model
{
    /**
     * Devuelve el registro único de la empresa, creándolo si aún no existe.
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::create(['name' => config('app.name')]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon_path ? Storage::disk('public')->url($this->favicon_path) : null;
    }
}
