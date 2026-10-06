<?php

namespace App\Livewire\Admin;

use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Support\YouTube;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal reutilizable para elegir imágenes o videos del gestor de archivos,
 * o insertarlos por link (imagen) o YouTube.
 *
 * Se abre con el evento «open-media-picker» y, al confirmar, emite
 * «media-picked» con el contexto recibido y los elementos elegidos:
 *   ['type' => 'file', 'media_file_id' => 1]
 *   ['type' => 'image_url', 'url' => 'https://...']
 *   ['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']
 */
class MediaPicker extends Component
{
    private const STEP = 24;

    public bool $open = false;

    /** Identifica quién abrió el selector, para que solo ese reciba la respuesta. */
    public string $context = '';

    /** image | video | all */
    public string $mode = 'image';

    public bool $multiple = false;

    /**
     * Tipos de link permitidos: image y/o youtube.
     *
     * @var array<int, string>
     */
    public array $external = [];

    /**
     * Archivos que ya están en uso, para marcarlos.
     *
     * @var array<int, int>
     */
    public array $used = [];

    public string $search = '';

    /** all | image | video (solo cuando mode = all) */
    public string $type = 'all';

    public string $folder = '';

    public int $limit = self::STEP;

    /** @var array<int, int> */
    public array $selected = [];

    public bool $showExternal = false;

    /** image | youtube */
    public string $externalKind = 'image';

    public string $externalInput = '';

    /**
     * @param  array<int, string>  $external
     * @param  array<int, int>  $used
     */
    #[On('open-media-picker')]
    public function openFor(string $context, string $mode = 'image', bool $multiple = false, array $external = [], array $used = []): void
    {
        $this->reset('search', 'type', 'folder', 'selected', 'showExternal', 'externalInput');
        $this->resetErrorBag();

        $this->context = $context;
        $this->mode = in_array($mode, ['image', 'video', 'all'], true) ? $mode : 'image';
        $this->multiple = $multiple;
        $this->external = array_values(array_intersect($external, ['image', 'youtube']));
        $this->externalKind = $this->external[0] ?? 'image';
        $this->used = array_map('intval', $used);
        $this->limit = self::STEP;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetErrorBag();
    }

    public function updatedSearch(): void
    {
        $this->limit = self::STEP;
    }

    public function updatedType(): void
    {
        $this->limit = self::STEP;
    }

    public function updatedFolder(): void
    {
        $this->limit = self::STEP;
    }

    public function loadMore(): void
    {
        $this->limit += self::STEP;
    }

    public function toggle(int $id): void
    {
        if (! $this->multiple) {
            $this->selected = in_array($id, $this->selected, true) ? [] : [$id];

            return;
        }

        $this->selected = in_array($id, $this->selected, true)
            ? array_values(array_diff($this->selected, [$id]))
            : [...$this->selected, $id];
    }

    public function confirm(): void
    {
        $files = $this->pickableFiles()->whereIn('id', $this->selected)->get()->keyBy('id');

        $items = [];
        foreach ($this->selected as $id) {
            if ($files->has($id)) {
                $items[] = ['type' => 'file', 'media_file_id' => $id];
            }
        }

        $this->finish($items);
    }

    public function addExternal(): void
    {
        $this->externalInput = trim($this->externalInput);

        if (! in_array($this->externalKind, $this->external, true)) {
            return;
        }

        if ($this->externalKind === 'youtube') {
            $id = YouTube::extractId($this->externalInput);

            if ($id === null) {
                $this->addError('externalInput', 'No reconocemos ese enlace. Pega la URL del video o el código <iframe> de YouTube.');

                return;
            }

            $this->finish([['type' => 'youtube', 'youtube_id' => $id]]);

            return;
        }

        $this->validate([
            'externalInput' => ['required', 'url:http,https', 'max:2048'],
        ], [
            'externalInput.required' => 'Ingresa el enlace de la imagen.',
            'externalInput.url' => 'Ingresa una URL válida que empiece con http:// o https://.',
            'externalInput.max' => 'El enlace es demasiado largo.',
        ]);

        $this->finish([['type' => 'image_url', 'url' => $this->externalInput]]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function finish(array $items): void
    {
        $context = $this->context;

        $this->close();

        if ($items !== []) {
            $this->dispatch('media-picked', context: $context, items: $items);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function extensions(): array
    {
        $type = $this->mode === 'all' ? $this->type : $this->mode;

        return match ($type) {
            'image' => config('files.types.image'),
            'video' => config('files.types.video'),
            default => array_merge(config('files.types.image'), config('files.types.video')),
        };
    }

    /**
     * @return Builder<MediaFile>
     */
    protected function pickableFiles(): Builder
    {
        return MediaFile::query()
            ->whereIn('extension', $this->extensions())
            ->when($this->folder !== '', fn ($query) => $query->where('media_folder_id', (int) $this->folder))
            ->when(trim($this->search) !== '', fn ($query) => $query->nameLike($this->search));
    }

    public function render(): View
    {
        $data = [];

        if ($this->open) {
            $query = $this->pickableFiles();

            $data = [
                'files' => $query->clone()->latest()->latest('id')->limit($this->limit)->get(),
                'total' => $query->count(),
                'folders' => MediaFolder::orderBy('name')->get(),
            ];
        }

        return view('livewire.admin.media-picker', $data);
    }
}
