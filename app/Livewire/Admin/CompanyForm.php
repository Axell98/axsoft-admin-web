<?php

namespace App\Livewire\Admin;

use App\Models\CompanyProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Datos del cliente')]
class CompanyForm extends Component
{
    use WithFileUploads;

    /** Campos de texto del formulario. */
    private const TEXT_FIELDS = [
        'name', 'legal_name', 'ruc', 'slogan',
        'address', 'maps_url', 'business_hours',
        'email', 'email_2', 'phone', 'whatsapp', 'whatsapp_2',
        'website', 'facebook', 'instagram', 'youtube', 'tiktok', 'linkedin', 'behance',
        'description', 'meta_title', 'meta_description',
    ];

    /** Imágenes del formulario: propiedad => columna donde se guarda la ruta. */
    private const IMAGE_FIELDS = [
        'logo' => 'logo_path',
        'favicon' => 'favicon_path',
    ];

    public string $name = '';

    public string $legal_name = '';

    public string $ruc = '';

    public string $slogan = '';

    public string $address = '';

    public string $maps_url = '';

    public string $business_hours = '';

    public string $email = '';

    public string $email_2 = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $whatsapp_2 = '';

    public string $website = '';

    public string $facebook = '';

    public string $instagram = '';

    public string $youtube = '';

    public string $tiktok = '';

    public string $linkedin = '';

    public string $behance = '';

    public string $description = '';

    public string $meta_title = '';

    public string $meta_description = '';

    public ?TemporaryUploadedFile $logo = null;

    public ?TemporaryUploadedFile $favicon = null;

    public ?string $currentLogoUrl = null;

    public ?string $currentFaviconUrl = null;

    public function mount(): void
    {
        $profile = CompanyProfile::current();

        $this->fill(array_map(
            fn (?string $value) => $value ?? '',
            $profile->only(self::TEXT_FIELDS),
        ));

        $this->syncImageUrls($profile);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $url = ['nullable', 'url', 'max:255'];
        $phone = ['nullable', 'regex:/^\+?[0-9 ]{6,20}$/'];
        $image = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'ruc' => ['nullable', 'digits:11'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
            'business_hours' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'email_2' => ['nullable', 'email', 'max:255'],
            'phone' => $phone,
            'whatsapp' => $phone,
            'whatsapp_2' => $phone,
            'website' => $url,
            'facebook' => $url,
            'instagram' => $url,
            'youtube' => $url,
            'tiktok' => $url,
            'linkedin' => $url,
            'behance' => $url,
            'description' => ['nullable', 'string', 'max:2000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'logo' => $image,
            'favicon' => $image,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Ingresa el nombre de la empresa.',
            'ruc.digits' => 'El RUC debe tener exactamente 11 dígitos.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email_2.email' => 'Ingresa un correo electrónico válido.',
            'phone.regex' => 'Ingresa un número válido (solo dígitos, espacios y + al inicio).',
            'whatsapp.regex' => 'Ingresa un número válido (solo dígitos, espacios y + al inicio).',
            'whatsapp_2.regex' => 'Ingresa un número válido (solo dígitos, espacios y + al inicio).',
            '*.url' => 'Ingresa una URL válida (https://...).',
            'description.max' => 'La descripción no puede superar los 2000 caracteres.',
            'meta_title.max' => 'El meta título no puede superar los 70 caracteres.',
            'meta_description.max' => 'La meta descripción no puede superar los 160 caracteres.',
            '*.image' => 'El archivo debe ser una imagen.',
            '*.mimes' => 'La imagen debe ser PNG, JPG o WEBP.',
            '*.max' => 'El valor ingresado es demasiado largo o pesado.',
        ];
    }

    public function updatedLogo(): void
    {
        $this->validateImage('logo');
    }

    public function updatedFavicon(): void
    {
        $this->validateImage('favicon');
    }

    /**
     * Valida la imagen recién elegida y la descarta si no es válida,
     * para que la vista no intente previsualizar un archivo incorrecto.
     */
    protected function validateImage(string $field): void
    {
        try {
            $this->validateOnly($field);
        } catch (ValidationException $e) {
            $this->{$field} = null;

            throw $e;
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [];
        foreach (self::TEXT_FIELDS as $field) {
            $data[$field] = $validated[$field] === '' ? null : $validated[$field];
        }

        $profile = CompanyProfile::current();

        foreach (self::IMAGE_FIELDS as $field => $column) {
            if ($this->{$field}) {
                $this->deleteImageFile($profile, $column);
                $data[$column] = $this->{$field}->store($field === 'logo' ? 'logos' : 'favicons', 'public');
            }
        }

        $profile->update($data);

        $this->logo = null;
        $this->favicon = null;
        $this->syncImageUrls($profile);

        $this->dispatch('saved');
    }

    public function removeImage(string $field): void
    {
        $column = self::IMAGE_FIELDS[$field] ?? abort(404);

        $profile = CompanyProfile::current();

        $this->deleteImageFile($profile, $column);
        $profile->update([$column => null]);

        $this->{$field} = null;
        $this->syncImageUrls($profile);

        $this->dispatch('saved');
    }

    protected function deleteImageFile(CompanyProfile $profile, string $column): void
    {
        if ($profile->{$column}) {
            Storage::disk('public')->delete($profile->{$column});
        }
    }

    protected function syncImageUrls(CompanyProfile $profile): void
    {
        $this->currentLogoUrl = $profile->logoUrl();
        $this->currentFaviconUrl = $profile->faviconUrl();
    }

    public function render(): View
    {
        return view('livewire.admin.company-form');
    }
}
