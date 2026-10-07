<?php

namespace App\Livewire\Admin;

use App\Models\MediaFile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectPhase;
use App\Support\RichText;
use App\Support\YouTube;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProjectForm extends Component
{
    public ?int $projectId = null;

    public string $title = '';

    /** Vacío = se genera a partir del título. */
    public string $slug = '';

    /** HTML del editor; se limpia al guardar. */
    public string $description = '';

    public string $location = '';

    /** Id de la categoría elegida; vacío = sin categoría. */
    public string $categoryId = '';

    // Strings para aceptar el campo vacío mientras se escribe.
    public string $executionPercentage = '';

    public string $year = '';

    public bool $isPublished = true;

    /**
     * Fases del proyecto. Cada una: uid, id (null si es nueva), type,
     * media_file_id, url (imagen por link), title y content (HTML).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $phases = [];

    /** file | youtube | null */
    public ?string $videoType = null;

    public ?int $videoMediaFileId = null;

    public ?string $videoYoutubeId = null;

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->fillFromProject($project);
        }
    }

    protected function fillFromProject(Project $project): void
    {
        $project->load('phases');

        $this->projectId = $project->id;
        $this->title = $project->title;
        $this->slug = $project->slug;
        $this->description = (string) $project->description;
        $this->location = (string) $project->location;
        $this->categoryId = $project->project_category_id === null ? '' : (string) $project->project_category_id;
        $this->executionPercentage = $project->execution_percentage === null ? '' : (string) $project->execution_percentage;
        $this->year = $project->year === null ? '' : (string) $project->year;
        $this->isPublished = $project->is_published;

        $this->phases = $project->phases->map(fn (ProjectPhase $phase) => [
            'uid' => Str::random(10),
            'id' => $phase->id,
            'type' => $phase->type,
            'media_file_id' => $phase->media_file_id,
            'url' => $phase->external_url,
            'title' => $phase->title,
            'content' => $phase->content,
        ])->all();

        $this->videoType = $project->video_type;
        $this->videoMediaFileId = $project->video_media_file_id;
        $this->videoYoutubeId = $project->video_youtube_id;
    }

    // --- Selector de archivos -------------------------------------------------

    public function choosePhaseImages(): void
    {
        $this->dispatch(
            'open-media-picker',
            context: 'phase',
            mode: 'image',
            multiple: true,
            external: ['image'],
            used: array_values(array_filter(array_column($this->phases, 'media_file_id'))),
        )->to(MediaPicker::class);
    }

    public function chooseVideo(): void
    {
        $this->dispatch(
            'open-media-picker',
            context: 'video',
            mode: 'video',
            multiple: false,
            external: ['youtube'],
            used: array_filter([$this->videoMediaFileId]),
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
        match ($context) {
            'phase' => $this->addPhases($items),
            'video' => $this->setVideo($items[0] ?? []),
            default => null,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function addPhases(array $items): void
    {
        $added = 0;

        foreach ($items as $item) {
            if (count($this->phases) >= Project::MAX_PHASES) {
                break;
            }

            $phase = null;

            if (($item['type'] ?? null) === ProjectPhase::TYPE_FILE && $this->isMediaOfKind($item['media_file_id'] ?? null, 'image')) {
                $phase = ['type' => ProjectPhase::TYPE_FILE, 'media_file_id' => (int) $item['media_file_id']];
            } elseif (($item['type'] ?? null) === ProjectPhase::TYPE_IMAGE_URL && $this->isValidUrl($item['url'] ?? null)) {
                $phase = ['type' => ProjectPhase::TYPE_IMAGE_URL, 'url' => (string) $item['url']];
            }

            if ($phase) {
                $this->phases[] = array_merge(['uid' => Str::random(10), 'id' => null, 'media_file_id' => null, 'url' => null, 'title' => null, 'content' => null], $phase);
                $added++;
            }
        }

        if ($added > 0) {
            $this->dispatch('notify', message: $added === 1 ? 'Fase agregada' : "{$added} fases agregadas");
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function setVideo(array $item): void
    {
        if (($item['type'] ?? null) === 'file' && $this->isMediaOfKind($item['media_file_id'] ?? null, 'video')) {
            $this->videoType = Project::VIDEO_FILE;
            $this->videoMediaFileId = (int) $item['media_file_id'];
            $this->videoYoutubeId = null;
        } elseif (($item['type'] ?? null) === 'youtube' && is_string($item['youtube_id'] ?? null) && preg_match('/^[A-Za-z0-9_-]{11}$/', $item['youtube_id'])) {
            $this->videoType = Project::VIDEO_YOUTUBE;
            $this->videoYoutubeId = $item['youtube_id'];
            $this->videoMediaFileId = null;
        }
    }

    public function removeVideo(): void
    {
        $this->videoType = null;
        $this->videoMediaFileId = null;
        $this->videoYoutubeId = null;
    }

    // --- Fases ------------------------------------------------------------------

    public function removePhase(string $uid): void
    {
        $this->phases = array_values(array_filter($this->phases, fn (array $phase) => $phase['uid'] !== $uid));
    }

    public function movePhase(string $uid, string $direction): void
    {
        $index = collect($this->phases)->search(fn (array $phase) => $phase['uid'] === $uid);

        if ($index === false) {
            return;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($this->phases[$target])) {
            return;
        }

        [$this->phases[$index], $this->phases[$target]] = [$this->phases[$target], $this->phases[$index]];
    }

    // --- Guardar ------------------------------------------------------------------

    public function save(): void
    {
        $this->normalize();
        $this->validate($this->rules(), $this->messages());
        $this->ensureMediaKinds();

        $attributes = [
            'title' => $this->title,
            'slug' => $this->slug !== '' ? $this->slug : Project::uniqueSlug($this->title, $this->projectId),
            'description' => $this->description !== '' ? $this->description : null,
            'location' => $this->blankToNull($this->location),
            'project_category_id' => $this->categoryId !== '' ? (int) $this->categoryId : null,
            'execution_percentage' => $this->executionPercentage !== '' ? (int) $this->executionPercentage : null,
            'year' => $this->year !== '' ? (int) $this->year : null,
            'is_published' => $this->isPublished,
            'video_type' => $this->videoType,
            'video_media_file_id' => $this->videoType === Project::VIDEO_FILE ? $this->videoMediaFileId : null,
            'video_youtube_id' => $this->videoType === Project::VIDEO_YOUTUBE ? $this->videoYoutubeId : null,
        ];

        $isNew = $this->projectId === null;

        DB::transaction(function () use ($attributes): void {
            if ($this->projectId) {
                $project = Project::findOrFail($this->projectId);
                $project->update($attributes);
            } else {
                $project = Project::create($attributes);
            }

            $keep = array_values(array_filter(array_column($this->phases, 'id')));
            $project->phases()->whereNotIn('id', $keep)->delete();

            foreach ($this->phases as $position => $phase) {
                $phaseAttributes = [
                    'type' => $phase['type'],
                    'media_file_id' => $phase['type'] === ProjectPhase::TYPE_FILE ? $phase['media_file_id'] : null,
                    'external_url' => $phase['type'] === ProjectPhase::TYPE_IMAGE_URL ? $phase['url'] : null,
                    'title' => $this->blankToNull($phase['title'] ?? null),
                    'content' => $phase['content'] ?: null,
                    'position' => $position,
                ];

                if (! empty($phase['id'])) {
                    $project->phases()->whereKey($phase['id'])->update($phaseAttributes);
                } else {
                    $project->phases()->create($phaseAttributes);
                }
            }
        });

        session()->flash('notify', $isNew ? 'Proyecto creado correctamente' : 'Proyecto guardado correctamente');

        $this->redirectRoute('admin.projects', navigate: true);
    }

    /**
     * Limpia los textos y el HTML del editor antes de validar.
     */
    protected function normalize(): void
    {
        $this->title = trim($this->title);
        $this->location = trim($this->location);
        $this->categoryId = trim($this->categoryId);
        $this->executionPercentage = trim($this->executionPercentage);
        $this->year = trim($this->year);
        $this->description = RichText::clean($this->description) ?? '';

        $slug = trim($this->slug);
        $this->slug = $slug === '' ? '' : Str::slug($slug);

        if ($slug !== '' && $this->slug === '') {
            throw ValidationException::withMessages(['slug' => 'El slug solo puede contener letras y números.']);
        }

        foreach ($this->phases as $index => $phase) {
            $this->phases[$index]['title'] = trim((string) ($phase['title'] ?? ''));
            $this->phases[$index]['content'] = RichText::clean($phase['content'] ?? null);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('projects', 'slug')->ignore($this->projectId)],
            'description' => ['nullable', 'string', 'max:20000'],
            'location' => ['nullable', 'string', 'max:150'],
            'categoryId' => ['nullable', 'integer', 'exists:project_categories,id'],
            'executionPercentage' => ['nullable', 'integer', 'between:0,100'],
            'year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 10)],
            'isPublished' => ['boolean'],
            'phases' => ['array', 'max:'.Project::MAX_PHASES],
            'phases.*.type' => ['required', Rule::in([ProjectPhase::TYPE_FILE, ProjectPhase::TYPE_IMAGE_URL])],
            'phases.*.media_file_id' => ['nullable', 'required_if:phases.*.type,'.ProjectPhase::TYPE_FILE, 'integer', 'exists:media_files,id'],
            'phases.*.url' => ['nullable', 'required_if:phases.*.type,'.ProjectPhase::TYPE_IMAGE_URL, 'url:http,https', 'max:2048'],
            'phases.*.title' => ['nullable', 'string', 'max:200'],
            'phases.*.content' => ['nullable', 'string', 'max:20000'],
            'videoType' => ['nullable', Rule::in([Project::VIDEO_FILE, Project::VIDEO_YOUTUBE])],
            'videoMediaFileId' => ['nullable', 'required_if:videoType,'.Project::VIDEO_FILE, 'integer', 'exists:media_files,id'],
            'videoYoutubeId' => ['nullable', 'required_if:videoType,'.Project::VIDEO_YOUTUBE, 'regex:/^[A-Za-z0-9_-]{11}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'title.required' => 'Ingresa el título del proyecto.',
            'title.max' => 'El título no puede superar los 200 caracteres.',
            'slug.unique' => 'Ese slug ya está en uso por otro proyecto.',
            'slug.max' => 'El slug no puede superar los 200 caracteres.',
            'description.max' => 'La descripción es demasiado larga.',
            'location.max' => 'La locación no puede superar los 150 caracteres.',
            'categoryId.integer' => 'Elige una categoría de la lista.',
            'categoryId.exists' => 'La categoría elegida ya no existe. Elige otra de la lista.',
            'executionPercentage.integer' => 'La ejecución debe ser un número entero.',
            'executionPercentage.between' => 'La ejecución debe estar entre 0 y 100.',
            'year.integer' => 'El año debe ser un número entero.',
            'year.between' => 'Ingresa un año válido.',
            'phases.max' => 'Solo puedes agregar hasta '.Project::MAX_PHASES.' fases.',
            'phases.*.title.max' => 'El título de una fase supera los 200 caracteres.',
            'phases.*.content.max' => 'El contenido de una fase es demasiado largo.',
            'phases.*.media_file_id.exists' => 'Uno de los archivos ya no existe. Quita esa fase y vuelve a intentarlo.',
            'videoMediaFileId.exists' => 'El video elegido ya no existe.',
        ];
    }

    /**
     * Las imágenes de las fases deben ser imágenes y el video, un video.
     */
    protected function ensureMediaKinds(): void
    {
        $imageIds = collect($this->phases)->where('type', ProjectPhase::TYPE_FILE)->pluck('media_file_id')->unique();

        if ($imageIds->isNotEmpty() && MediaFile::whereIn('id', $imageIds)->whereIn('extension', config('files.types.image'))->count() !== $imageIds->count()) {
            throw ValidationException::withMessages(['phases' => 'Solo se pueden usar imágenes en las fases.']);
        }

        if ($this->videoType === Project::VIDEO_FILE && ! $this->isMediaOfKind($this->videoMediaFileId, 'video')) {
            throw ValidationException::withMessages(['videoMediaFileId' => 'El archivo elegido no es un video.']);
        }
    }

    // --- Auxiliares -------------------------------------------------------------

    protected function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function isMediaOfKind(mixed $id, string $kind): bool
    {
        return is_numeric($id)
            && MediaFile::whereKey((int) $id)->whereIn('extension', config("files.types.{$kind}"))->exists();
    }

    protected function isValidUrl(mixed $url): bool
    {
        return is_string($url)
            && ! Validator::make(['url' => $url], ['url' => ['required', 'url:http,https', 'max:2048']])->fails();
    }

    /**
     * Prepara las fases y el video para mostrarlos a partir de datos del servidor.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function phaseCards(): array
    {
        $files = MediaFile::whereIn('id', array_filter(array_column($this->phases, 'media_file_id')))->get()->keyBy('id');

        return collect($this->phases)->map(function (array $phase) use ($files): array {
            $file = $phase['media_file_id'] ? $files->get($phase['media_file_id']) : null;

            return [
                'uid' => $phase['uid'],
                'content' => $phase['content'] ?? '',
                'src' => $phase['type'] === ProjectPhase::TYPE_FILE ? $file?->url() : $phase['url'],
                'name' => $phase['type'] === ProjectPhase::TYPE_FILE ? ($file->name ?? 'Archivo no disponible') : $phase['url'],
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function videoCard(): ?array
    {
        if ($this->videoType === Project::VIDEO_YOUTUBE && $this->videoYoutubeId) {
            return ['kind' => 'youtube', 'src' => YouTube::thumbnailUrl($this->videoYoutubeId), 'name' => 'Video de YouTube'];
        }

        if ($this->videoType === Project::VIDEO_FILE && $this->videoMediaFileId) {
            $file = MediaFile::find($this->videoMediaFileId);

            return ['kind' => 'video', 'src' => $file?->url(), 'name' => $file->name ?? 'Archivo no disponible'];
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.admin.project-form', [
            'phaseCards' => $this->phaseCards(),
            'videoCard' => $this->videoCard(),
            'categories' => ProjectCategory::query()->orderBy('name')->get(['id', 'name']),
            'slugSuggestion' => Str::slug($this->title),
            'maxPhases' => Project::MAX_PHASES,
        ])->title($this->projectId ? 'Editar proyecto' : 'Nuevo proyecto');
    }
}
