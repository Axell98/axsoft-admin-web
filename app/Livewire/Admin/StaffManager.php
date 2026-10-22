<?php

namespace App\Livewire\Admin;

use App\Models\MediaFile;
use App\Models\StaffMember;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Staff')]
class StaffManager extends Component
{
    use WithPagination;

    private const PER_PAGE = 24;

    public bool $showForm = false;

    /** Null = integrante nuevo. */
    public ?int $editingId = null;

    public string $name = '';

    public string $role = '';

    // String para aceptar el campo vacío mientras se escribe.
    public string $sortOrder = '';

    /** file | image_url | null */
    public ?string $imageType = null;

    public ?int $mediaFileId = null;

    public ?string $externalUrl = null;

    // --- Formulario ---------------------------------------------------------------

    public function create(): void
    {
        $this->resetForm();
        $this->sortOrder = (string) StaffMember::nextOrder();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $member = StaffMember::findOrFail($id);

        $this->resetForm();
        $this->editingId = $member->id;
        $this->name = $member->name;
        $this->role = (string) $member->role;
        $this->sortOrder = (string) $member->sort_order;
        $this->imageType = $member->image_type;
        $this->mediaFileId = $member->media_file_id;
        $this->externalUrl = $member->external_url;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset('editingId', 'name', 'role', 'sortOrder', 'imageType', 'mediaFileId', 'externalUrl');
        $this->resetErrorBag();
    }

    // --- Foto -----------------------------------------------------------------------

    public function chooseImage(): void
    {
        $this->dispatch(
            'open-media-picker',
            context: 'staff',
            mode: 'image',
            multiple: false,
            external: ['image'],
            used: $this->mediaFileId ? [$this->mediaFileId] : [],
        )->to(MediaPicker::class);
    }

    /**
     * Recibe la foto elegida. Se vuelve a validar aquí porque el evento viaja por el navegador.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    #[On('media-picked')]
    public function onMediaPicked(string $context, array $items): void
    {
        if ($context !== 'staff' || ! $this->showForm) {
            return;
        }

        $item = $items[0] ?? [];

        if (($item['type'] ?? null) === StaffMember::IMAGE_FILE && $this->isImageFile($item['media_file_id'] ?? null)) {
            $this->imageType = StaffMember::IMAGE_FILE;
            $this->mediaFileId = (int) $item['media_file_id'];
            $this->externalUrl = null;
        } elseif (($item['type'] ?? null) === StaffMember::IMAGE_URL && $this->isValidUrl($item['url'] ?? null)) {
            $this->imageType = StaffMember::IMAGE_URL;
            $this->externalUrl = (string) $item['url'];
            $this->mediaFileId = null;
        }
    }

    public function removeImage(): void
    {
        $this->reset('imageType', 'mediaFileId', 'externalUrl');
    }

    // --- Guardar y eliminar -------------------------------------------------------------

    public function save(): void
    {
        $this->name = trim($this->name);
        $this->role = trim($this->role);
        $this->sortOrder = trim($this->sortOrder);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'sortOrder' => ['required', 'integer', 'between:0,'.StaffMember::MAX_ORDER],
        ], [
            'name.required' => 'Ingresa el nombre.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'role.max' => 'El cargo no puede superar los 120 caracteres.',
            'sortOrder.required' => 'Ingresa el orden.',
            'sortOrder.integer' => 'El orden debe ser un número entero.',
            'sortOrder.between' => 'El orden debe estar entre 0 y '.StaffMember::MAX_ORDER.'.',
        ]);

        $this->ensureImageIsValid();

        $attributes = [
            'name' => $this->name,
            'role' => $this->role !== '' ? $this->role : null,
            'sort_order' => (int) $this->sortOrder,
            'image_type' => $this->imageType,
            'media_file_id' => $this->imageType === StaffMember::IMAGE_FILE ? $this->mediaFileId : null,
            'external_url' => $this->imageType === StaffMember::IMAGE_URL ? $this->externalUrl : null,
        ];

        if ($this->editingId !== null) {
            StaffMember::findOrFail($this->editingId)->update($attributes);
            $message = 'Integrante actualizado';
        } else {
            StaffMember::create($attributes);
            $message = 'Integrante registrado';
        }

        $this->closeForm();
        $this->resetPage();
        $this->dispatch('notify', message: $message);
    }

    /**
     * La foto, si hay, debe ser un archivo de imagen existente o un link http(s).
     */
    protected function ensureImageIsValid(): void
    {
        if ($this->imageType === null) {
            return;
        }

        $valid = match ($this->imageType) {
            StaffMember::IMAGE_FILE => $this->isImageFile($this->mediaFileId),
            StaffMember::IMAGE_URL => $this->isValidUrl($this->externalUrl),
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages(['imageType' => 'La foto elegida no es válida. Quítala y vuelve a elegirla.']);
        }
    }

    public function delete(int $id): void
    {
        StaffMember::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->closeForm();
        }

        $this->dispatch('notify', message: 'Integrante eliminado');
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
     * Foto que se muestra en el formulario, a partir de datos del servidor.
     */
    protected function imagePreview(): ?string
    {
        return match ($this->imageType) {
            StaffMember::IMAGE_FILE => $this->mediaFileId ? MediaFile::find($this->mediaFileId)?->url() : null,
            StaffMember::IMAGE_URL => $this->externalUrl,
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.admin.staff-manager', [
            'members' => StaffMember::query()->with('mediaFile')->ordered()->paginate(self::PER_PAGE),
            'imagePreview' => $this->showForm ? $this->imagePreview() : null,
        ]);
    }
}
