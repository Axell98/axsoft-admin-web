<div class="mx-auto max-w-5xl">
    <form wire:submit="save">
        {{-- Cabecera --}}
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <a href="{{ route('admin.projects') }}" wire:navigate class="mb-2 inline-flex items-center gap-1.5 text-sm text-slate-500 transition hover:text-indigo-600">
                    <x-icon name="arrow-left" class="size-4" />
                    Volver a proyectos
                </a>
                <h2 class="text-xl font-semibold tracking-tight uppercase">{{ $projectId ? 'Editar proyecto' : 'Nuevo proyecto' }}</h2>
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
            >
                <svg wire:loading wire:target="save" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <x-icon wire:loading.remove wire:target="save" name="save" class="size-4" />
                <span wire:loading.remove wire:target="save">{{ $projectId ? 'Guardar cambios' : 'Crear proyecto' }}</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        </div>

        {{-- Datos del proyecto --}}
        <section class="mt-8">
            <x-form-section title="Datos del proyecto" />

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <x-field label="Título" name="title" wire:model="title" maxlength="200" placeholder="Ej. Diseño edificio multifamiliar" class="lg:col-span-2" />

                <x-field
                    label="Slug (URL)"
                    name="slug"
                    wire:model="slug"
                    maxlength="200"
                    placeholder="{{ $slugSuggestion ?: 'se-genera-del-titulo' }}"
                    hint="Déjalo vacío para generarlo del título{{ $slugSuggestion && $slug === '' ? ': '.$slugSuggestion : '' }}."
                />

                <div class="md:col-span-2 lg:col-span-3">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Descripción</label>
                    <x-rich-editor id="description-editor" model="description" :value="$description" placeholder="Describe el proyecto..." />
                    @error('description')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-field label="Locación" name="location" wire:model="location" maxlength="150" placeholder="Ej. San Borja, Lima" />
                <x-field label="Categoría" name="category" wire:model="category" maxlength="100" placeholder="Ej. Residencial" />
                <div class="grid grid-cols-2 gap-5">
                    <x-field label="Ejecución (%)" name="executionPercentage" type="number" min="0" max="100" wire:model="executionPercentage" placeholder="100" />
                    <x-field label="Año" name="year" type="number" min="1900" wire:model="year" placeholder="{{ now()->year }}" />
                </div>
            </div>

            <div class="mt-4 max-w-sm rounded-xl border border-slate-200 bg-white px-4">
                <x-toggle label="Visible en el sitio web" wire:model="isPublished" />
            </div>
        </section>

        {{-- Fases --}}
        <section class="mt-10">
            <x-form-section title="Fases del proyecto" />

            <p class="mb-4 text-sm text-slate-500">
                Cada imagen representa una fase y tiene su propio título y contenido. La primera imagen es la portada del proyecto.
            </p>

            @error('phases')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror
            @error('phases.*')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            <div class="space-y-4">
                @foreach ($phaseCards as $index => $card)
                    <div wire:key="phase-{{ $card['uid'] }}" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold tracking-wide text-indigo-700 uppercase">Fase {{ $index + 1 }}</span>

                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="movePhase('{{ $card['uid'] }}', 'up')" @disabled($index === 0) title="Subir" aria-label="Subir fase" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 disabled:opacity-30 disabled:hover:bg-transparent">
                                    <x-icon name="arrow-up" class="size-4" />
                                </button>
                                <button type="button" wire:click="movePhase('{{ $card['uid'] }}', 'down')" @disabled($index === count($phaseCards) - 1) title="Bajar" aria-label="Bajar fase" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 disabled:opacity-30 disabled:hover:bg-transparent">
                                    <x-icon name="arrow-down" class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="removePhase('{{ $card['uid'] }}')"
                                    wire:confirm="¿Quitar esta fase del proyecto?"
                                    title="Quitar"
                                    aria-label="Quitar fase"
                                    class="rounded-lg p-2 text-red-500 transition hover:bg-red-50"
                                >
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-[14rem_minmax(0,1fr)]">
                            <div class="aspect-video overflow-hidden rounded-xl border border-slate-200 bg-slate-100 md:aspect-[4/3]">
                                @if ($card['src'])
                                    <img src="{{ $card['src'] }}" alt="{{ $card['name'] }}" loading="lazy" referrerpolicy="no-referrer" class="size-full object-cover">
                                @else
                                    <div class="flex size-full flex-col items-center justify-center gap-1 text-slate-400">
                                        <x-icon name="alert" class="size-6" />
                                        <span class="px-2 text-center text-xs">{{ $card['name'] }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0 space-y-4">
                                <div>
                                    <label for="phase-title-{{ $card['uid'] }}" class="mb-1.5 block text-sm font-medium text-slate-700">Título de la fase</label>
                                    <input
                                        id="phase-title-{{ $card['uid'] }}"
                                        type="text"
                                        wire:model="phases.{{ $index }}.title"
                                        maxlength="200"
                                        placeholder="Ej. Fase 01: Concepción y modelado 3D"
                                        @class([
                                            'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                                            'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('phases.'.$index.'.title'),
                                            'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('phases.'.$index.'.title'),
                                        ])
                                    >
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Contenido de la fase</label>
                                    <x-rich-editor id="phase-editor-{{ $card['uid'] }}" :phase-uid="$card['uid']" :value="$card['content']" placeholder="Describe esta fase del proyecto..." />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <button
                    type="button"
                    wire:click="choosePhaseImages"
                    @disabled(count($phaseCards) >= $maxPhases)
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm font-medium text-slate-600 transition hover:border-indigo-400 hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <x-icon name="image" class="size-5" />
                    {{ count($phaseCards) ? 'Agregar más imágenes' : 'Agregar imágenes' }}
                </button>

                <p class="text-xs text-slate-500">{{ count($phaseCards) }} de {{ $maxPhases }} fases.</p>
            </div>
        </section>

        {{-- Video --}}
        <section class="mt-10">
            <x-form-section title="Video del proyecto (opcional)" />

            @error('videoMediaFileId')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror
            @error('videoYoutubeId')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            @if ($videoCard)
                <div class="flex flex-wrap items-center gap-5 rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="relative aspect-video w-56 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                        @if ($videoCard['kind'] === 'video' && $videoCard['src'])
                            <video src="{{ $videoCard['src'] }}#t=0.5" preload="metadata" muted playsinline class="size-full object-cover"></video>
                        @elseif ($videoCard['src'])
                            <img src="{{ $videoCard['src'] }}" alt="" loading="lazy" class="size-full object-cover">
                        @else
                            <div class="flex size-full items-center justify-center text-slate-400"><x-icon name="alert" class="size-6" /></div>
                        @endif
                        <span class="absolute top-2 left-2 flex items-center gap-1 rounded-full bg-slate-900/70 px-2 py-1 text-xs font-medium text-white"><x-icon name="play" class="size-3" /> {{ $videoCard['kind'] === 'youtube' ? 'YouTube' : 'Video' }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $videoCard['name'] }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="chooseVideo" class="rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-medium transition hover:bg-slate-50">Cambiar video</button>
                            <button type="button" wire:click="removeVideo" class="rounded-xl px-3.5 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">Quitar</button>
                        </div>
                    </div>
                </div>
            @else
                <button
                    type="button"
                    wire:click="chooseVideo"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm font-medium text-slate-600 transition hover:border-indigo-400 hover:text-indigo-600"
                >
                    <x-icon name="video" class="size-5" />
                    Elegir un video o pegar un enlace de YouTube
                </button>
            @endif
        </section>

        <div class="mt-10 flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
            <a href="{{ route('admin.projects') }}" wire:navigate class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancelar</a>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
            >
                {{ $projectId ? 'Guardar cambios' : 'Crear proyecto' }}
            </button>
        </div>
    </form>

    <livewire:admin.media-picker />
</div>
