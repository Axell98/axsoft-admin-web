<?php

namespace App\Livewire\Admin;

use App\Models\MediaFile;
use App\Models\Popup;
use App\Models\PopupItem;
use App\Support\YouTube;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Los cambios se acumulan en el componente y solo se guardan en la base de datos
 * al pulsar «Guardar». La vista previa muestra siempre lo que hay en pantalla.
 */
#[Layout('components.layouts.app')]
#[Title('Pop-up')]
class PopupManager extends Component
{
    public string $title = '';

    public bool $isVisible = false;

    public bool $showHeader = false;

    public bool $showBorder = false;

    /** image | slider | video */
    public string $displayType = Popup::TYPE_IMAGE;

    public string $linkUrl = '';

    /**
     * Contenido del pop-up. Cada elemento: uid, id (null si es nuevo), type,
     * media_file_id, url (imagen por link) y youtube_id.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $items = [];

    public function mount(): void
    {
        $popup = Popup::main();

        $this->title = (string) $popup->title;
        $this->isVisible = $popup->is_visible;
        $this->showHeader = $popup->show_header;
        $this->showBorder = $popup->show_border;
        $this->displayType = $popup->display_type;
        $this->linkUrl = (string) $popup->link_url;

        $this->loadItems($popup);
    }

    protected function loadItems(Popup $popup): void
    {
        $this->items = $popup->exists
            ? $popup->items()->get()->map(fn (PopupItem $item) => [
                'uid' => Str::random(10),
                'id' => $item->id,
                'type' => $item->type,
                'media_file_id' => $item->media_file_id,
                'url' => $item->external_url,
                'youtube_id' => $item->youtube_id,
            ])->all()
            : [];
    }

    // --- Tipo de contenido -----------------------------------------------------

    /**
     * Al cambiar el tipo solo se conservan los elementos que sirven para el nuevo
     * (imágenes para imagen y slider, video para video) y, si es uno solo, el primero.
     */
    public function updatedDisplayType(): void
    {
        if (! in_array($this->displayType, Popup::types(), true)) {
            $this->displayType = Popup::TYPE_IMAGE;
        }

        $before = count($this->items);
        $compatible = array_values(array_filter($this->items, fn (array $item) => $this->isCompatible($item)));
        $this->items = $this->displayType === Popup::TYPE_SLIDER ? $compatible : array_slice($compatible, 0, 1);

        if (count($this->items) < $before) {
            $this->dispatch('notify', message: 'Se quitó el contenido que no corresponde al tipo elegido');
        }
    }

    // --- Selector de archivos ------------------------------------------------------

    public function choose(): void
    {
        $isVideo = $this->displayType === Popup::TYPE_VIDEO;

        $this->dispatch(
            'open-media-picker',
            context: 'popup',
            mode: $isVideo ? 'video' : 'image',
            multiple: $this->displayType === Popup::TYPE_SLIDER,
            external: $isVideo ? ['youtube'] : ['image'],
            used: array_values(array_filter(array_column($this->items, 'media_file_id'))),
        )->to(MediaPicker::class);
    }

    /**
     * Recibe lo elegido en el selector. Todo se vuelve a validar aquí porque el evento viaja por el navegador.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    #[On('media-picked')]
    public function onMediaPicked(string $context, array $items): void
    {
        if ($context !== 'popup') {
            return;
        }

        $valid = [];

        foreach ($items as $item) {
            $candidate = match ($item['type'] ?? null) {
                PopupItem::TYPE_FILE => ['type' => PopupItem::TYPE_FILE, 'media_file_id' => (int) ($item['media_file_id'] ?? 0)],
                PopupItem::TYPE_IMAGE_URL => ['type' => PopupItem::TYPE_IMAGE_URL, 'url' => $this->isValidUrl($item['url'] ?? null) ? (string) $item['url'] : null],
                PopupItem::TYPE_YOUTUBE => ['type' => PopupItem::TYPE_YOUTUBE, 'youtube_id' => $this->isYouTubeId($item['youtube_id'] ?? null) ? (string) $item['youtube_id'] : null],
                default => null,
            };

            if ($candidate === null) {
                continue;
            }

            $candidate = $this->newItem($candidate);

            if ($this->isCompatible($candidate) && $this->hasContent($candidate)) {
                $valid[] = $candidate;
            }
        }

        if ($valid === []) {
            return;
        }

        if ($this->displayType === Popup::TYPE_SLIDER) {
            $valid = array_slice($valid, 0, max(0, Popup::MAX_ITEMS - count($this->items)));
            $this->items = [...$this->items, ...$valid];
        } else {
            $this->items = [$valid[0]];
        }

        if ($valid !== []) {
            $this->dispatch('notify', message: count($valid) === 1 ? 'Contenido agregado' : count($valid).' imágenes agregadas');
        }
    }

    public function removeItem(string $uid): void
    {
        $this->items = array_values(array_filter($this->items, fn (array $item) => $item['uid'] !== $uid));
    }

    public function clearItems(): void
    {
        $this->items = [];
    }

    public function moveItem(string $uid, string $direction): void
    {
        $index = collect($this->items)->search(fn (array $item) => $item['uid'] === $uid);

        if ($index === false) {
            return;
        }

        $target = $direction === 'left' ? $index - 1 : $index + 1;

        if (! isset($this->items[$target])) {
            return;
        }

        [$this->items[$index], $this->items[$target]] = [$this->items[$target], $this->items[$index]];
    }

    // --- Guardar ------------------------------------------------------------------

    public function save(): void
    {
        $this->title = trim($this->title);
        $this->linkUrl = trim($this->linkUrl);

        $this->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'isVisible' => ['boolean'],
            'showHeader' => ['boolean'],
            'showBorder' => ['boolean'],
            'displayType' => ['required', Rule::in(Popup::types())],
            'linkUrl' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/|#)\S+$/i'],
            'items' => ['array', 'max:'.Popup::MAX_ITEMS],
            'items.*.type' => ['required', Rule::in([PopupItem::TYPE_FILE, PopupItem::TYPE_IMAGE_URL, PopupItem::TYPE_YOUTUBE])],
            'items.*.media_file_id' => ['nullable', 'required_if:items.*.type,'.PopupItem::TYPE_FILE, 'integer', 'exists:media_files,id'],
            'items.*.url' => ['nullable', 'required_if:items.*.type,'.PopupItem::TYPE_IMAGE_URL, 'url:http,https', 'max:2048'],
            'items.*.youtube_id' => ['nullable', 'required_if:items.*.type,'.PopupItem::TYPE_YOUTUBE, 'regex:/^[A-Za-z0-9_-]{11}$/'],
        ], [
            'title.max' => 'El título no puede superar los 120 caracteres.',
            'displayType.in' => 'Elige un tipo de contenido válido.',
            'linkUrl.regex' => 'Ingresa una URL válida (https://...), una ruta (/contacto) o un ancla (#nosotros).',
            'linkUrl.max' => 'El enlace es demasiado largo.',
            'items.max' => 'Solo puedes agregar hasta '.Popup::MAX_ITEMS.' imágenes.',
            'items.*.media_file_id.exists' => 'Uno de los archivos ya no existe. Quítalo y vuelve a intentarlo.',
        ]);

        $this->ensureContentMatchesType();

        DB::transaction(function (): void {
            $popup = Popup::query()->first() ?? new Popup;

            $popup->fill([
                'title' => $this->title !== '' ? $this->title : null,
                'display_type' => $this->displayType,
                'is_visible' => $this->isVisible,
                'show_header' => $this->showHeader,
                'show_border' => $this->showBorder,
                'link_url' => $this->linkUrl !== '' ? $this->linkUrl : null,
            ])->save();

            $keep = array_values(array_filter(array_column($this->items, 'id')));
            $popup->items()->whereNotIn('id', $keep)->delete();

            foreach ($this->items as $position => $item) {
                $attributes = [
                    'type' => $item['type'],
                    'media_file_id' => $item['type'] === PopupItem::TYPE_FILE ? $item['media_file_id'] : null,
                    'external_url' => $item['type'] === PopupItem::TYPE_IMAGE_URL ? $item['url'] : null,
                    'youtube_id' => $item['type'] === PopupItem::TYPE_YOUTUBE ? $item['youtube_id'] : null,
                    'position' => $position,
                ];

                if (! empty($item['id'])) {
                    $popup->items()->whereKey($item['id'])->update($attributes);
                } else {
                    $popup->items()->create($attributes);
                }
            }

            $this->loadItems($popup);
        });

        $this->dispatch('notify', message: 'Pop-up guardado correctamente');
    }

    /**
     * El contenido debe corresponder al tipo elegido y, si el pop-up está visible, estar completo.
     */
    protected function ensureContentMatchesType(): void
    {
        $isVideo = $this->displayType === Popup::TYPE_VIDEO;

        foreach ($this->items as $item) {
            if (! $this->isCompatible($item)) {
                throw ValidationException::withMessages([
                    'items' => $isVideo ? 'Un pop-up de video solo puede tener un video.' : 'Solo se pueden usar imágenes en este tipo de pop-up.',
                ]);
            }
        }

        if ($this->displayType !== Popup::TYPE_SLIDER && count($this->items) > 1) {
            throw ValidationException::withMessages([
                'items' => $isVideo ? 'Un pop-up de video solo puede tener un video.' : 'Un pop-up de imagen solo puede tener una imagen. Usa el tipo slider para varias.',
            ]);
        }

        if ($this->showHeader && $this->title === '') {
            throw ValidationException::withMessages(['title' => 'Escribe un título para mostrar el encabezado.']);
        }

        if (! $this->isVisible) {
            return;
        }

        $missing = match ($this->displayType) {
            Popup::TYPE_SLIDER => count($this->items) < 2 ? 'Un slider necesita al menos 2 imágenes.' : null,
            Popup::TYPE_VIDEO => $this->items === [] ? 'Elige el video del pop-up.' : null,
            default => $this->items === [] ? 'Elige la imagen del pop-up.' : null,
        };

        if ($missing) {
            throw ValidationException::withMessages(['items' => $missing.' O desactiva «Ventana visible» para guardar un borrador.']);
        }
    }

    // --- Auxiliares -------------------------------------------------------------

    /**
     * Indica si un elemento sirve para el tipo de pop-up elegido.
     *
     * @param  array<string, mixed>  $item
     */
    protected function isCompatible(array $item): bool
    {
        $wantsVideo = $this->displayType === Popup::TYPE_VIDEO;

        return match ($item['type'] ?? null) {
            PopupItem::TYPE_YOUTUBE => $wantsVideo,
            PopupItem::TYPE_IMAGE_URL => ! $wantsVideo,
            PopupItem::TYPE_FILE => is_numeric($item['media_file_id'] ?? null)
                && MediaFile::whereKey((int) $item['media_file_id'])->whereIn('extension', config('files.types.'.($wantsVideo ? 'video' : 'image')))->exists(),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function hasContent(array $item): bool
    {
        return match ($item['type']) {
            PopupItem::TYPE_FILE => (int) $item['media_file_id'] > 0,
            PopupItem::TYPE_IMAGE_URL => ! empty($item['url']),
            PopupItem::TYPE_YOUTUBE => ! empty($item['youtube_id']),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function newItem(array $attributes): array
    {
        return array_merge([
            'uid' => Str::random(10),
            'id' => null,
            'type' => PopupItem::TYPE_FILE,
            'media_file_id' => null,
            'url' => null,
            'youtube_id' => null,
        ], $attributes);
    }

    protected function isValidUrl(mixed $url): bool
    {
        return is_string($url)
            && ! Validator::make(['url' => $url], ['url' => ['required', 'url:http,https', 'max:2048']])->fails();
    }

    protected function isYouTubeId(mixed $id): bool
    {
        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1;
    }

    /**
     * Prepara cada elemento para mostrarlo: tipo, fuente y nombre, a partir de datos del servidor.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function cards(): array
    {
        $files = MediaFile::whereIn('id', array_filter(array_column($this->items, 'media_file_id')))->get()->keyBy('id');

        return collect($this->items)->map(function (array $item) use ($files): array {
            $card = ['uid' => $item['uid'], 'kind' => 'missing', 'src' => null, 'name' => 'Archivo no disponible'];

            if ($item['type'] === PopupItem::TYPE_YOUTUBE && $item['youtube_id']) {
                return [...$card, 'kind' => 'youtube', 'src' => YouTube::thumbnailUrl($item['youtube_id']), 'name' => 'Video de YouTube'];
            }

            if ($item['type'] === PopupItem::TYPE_IMAGE_URL && $item['url']) {
                return [...$card, 'kind' => 'image', 'src' => $item['url'], 'name' => $item['url']];
            }

            $file = $files->get($item['media_file_id']);

            if ($file) {
                return [...$card, 'kind' => $file->category() === 'video' ? 'video' : 'image', 'src' => $file->url(), 'name' => $file->name];
            }

            return $card;
        })->all();
    }

    public function render(): View
    {
        return view('livewire.admin.popup-manager', [
            'cards' => $this->cards(),
            'maxItems' => Popup::MAX_ITEMS,
        ]);
    }
}
