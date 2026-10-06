<?php

namespace App\Livewire\Admin;

use App\Models\BannerSlide;
use App\Models\BannerSlider;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Support\YouTube;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Los cambios (opciones y banners) se acumulan en el componente y solo se
 * guardan en la base de datos al pulsar «Guardar cambios».
 */
#[Layout('components.layouts.app')]
#[Title('Banner')]
class BannerManager extends Component
{
    private const PICKER_STEP = 24;

    // Opciones del slider. El porcentaje es string para aceptar el campo vacío mientras se escribe.
    public string $screenPercentage = '100';

    public bool $showArrows = true;

    public bool $showIndicators = true;

    /**
     * Banners en pantalla. Cada uno: uid, id (null si es nuevo), type,
     * media_file_id, url (imagen por link), youtube_id y link_url.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $slides = [];

    public bool $dirty = false;

    // Modal «Seleccionar imagen»
    public bool $showPicker = false;

    public string $pickerSearch = '';

    /** all | image | video */
    public string $pickerType = 'all';

    public string $pickerFolder = '';

    public int $pickerLimit = self::PICKER_STEP;

    /** @var array<int, int> */
    public array $pickerSelected = [];

    public bool $showExternalForm = false;

    /** image | youtube */
    public string $externalKind = 'image';

    public string $externalInput = '';

    // Modal «Enlace» de un banner
    public ?string $editingLinkUid = null;

    public string $linkUrl = '';

    // Modal «Texto» de un banner (solo disponible con los indicadores activos)
    public ?string $editingTextUid = null;

    public string $textTitle = '';

    public string $textDescription = '';

    public function mount(): void
    {
        $slider = BannerSlider::main();

        $this->screenPercentage = (string) $slider->screen_percentage;
        $this->showArrows = $slider->show_arrows;
        $this->showIndicators = $slider->show_indicators;

        $this->loadSlides($slider);
    }

    protected function loadSlides(BannerSlider $slider): void
    {
        $this->slides = $slider->slides()->get()->map(fn (BannerSlide $slide) => [
            'uid' => Str::random(10),
            'id' => $slide->id,
            'type' => $slide->type,
            'media_file_id' => $slide->media_file_id,
            'url' => $slide->external_url,
            'youtube_id' => $slide->youtube_id,
            'title' => $slide->title,
            'description' => $slide->description,
            'link_url' => $slide->link_url,
        ])->all();

        $this->dirty = false;
    }

    public function updatedScreenPercentage(): void
    {
        $this->dirty = true;
    }

    public function updatedShowArrows(): void
    {
        $this->dirty = true;
    }

    public function updatedShowIndicators(): void
    {
        $this->dirty = true;
    }

    // --- Buscar imágenes (archivos del gestor) ---------------------------------

    public function openPicker(): void
    {
        $this->reset('pickerSearch', 'pickerType', 'pickerFolder', 'pickerSelected', 'showExternalForm', 'externalInput');
        $this->pickerLimit = self::PICKER_STEP;
        $this->resetErrorBag();
        $this->showPicker = true;
    }

    public function closePicker(): void
    {
        $this->showPicker = false;
        $this->resetErrorBag();
    }

    public function updatedPickerSearch(): void
    {
        $this->pickerLimit = self::PICKER_STEP;
    }

    public function updatedPickerType(): void
    {
        $this->pickerLimit = self::PICKER_STEP;
    }

    public function updatedPickerFolder(): void
    {
        $this->pickerLimit = self::PICKER_STEP;
    }

    public function loadMore(): void
    {
        $this->pickerLimit += self::PICKER_STEP;
    }

    public function togglePick(int $id): void
    {
        if (in_array($id, $this->pickerSelected, true)) {
            $this->pickerSelected = array_values(array_diff($this->pickerSelected, [$id]));
        } else {
            $this->pickerSelected[] = $id;
        }
    }

    public function addPicked(): void
    {
        $files = $this->pickableFiles()
            ->whereIn('id', $this->pickerSelected)
            ->get()
            ->sortBy(fn (MediaFile $file) => array_search($file->id, $this->pickerSelected, true));

        foreach ($files as $file) {
            if (! $this->hasRoom()) {
                break;
            }

            $this->slides[] = $this->newSlide([
                'type' => BannerSlide::TYPE_FILE,
                'media_file_id' => $file->id,
            ]);
        }

        $this->afterAdding(count($files));
    }

    // --- Insertar vía link --------------------------------------------------------

    public function addExternal(): void
    {
        $this->externalInput = trim($this->externalInput);

        if (! $this->hasRoom()) {
            $this->addError('externalInput', 'Alcanzaste el máximo de '.BannerSlider::MAX_SLIDES.' banners.');

            return;
        }

        if ($this->externalKind === 'youtube') {
            $id = YouTube::extractId($this->externalInput);

            if ($id === null) {
                $this->addError('externalInput', 'No reconocemos ese enlace. Pega la URL del video o el código <iframe> de YouTube.');

                return;
            }

            $slide = ['type' => BannerSlide::TYPE_YOUTUBE, 'youtube_id' => $id];
        } else {
            $this->validate([
                'externalInput' => ['required', 'url:http,https', 'max:2048'],
            ], [
                'externalInput.required' => 'Ingresa el enlace de la imagen.',
                'externalInput.url' => 'Ingresa una URL válida que empiece con http:// o https://.',
                'externalInput.max' => 'El enlace es demasiado largo.',
            ]);

            $slide = ['type' => BannerSlide::TYPE_IMAGE_URL, 'url' => $this->externalInput];
        }

        $this->slides[] = $this->newSlide($slide);
        $this->afterAdding(1);
    }

    protected function afterAdding(int $count): void
    {
        $this->dirty = $this->dirty || $count > 0;
        $this->closePicker();

        if ($count > 0) {
            $this->dispatch('notify', message: $count === 1 ? 'Banner agregado' : "{$count} banners agregados");
        }
    }

    // --- Banners ----------------------------------------------------------------

    public function removeSlide(string $uid): void
    {
        $this->slides = array_values(array_filter($this->slides, fn (array $slide) => $slide['uid'] !== $uid));
        $this->dirty = true;
    }

    public function moveSlide(string $uid, string $direction): void
    {
        $index = $this->indexOf($uid);

        if ($index === null) {
            return;
        }

        $target = $direction === 'left' ? $index - 1 : $index + 1;

        if (! isset($this->slides[$target])) {
            return;
        }

        [$this->slides[$index], $this->slides[$target]] = [$this->slides[$target], $this->slides[$index]];
        $this->dirty = true;
    }

    public function openLinkModal(string $uid): void
    {
        $index = $this->indexOf($uid);

        if ($index === null) {
            return;
        }

        $this->resetErrorBag();
        $this->editingLinkUid = $uid;
        $this->linkUrl = (string) ($this->slides[$index]['link_url'] ?? '');
    }

    public function closeLinkModal(): void
    {
        $this->editingLinkUid = null;
        $this->resetErrorBag();
    }

    public function saveLink(): void
    {
        $this->linkUrl = trim($this->linkUrl);

        $this->validate(['linkUrl' => $this->linkRules()], $this->linkMessages('linkUrl'));

        $index = $this->editingLinkUid ? $this->indexOf($this->editingLinkUid) : null;

        if ($index !== null) {
            $this->slides[$index]['link_url'] = $this->linkUrl === '' ? null : $this->linkUrl;
            $this->dirty = true;
        }

        $this->closeLinkModal();
    }

    public function openTextModal(string $uid): void
    {
        $index = $this->indexOf($uid);

        // El texto y la descripción solo se editan con «Mostrar indicadores» activo.
        if ($index === null || ! $this->showIndicators) {
            return;
        }

        $this->resetErrorBag();
        $this->editingTextUid = $uid;
        $this->textTitle = (string) ($this->slides[$index]['title'] ?? '');
        $this->textDescription = (string) ($this->slides[$index]['description'] ?? '');
    }

    public function closeTextModal(): void
    {
        $this->editingTextUid = null;
        $this->resetErrorBag();
    }

    public function saveText(): void
    {
        $this->textTitle = trim($this->textTitle);
        $this->textDescription = trim($this->textDescription);

        $this->validate([
            'textTitle' => ['nullable', 'string', 'max:120'],
            'textDescription' => ['nullable', 'string', 'max:500'],
        ], [
            'textTitle.max' => 'El texto no puede superar los 120 caracteres.',
            'textDescription.max' => 'La descripción no puede superar los 500 caracteres.',
        ]);

        $index = $this->editingTextUid ? $this->indexOf($this->editingTextUid) : null;

        if ($index !== null && $this->showIndicators) {
            $this->slides[$index]['title'] = $this->textTitle === '' ? null : $this->textTitle;
            $this->slides[$index]['description'] = $this->textDescription === '' ? null : $this->textDescription;
            $this->dirty = true;
        }

        $this->closeTextModal();
    }

    // --- Guardar ------------------------------------------------------------------

    public function save(): void
    {
        $this->validate([
            'screenPercentage' => ['required', 'integer', 'between:'.BannerSlider::MIN_PERCENTAGE.','.BannerSlider::MAX_PERCENTAGE],
            'showArrows' => ['boolean'],
            'showIndicators' => ['boolean'],
            'slides' => ['array', 'max:'.BannerSlider::MAX_SLIDES],
            'slides.*.type' => ['required', Rule::in([BannerSlide::TYPE_FILE, BannerSlide::TYPE_IMAGE_URL, BannerSlide::TYPE_YOUTUBE])],
            'slides.*.media_file_id' => ['nullable', 'required_if:slides.*.type,'.BannerSlide::TYPE_FILE, 'integer', 'exists:media_files,id'],
            'slides.*.url' => ['nullable', 'required_if:slides.*.type,'.BannerSlide::TYPE_IMAGE_URL, 'url:http,https', 'max:2048'],
            'slides.*.youtube_id' => ['nullable', 'required_if:slides.*.type,'.BannerSlide::TYPE_YOUTUBE, 'regex:/^[A-Za-z0-9_-]{11}$/'],
            'slides.*.title' => ['nullable', 'string', 'max:120'],
            'slides.*.description' => ['nullable', 'string', 'max:500'],
            'slides.*.link_url' => $this->linkRules(),
        ], [
            'slides.*.title.max' => 'Uno de los textos supera los 120 caracteres.',
            'slides.*.description.max' => 'Una de las descripciones supera los 500 caracteres.',
            'screenPercentage.required' => 'Ingresa el porcentaje de pantalla.',
            'screenPercentage.integer' => 'El porcentaje debe ser un número entero.',
            'screenPercentage.between' => 'El porcentaje debe estar entre '.BannerSlider::MIN_PERCENTAGE.' y '.BannerSlider::MAX_PERCENTAGE.'.',
            'slides.max' => 'Solo puedes tener hasta '.BannerSlider::MAX_SLIDES.' banners.',
            'slides.*.media_file_id.exists' => 'Uno de los archivos ya no existe. Quita ese banner y vuelve a intentarlo.',
            'slides.*.link_url.regex' => 'Uno de los enlaces no es válido.',
        ]);

        $this->ensureFilesAreMedia();

        $slider = BannerSlider::main();

        DB::transaction(function () use ($slider): void {
            $slider->update([
                'screen_percentage' => (int) $this->screenPercentage,
                'show_arrows' => $this->showArrows,
                'show_indicators' => $this->showIndicators,
            ]);

            $keep = array_values(array_filter(array_column($this->slides, 'id')));
            $slider->slides()->whereNotIn('id', $keep)->delete();

            foreach ($this->slides as $position => $slide) {
                $attributes = [
                    'type' => $slide['type'],
                    'media_file_id' => $slide['type'] === BannerSlide::TYPE_FILE ? $slide['media_file_id'] : null,
                    'external_url' => $slide['type'] === BannerSlide::TYPE_IMAGE_URL ? $slide['url'] : null,
                    'youtube_id' => $slide['type'] === BannerSlide::TYPE_YOUTUBE ? $slide['youtube_id'] : null,
                    'title' => ($slide['title'] ?? '') !== '' ? $slide['title'] : null,
                    'description' => ($slide['description'] ?? '') !== '' ? $slide['description'] : null,
                    'link_url' => ($slide['link_url'] ?? '') !== '' ? $slide['link_url'] : null,
                    'position' => $position,
                ];

                if (! empty($slide['id'])) {
                    $slider->slides()->whereKey($slide['id'])->update($attributes);
                } else {
                    $slider->slides()->create($attributes);
                }
            }
        });

        $this->loadSlides($slider);
        $this->dispatch('notify', message: 'Cambios guardados correctamente');
    }

    /**
     * Los banners de tipo archivo solo pueden ser imágenes o videos.
     */
    protected function ensureFilesAreMedia(): void
    {
        $ids = collect($this->slides)->where('type', BannerSlide::TYPE_FILE)->pluck('media_file_id')->all();

        if ($ids === []) {
            return;
        }

        $valid = MediaFile::whereIn('id', $ids)->whereIn('extension', $this->mediaExtensions())->count();

        if ($valid !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'slides' => 'Solo se pueden usar imágenes o videos como banner.',
            ]);
        }
    }

    // --- Auxiliares -------------------------------------------------------------

    /**
     * @return array<int, mixed>
     */
    protected function linkRules(): array
    {
        // Acepta URLs http(s), rutas internas (/contacto) y anclas (#nosotros). Rechaza javascript:, data:, etc.
        return ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/|#)\S+$/i'];
    }

    /**
     * @return array<string, string>
     */
    protected function linkMessages(string $field): array
    {
        return [
            "{$field}.regex" => 'Ingresa una URL válida (https://...), una ruta (/contacto) o un ancla (#nosotros).',
            "{$field}.max" => 'El enlace es demasiado largo.',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function newSlide(array $attributes): array
    {
        return array_merge([
            'uid' => Str::random(10),
            'id' => null,
            'type' => BannerSlide::TYPE_FILE,
            'media_file_id' => null,
            'url' => null,
            'youtube_id' => null,
            'title' => null,
            'description' => null,
            'link_url' => null,
        ], $attributes);
    }

    protected function hasRoom(): bool
    {
        return count($this->slides) < BannerSlider::MAX_SLIDES;
    }

    protected function indexOf(string $uid): ?int
    {
        foreach ($this->slides as $index => $slide) {
            if ($slide['uid'] === $uid) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function mediaExtensions(): array
    {
        return array_merge(config('files.types.image'), config('files.types.video'));
    }

    /**
     * Archivos del gestor que se pueden usar como banner, según los filtros del modal.
     *
     * @return Builder<MediaFile>
     */
    protected function pickableFiles(): Builder
    {
        $extensions = in_array($this->pickerType, ['image', 'video'], true)
            ? config("files.types.{$this->pickerType}")
            : $this->mediaExtensions();

        return MediaFile::query()
            ->whereIn('extension', $extensions)
            ->when($this->pickerFolder !== '', fn ($query) => $query->where('media_folder_id', (int) $this->pickerFolder))
            ->when(trim($this->pickerSearch) !== '', fn ($query) => $query->nameLike($this->pickerSearch));
    }

    /**
     * Prepara cada banner para mostrarlo: tipo, fuente y nombre, a partir de datos del servidor.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function cards(): array
    {
        $files = MediaFile::whereIn('id', array_filter(array_column($this->slides, 'media_file_id')))->get()->keyBy('id');

        return collect($this->slides)->map(function (array $slide) use ($files): array {
            $card = [
                'uid' => $slide['uid'],
                'link' => $slide['link_url'] ?? null,
                'title' => $slide['title'] ?? null,
                'description' => $slide['description'] ?? null,
                'kind' => 'missing',
                'src' => null,
                'name' => 'Archivo no disponible',
            ];

            if ($slide['type'] === BannerSlide::TYPE_YOUTUBE && $slide['youtube_id']) {
                return [...$card, 'kind' => 'youtube', 'src' => YouTube::thumbnailUrl($slide['youtube_id']), 'name' => 'Video de YouTube'];
            }

            if ($slide['type'] === BannerSlide::TYPE_IMAGE_URL && $slide['url']) {
                return [...$card, 'kind' => 'image', 'src' => $slide['url'], 'name' => $slide['url']];
            }

            $file = $files->get($slide['media_file_id']);

            if ($file) {
                return [...$card, 'kind' => $file->category() === 'video' ? 'video' : 'image', 'src' => $file->url(), 'name' => $file->name];
            }

            return $card;
        })->all();
    }

    public function render(): View
    {
        $data = [
            'cards' => $this->cards(),
            'percentage' => max(BannerSlider::MIN_PERCENTAGE, min(BannerSlider::MAX_PERCENTAGE, (int) $this->screenPercentage)),
            'maxSlides' => BannerSlider::MAX_SLIDES,
            'addedFileIds' => array_filter(array_column($this->slides, 'media_file_id')),
        ];

        if ($this->showPicker) {
            $query = $this->pickableFiles();
            $total = (clone $query)->count();

            $data += [
                'pickerFiles' => $query->latest()->latest('id')->limit($this->pickerLimit)->get(),
                'pickerTotal' => $total,
                'folders' => MediaFolder::orderBy('name')->get(),
            ];
        }

        $data['editingCard'] = collect($data['cards'])->firstWhere('uid', $this->editingLinkUid);
        $data['editingTextCard'] = collect($data['cards'])->firstWhere('uid', $this->editingTextUid);

        return view('livewire.admin.banner-manager', $data);
    }
}
