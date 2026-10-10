<?php

namespace App\Livewire\Admin;

use App\Models\MediaFile;
use App\Models\PageCover;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Portada de cada página interna del sitio. Se elige la página en un select y se
 * edita su portada; los cambios solo se guardan al pulsar «Guardar portada».
 */
#[Layout('components.layouts.app')]
#[Title('Portadas')]
class CoverManager extends Component
{
    /** Clave de la página interna elegida (ver config/covers.php). */
    #[Url(as: 'pagina')]
    public string $page = '';

    public string $title = '';

    /** file | image_url | null */
    public ?string $imageType = null;

    public ?int $mediaFileId = null;

    public ?string $externalUrl = null;

    public bool $isActive = true;

    public function mount(): void
    {
        $this->page = $this->validPage($this->page);
        $this->loadCover();
    }

    /**
     * Al elegir otra página se carga su portada (o un formulario vacío si aún no tiene).
     */
    public function updatedPage(): void
    {
        $this->page = $this->validPage($this->page);
        $this->resetErrorBag();
        $this->loadCover();
    }

    protected function loadCover(): void
    {
        $cover = PageCover::query()->where('page', $this->page)->first();

        $this->title = (string) $cover?->title;
        $this->imageType = $cover?->image_type;
        $this->mediaFileId = $cover?->media_file_id;
        $this->externalUrl = $cover?->external_url;
        $this->isActive = $cover ? $cover->is_active : true;
    }

    // --- Imagen ---------------------------------------------------------------------

    public function chooseImage(): void
    {
        $this->dispatch(
            'open-media-picker',
            context: 'cover',
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
        if ($context !== 'cover') {
            return;
        }

        $item = $items[0] ?? [];

        if (($item['type'] ?? null) === PageCover::IMAGE_FILE && $this->isImageFile($item['media_file_id'] ?? null)) {
            $this->imageType = PageCover::IMAGE_FILE;
            $this->mediaFileId = (int) $item['media_file_id'];
            $this->externalUrl = null;
        } elseif (($item['type'] ?? null) === PageCover::IMAGE_URL && $this->isValidUrl($item['url'] ?? null)) {
            $this->imageType = PageCover::IMAGE_URL;
            $this->externalUrl = (string) $item['url'];
            $this->mediaFileId = null;
        } else {
            return;
        }

        $this->resetErrorBag('imageType');
    }

    /**
     * Quita la imagen del formulario. Como el resto de cambios, se aplica al guardar.
     */
    public function removeImage(): void
    {
        $this->reset('imageType', 'mediaFileId', 'externalUrl');
        $this->resetErrorBag('imageType');
    }

    // --- Guardar ------------------------------------------------------------------

    public function save(): void
    {
        $this->title = trim($this->title);

        $this->validate([
            'page' => ['required', Rule::in(array_keys(PageCover::pages()))],
            'title' => ['nullable', 'string', 'max:150'],
            'isActive' => ['boolean'],
        ], [
            'page.required' => 'Elige la página interna.',
            'page.in' => 'Elige una página interna de la lista.',
            'title.max' => 'El título no puede superar los 150 caracteres.',
        ]);

        $this->ensureImageIsValid();

        PageCover::query()->updateOrCreate(['page' => $this->page], [
            'title' => $this->title !== '' ? $this->title : null,
            'image_type' => $this->imageType,
            'media_file_id' => $this->imageType === PageCover::IMAGE_FILE ? $this->mediaFileId : null,
            'external_url' => $this->imageType === PageCover::IMAGE_URL ? $this->externalUrl : null,
            'is_active' => $this->isActive,
        ]);

        $this->dispatch('notify', message: 'Portada guardada correctamente');
    }

    /**
     * La imagen, si hay, debe ser un archivo de imagen existente o un link http(s).
     * Sin imagen la página queda sin portada y el sitio usa la suya por defecto.
     */
    protected function ensureImageIsValid(): void
    {
        if ($this->imageType === null) {
            return;
        }

        $valid = match ($this->imageType) {
            PageCover::IMAGE_FILE => $this->isImageFile($this->mediaFileId),
            PageCover::IMAGE_URL => $this->isValidUrl($this->externalUrl),
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages(['imageType' => 'La imagen elegida no es válida o ya no existe. Elige otra o quítala.']);
        }
    }

    // --- Auxiliares -------------------------------------------------------------

    /**
     * Devuelve la clave si es una página configurada; si no, la primera de la lista.
     */
    protected function validPage(string $page): string
    {
        $pages = PageCover::pages();

        return array_key_exists($page, $pages) ? $page : (string) (array_key_first($pages) ?? '');
    }

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
            PageCover::IMAGE_FILE => $this->mediaFileId ? MediaFile::find($this->mediaFileId)?->url() : null,
            PageCover::IMAGE_URL => $this->externalUrl,
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.admin.cover-manager', [
            'pages' => PageCover::pages(),
            'covers' => PageCover::query()->with('mediaFile')->get()->keyBy('page'),
            'imagePreview' => $this->imagePreview(),
            'size' => config('covers.recommended_size'),
            'formats' => config('files.types.image'),
        ]);
    }
}
