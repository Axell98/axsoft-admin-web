<?php

namespace App\Livewire\Admin;

use App\Models\MediaFile;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Testimonios')]
class TestimonialManager extends Component
{
    use WithPagination;

    private const PER_PAGE = 12;

    public bool $showForm = false;

    /** Null = testimonio nuevo. */
    public ?int $editingId = null;

    public string $name = '';

    public string $role = '';

    public string $content = '';

    /** file | image_url | null */
    public ?string $imageType = null;

    public ?int $mediaFileId = null;

    public ?string $externalUrl = null;

    public bool $isPublished = true;

    // --- Formulario ---------------------------------------------------------------

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $testimonial = Testimonial::findOrFail($id);

        $this->resetForm();
        $this->editingId = $testimonial->id;
        $this->name = $testimonial->name;
        $this->role = (string) $testimonial->role;
        $this->content = $testimonial->content;
        $this->imageType = $testimonial->image_type;
        $this->mediaFileId = $testimonial->media_file_id;
        $this->externalUrl = $testimonial->external_url;
        $this->isPublished = $testimonial->is_published;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset('editingId', 'name', 'role', 'content', 'imageType', 'mediaFileId', 'externalUrl');
        $this->isPublished = true;
        $this->resetErrorBag();
    }

    // --- Imagen ---------------------------------------------------------------------

    public function chooseImage(): void
    {
        $this->dispatch(
            'open-media-picker',
            context: 'testimonial',
            mode: 'image',
            multiple: false,
            external: ['image'],
            used: $this->mediaFileId ? [$this->mediaFileId] : [],
        )->to(MediaPicker::class);
    }

    /**
     * Recibe la imagen elegida. Se vuelve a validar aquí porque el evento viaja por el navegador.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    #[On('media-picked')]
    public function onMediaPicked(string $context, array $items): void
    {
        if ($context !== 'testimonial' || ! $this->showForm) {
            return;
        }

        $item = $items[0] ?? [];

        if (($item['type'] ?? null) === Testimonial::IMAGE_FILE && $this->isImageFile($item['media_file_id'] ?? null)) {
            $this->imageType = Testimonial::IMAGE_FILE;
            $this->mediaFileId = (int) $item['media_file_id'];
            $this->externalUrl = null;
        } elseif (($item['type'] ?? null) === Testimonial::IMAGE_URL && $this->isValidUrl($item['url'] ?? null)) {
            $this->imageType = Testimonial::IMAGE_URL;
            $this->externalUrl = (string) $item['url'];
            $this->mediaFileId = null;
        }
    }

    public function removeImage(): void
    {
        $this->reset('imageType', 'mediaFileId', 'externalUrl');
    }

    // --- Guardar, ocultar y eliminar ------------------------------------------------

    public function save(): void
    {
        $this->name = trim($this->name);
        $this->role = trim($this->role);
        $this->content = trim($this->content);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'content' => ['required', 'string', 'max:1500'],
            'isPublished' => ['boolean'],
        ], [
            'name.required' => 'Ingresa el nombre de quien da el testimonio.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'role.max' => 'El cargo no puede superar los 120 caracteres.',
            'content.required' => 'Escribe el testimonio.',
            'content.max' => 'El testimonio no puede superar los 1500 caracteres.',
        ]);

        $this->ensureImageIsValid();

        $attributes = [
            'name' => $this->name,
            'role' => $this->role !== '' ? $this->role : null,
            'content' => $this->content,
            'image_type' => $this->imageType,
            'media_file_id' => $this->imageType === Testimonial::IMAGE_FILE ? $this->mediaFileId : null,
            'external_url' => $this->imageType === Testimonial::IMAGE_URL ? $this->externalUrl : null,
            'is_published' => $this->isPublished,
        ];

        if ($this->editingId !== null) {
            Testimonial::findOrFail($this->editingId)->update($attributes);
            $message = 'Testimonio actualizado';
        } else {
            Testimonial::create($attributes);
            $message = 'Testimonio registrado';
        }

        $this->closeForm();
        $this->resetPage();
        $this->dispatch('notify', message: $message);
    }

    /**
     * La imagen, si hay, debe ser un archivo de imagen existente o un link http(s).
     */
    protected function ensureImageIsValid(): void
    {
        if ($this->imageType === null) {
            return;
        }

        $valid = match ($this->imageType) {
            Testimonial::IMAGE_FILE => $this->isImageFile($this->mediaFileId),
            Testimonial::IMAGE_URL => $this->isValidUrl($this->externalUrl),
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages(['imageType' => 'La imagen elegida no es válida. Quítala y vuelve a elegirla.']);
        }
    }

    public function toggleVisibility(int $id): void
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->update(['is_published' => ! $testimonial->is_published]);

        $this->dispatch('notify', message: $testimonial->is_published ? 'Testimonio visible en el sitio web' : 'Testimonio oculto del sitio web');
    }

    public function delete(int $id): void
    {
        Testimonial::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->closeForm();
        }

        $this->dispatch('notify', message: 'Testimonio eliminado');
    }

    // --- Auxiliares -------------------------------------------------------------

    protected function isImageFile(mixed $id): bool
    {
        return is_numeric($id)
            && MediaFile::whereKey((int) $id)->whereIn('extension', config('files.types.image'))->exists();
    }

    protected function isValidUrl(mixed $url): bool
    {
        return is_string($url)
            && ! Validator::make(['url' => $url], ['url' => ['required', 'url:http,https', 'max:2048']])->fails();
    }

    /**
     * Imagen que se muestra en el formulario, a partir de datos del servidor.
     */
    protected function imagePreview(): ?string
    {
        return match ($this->imageType) {
            Testimonial::IMAGE_FILE => $this->mediaFileId ? MediaFile::find($this->mediaFileId)?->url() : null,
            Testimonial::IMAGE_URL => $this->externalUrl,
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.admin.testimonial-manager', [
            'testimonials' => Testimonial::query()->with('mediaFile')->latest()->latest('id')->paginate(self::PER_PAGE),
            'total' => Testimonial::count(),
            'imagePreview' => $this->showForm ? $this->imagePreview() : null,
        ]);
    }
}
