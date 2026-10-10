<?php

namespace App\Livewire\Admin;

use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Support\Thumbnail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Archivos')]
class FileManager extends Component
{
    use WithFileUploads, WithPagination;

    private const PER_PAGE = 15;

    /** Carpeta que se está viendo; null = todos los archivos. */
    #[Url(as: 'carpeta')]
    public ?int $folderId = null;

    #[Url(as: 'buscar')]
    public string $search = '';

    /** @var array<int, int|string> */
    public array $selected = [];

    /** Carpeta destino de las subidas; '' = sin carpeta. */
    public string $uploadFolder = '';

    /** Archivo que se está subiendo en este momento (se sube de uno en uno). */
    public ?TemporaryUploadedFile $upload = null;

    public bool $showFolderModal = false;

    public ?int $editingFolderId = null;

    public string $folderName = '';

    public bool $showMoveModal = false;

    /** @var array<int, int> */
    public array $moveIds = [];

    /** Carpeta destino al mover archivos; '' = sin carpeta. */
    public string $moveTo = '';

    public function mount(): void
    {
        if ($this->folderId !== null && ! MediaFolder::whereKey($this->folderId)->exists()) {
            $this->folderId = null;
        }

        $this->uploadFolder = (string) ($this->folderId ?? '');
    }

    public function selectFolder(?int $id = null): void
    {
        $this->folderId = $id;
        $this->uploadFolder = (string) ($id ?? '');
        $this->selected = [];
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    // --- Carpetas -----------------------------------------------------------

    public function openFolderModal(?int $id = null): void
    {
        $this->resetErrorBag();
        $this->editingFolderId = $id;
        $this->folderName = $id ? (string) MediaFolder::findOrFail($id)->name : '';
        $this->showFolderModal = true;
    }

    public function closeFolderModal(): void
    {
        $this->showFolderModal = false;
        $this->resetErrorBag();
    }

    public function saveFolder(): void
    {
        $this->folderName = trim($this->folderName);

        $this->validate([
            'folderName' => [
                'required',
                'string',
                'max:60',
                Rule::unique('media_folders', 'name')->ignore($this->editingFolderId),
            ],
        ], [
            'folderName.required' => 'Ingresa un nombre para la carpeta.',
            'folderName.max' => 'El nombre no puede superar los 60 caracteres.',
            'folderName.unique' => 'Ya existe una carpeta con ese nombre.',
        ]);

        if ($this->editingFolderId) {
            MediaFolder::findOrFail($this->editingFolderId)->update(['name' => $this->folderName]);
            $message = 'Carpeta renombrada';
        } else {
            $folder = MediaFolder::create(['name' => $this->folderName]);
            $this->selectFolder($folder->id);
            $message = 'Carpeta creada';
        }

        $this->closeFolderModal();
        $this->dispatch('notify', message: $message);
    }

    public function deleteFolder(int $id): void
    {
        MediaFolder::findOrFail($id)->delete();

        if ($this->folderId === $id) {
            $this->selectFolder(null);
        }

        if ($this->uploadFolder === (string) $id) {
            $this->uploadFolder = '';
        }

        $this->dispatch('notify', message: 'Carpeta eliminada');
    }

    // --- Archivos -----------------------------------------------------------

    public function deleteFile(int $id): void
    {
        MediaFile::findOrFail($id)->delete();

        $this->selected = array_values(array_diff($this->selected, [$id, (string) $id]));
        $this->dispatch('notify', message: 'Archivo eliminado');
    }

    public function deleteSelected(): void
    {
        $ids = array_map('intval', $this->selected);

        MediaFile::whereIn('id', $ids)->get()->each->delete();

        $this->selected = [];
        $this->dispatch('notify', message: count($ids) === 1 ? 'Archivo eliminado' : count($ids).' archivos eliminados');
    }

    /**
     * Abre el modal para mover un archivo, o los seleccionados si no se indica ninguno.
     */
    public function openMoveModal(?int $id = null): void
    {
        $ids = $id !== null ? [$id] : array_map('intval', $this->selected);

        if ($ids === []) {
            return;
        }

        $this->resetErrorBag();
        $this->moveIds = $ids;
        $this->moveTo = '';
        $this->showMoveModal = true;
    }

    public function closeMoveModal(): void
    {
        $this->showMoveModal = false;
        $this->moveIds = [];
        $this->resetErrorBag();
    }

    public function moveFiles(): void
    {
        $this->validate([
            'moveTo' => ['nullable', Rule::exists('media_folders', 'id')],
        ], [
            'moveTo.exists' => 'La carpeta elegida ya no existe.',
        ]);

        $folder = $this->moveTo !== '' ? MediaFolder::findOrFail((int) $this->moveTo) : null;

        $moved = MediaFile::whereIn('id', $this->moveIds)->update(['media_folder_id' => $folder?->id]);

        $this->selected = [];
        $this->closeMoveModal();
        $this->resetPage();

        $this->dispatch('notify', message: ($moved === 1 ? 'Archivo movido' : "{$moved} archivos movidos").' a '.($folder ? "«{$folder->name}»" : 'Sin carpeta'));
    }

    /**
     * Guarda el archivo que el navegador acaba de subir como temporal.
     * Se invoca una vez por archivo para poder mostrar el avance individual.
     *
     * @return array{ok: bool, error?: string}
     */
    public function storeUpload(): array
    {
        $file = $this->upload;

        if (! $file) {
            return ['ok' => false, 'error' => 'No se recibió el archivo.'];
        }

        try {
            $name = $this->cleanName($file->getClientOriginalName());
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (! in_array($extension, MediaFile::allowedExtensions(), true)) {
                return ['ok' => false, 'error' => 'Tipo de archivo no permitido'.($extension ? " (.{$extension})" : '').'.'];
            }

            if ($file->getSize() > MediaFile::maxUploadKb() * 1024) {
                return ['ok' => false, 'error' => 'El archivo supera el tamaño máximo permitido.'];
            }

            $folderId = ctype_digit($this->uploadFolder) && MediaFolder::whereKey((int) $this->uploadFolder)->exists()
                ? (int) $this->uploadFolder
                : null;

            $path = $file->storeAs(
                'files/'.now()->format('Y/m'),
                Str::random(40).'.'.$extension,
                MediaFile::DISK,
            );

            $media = MediaFile::create([
                'media_folder_id' => $folderId,
                'name' => $name,
                'path' => $path,
                'extension' => $extension,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            // La miniatura es opcional: si no se puede crear ahora, se crea al mostrar el archivo.
            Thumbnail::generate($media);

            return ['ok' => true];
        } finally {
            $file->delete();
            $this->upload = null;
        }
    }

    /**
     * Normaliza el nombre del archivo: sin rutas, espacios convertidos en «_»,
     * tildes y ñ transliteradas y sin caracteres especiales.
     * Ej.: «Diseño núcleo (final) #2.PDF» → «Diseno_nucleo_final_2.pdf».
     */
    protected function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));

        $extension = strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', pathinfo($name, PATHINFO_EXTENSION)));
        $base = Str::ascii(pathinfo($name, PATHINFO_FILENAME));

        $base = (string) preg_replace('/[\s.]+/', '_', $base);
        $base = (string) preg_replace('/[^A-Za-z0-9_-]/', '', $base);
        $base = trim((string) preg_replace('/_{2,}/', '_', $base), '_-');
        $base = substr($base, 0, 150);

        if ($base === '') {
            $base = 'archivo';
        }

        return $extension !== '' ? "{$base}.{$extension}" : $base;
    }

    public function render(): View
    {
        $files = MediaFile::query()
            ->when($this->folderId, fn ($query, $id) => $query->where('media_folder_id', $id))
            ->when(trim($this->search) !== '', fn ($query) => $query->nameLike($this->search))
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return view('livewire.admin.file-manager', [
            'folders' => MediaFolder::withCount('files')->orderBy('name')->get(),
            'totalFiles' => MediaFile::count(),
            'files' => $files,
            'pageIds' => $files->pluck('id')->all(),
            'maxKb' => MediaFile::maxUploadKb(),
            'extensions' => MediaFile::allowedExtensions(),
        ]);
    }
}
