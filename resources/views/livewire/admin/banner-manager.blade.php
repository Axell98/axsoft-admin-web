<div class="mx-auto max-w-7xl">
    {{-- Cabecera --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h2 class="text-xl font-semibold tracking-tight uppercase">Banner</h2>
            <p class="mt-1 text-sm text-slate-500">Imágenes y videos que se muestran en la portada de tu sitio web.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if ($dirty)
                <span class="flex items-center gap-2 text-sm text-amber-600">
                    <span class="size-2 rounded-full bg-amber-500"></span>
                    Cambios sin guardar
                </span>
            @endif

            <button
                type="button"
                wire:click="openPicker"
                class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold transition hover:bg-slate-50 focus:ring-4 focus:ring-indigo-500/20 focus:outline-none"
            >
                <x-icon name="search" class="size-4" />
                Buscar imágenes
            </button>

            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
            >
                <svg wire:loading wire:target="save" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <x-icon wire:loading.remove wire:target="save" name="save" class="size-4" />
                <span wire:loading.remove wire:target="save">Guardar cambios</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[18rem_minmax(0,1fr)]">
        {{-- Opciones --}}
        <aside class="self-start overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3.5 text-sm font-semibold tracking-wide uppercase">
                <x-icon name="sliders" class="size-4" />
                Opciones
            </div>

            <div class="divide-y divide-slate-200 px-5">
                <div class="py-4">
                    <label for="screenPercentage" class="mb-1.5 block text-sm font-medium">Alto en pantalla (%)</label>
                    <div class="flex items-center gap-3">
                        <input
                            id="screenPercentage"
                            type="number"
                            min="10"
                            max="100"
                            wire:model.live.debounce.400ms="screenPercentage"
                            @class([
                                'w-24 rounded-lg border bg-white px-3.5 py-2 text-sm shadow-xs outline-none transition focus:ring-4',
                                'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('screenPercentage'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('screenPercentage'),
                            ])
                        >
                        <span class="text-sm text-slate-500">de 10 a 100</span>
                    </div>
                    @error('screenPercentage')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    {{-- Vista previa de la proporción --}}
                    <div class="mt-4 rounded-lg border-2 border-slate-300 bg-slate-100 p-1" aria-hidden="true">
                        <div class="relative aspect-[16/10] overflow-hidden rounded bg-white">
                            <div class="absolute inset-x-0 top-0 flex items-center justify-center bg-indigo-500/80 text-xs font-semibold text-white transition-all" style="height: {{ $percentage }}%">
                                {{ $percentage }}%
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Porcentaje de la altura de la pantalla que ocupa el banner.</p>
                </div>

                <div>
                    <x-toggle label="Mostrar indicadores" wire:model.live="showIndicators" />
                    <p class="-mt-1 pb-3.5 text-xs text-slate-500">
                        @if ($showIndicators)
                            Puedes agregar un texto y una descripción a cada banner con el botón «Texto».
                        @else
                            Actívalos para poder agregar texto y descripción a cada banner. Los textos ya escritos se conservan.
                        @endif
                    </p>
                </div>
                <x-toggle label="Mostrar flechas" wire:model.live="showArrows" />
            </div>
        </aside>

        {{-- Banners --}}
        <section class="min-w-0">
            @error('slides')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror
            @error('slides.*')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            @if (count($cards) === 0)
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <x-icon name="image" class="size-7" />
                    </span>
                    <h4 class="mt-4 font-semibold">Aún no hay banners</h4>
                    <p class="mt-1 max-w-sm text-sm text-slate-500">Elige imágenes o videos de tus archivos, o inserta una imagen por link o un video de YouTube.</p>
                    <button type="button" wire:click="openPicker" class="mt-5 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        Buscar imágenes
                    </button>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($cards as $index => $card)
                        <div wire:key="slide-{{ $card['uid'] }}" class="group relative aspect-video overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                            @if ($card['kind'] === 'video')
                                <video src="{{ $card['src'] }}#t=0.5" preload="metadata" muted playsinline class="size-full object-cover"></video>
                            @elseif ($card['src'])
                                <img src="{{ $card['src'] }}" alt="{{ $card['name'] }}" loading="lazy" referrerpolicy="no-referrer" class="size-full object-cover">
                            @else
                                <div class="flex size-full flex-col items-center justify-center gap-1 text-slate-400">
                                    <x-icon name="alert" class="size-6" />
                                    <span class="text-xs">{{ $card['name'] }}</span>
                                </div>
                            @endif

                            {{-- Etiquetas --}}
                            <div class="absolute top-2 left-2 flex items-center gap-1.5">
                                <span class="flex size-6 items-center justify-center rounded-full bg-slate-900/70 text-xs font-semibold text-white">{{ $index + 1 }}</span>
                                @if ($card['kind'] === 'video' || $card['kind'] === 'youtube')
                                    <span class="flex items-center gap-1 rounded-full bg-slate-900/70 px-2 py-1 text-xs font-medium text-white">
                                        <x-icon name="play" class="size-3" />
                                        {{ $card['kind'] === 'youtube' ? 'YouTube' : 'Video' }}
                                    </span>
                                @endif
                            </div>

                            {{-- Orden --}}
                            <div class="absolute top-2 right-2 flex items-center rounded-full bg-slate-900/70 text-white">
                                <button type="button" wire:click="moveSlide('{{ $card['uid'] }}', 'left')" @disabled($index === 0) title="Mover antes" aria-label="Mover antes" class="rounded-full p-1.5 transition hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent">
                                    <x-icon name="arrow-left" class="size-4" />
                                </button>
                                <button type="button" wire:click="moveSlide('{{ $card['uid'] }}', 'right')" @disabled($index === count($cards) - 1) title="Mover después" aria-label="Mover después" class="rounded-full p-1.5 transition hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent">
                                    <x-icon name="arrow-right" class="size-4" />
                                </button>
                            </div>

                            {{-- Texto y descripción (solo con indicadores activos) --}}
                            @if ($showIndicators && ($card['title'] || $card['description']))
                                <div class="pointer-events-none absolute inset-x-0 bottom-11 px-3 text-white">
                                    @if ($card['title'])
                                        <p class="truncate text-sm font-semibold drop-shadow">{{ $card['title'] }}</p>
                                    @endif
                                    @if ($card['description'])
                                        <p class="line-clamp-2 text-xs text-white/85 drop-shadow">{{ $card['description'] }}</p>
                                    @endif
                                </div>
                            @endif

                            {{-- Acciones --}}
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-end gap-0.5 bg-linear-to-t from-slate-950/85 to-transparent px-2 pt-8 pb-2 text-sm text-white">
                                @if ($showIndicators)
                                    <button type="button" wire:click="openTextModal('{{ $card['uid'] }}')" title="Texto y descripción" class="flex items-center gap-1.5 rounded-md px-2 py-1.5 font-medium transition hover:bg-white/15">
                                        <x-icon name="text" class="size-4" />
                                        Texto
                                        @if ($card['title'] || $card['description'])
                                            <span class="size-1.5 rounded-full bg-emerald-400"></span>
                                        @endif
                                    </button>
                                @endif
                                <button type="button" wire:click="openLinkModal('{{ $card['uid'] }}')" title="{{ $card['link'] ?: 'Agregar enlace' }}" class="flex items-center gap-1.5 rounded-md px-2 py-1.5 font-medium transition hover:bg-white/15">
                                    <x-icon name="link" class="size-4" />
                                    Enlace
                                    @if ($card['link'])
                                        <span class="size-1.5 rounded-full bg-emerald-400"></span>
                                    @endif
                                </button>
                                <button type="button" wire:click="removeSlide('{{ $card['uid'] }}')" class="flex items-center gap-1.5 rounded-md px-2 py-1.5 font-medium transition hover:bg-white/15">
                                    <x-icon name="trash" class="size-4" />
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="mt-4 text-xs text-slate-500">{{ count($cards) }} de {{ $maxSlides }} banners. El orden de esta lista es el orden en que se mostrarán.</p>
            @endif
        </section>
    </div>

    {{-- Modal: seleccionar imagen o video --}}
    @if ($showPicker)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Seleccionar imagen" x-data x-on:keydown.escape.window="$wire.closePicker()">
            <div wire:click="closePicker" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

            <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-semibold tracking-tight">Seleccionar imagen o video</h3>
                    <button type="button" wire:click="closePicker" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100" aria-label="Cerrar">
                        <x-icon name="close" />
                    </button>
                </div>

                {{-- Filtros --}}
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-6 py-3">
                    <div class="relative min-w-48 flex-1">
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="pickerSearch"
                            placeholder="Buscar por nombre"
                            class="w-full rounded-lg border border-slate-300 bg-white py-2 pr-3.5 pl-10 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15"
                        >
                    </div>

                    <select wire:model.live="pickerType" aria-label="Tipo" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
                        <option value="all">Imágenes y videos</option>
                        <option value="image">Solo imágenes</option>
                        <option value="video">Solo videos</option>
                    </select>

                    <select wire:model.live="pickerFolder" aria-label="Carpeta" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
                        <option value="">Todas las carpetas</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" wire:key="picker-folder-{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Archivos --}}
                <div class="overflow-y-auto p-6">
                    @if ($pickerFiles->isEmpty())
                        <div class="flex flex-col items-center py-12 text-center">
                            <x-icon name="image" class="size-10 text-slate-300" />
                            <p class="mt-3 text-sm font-medium">No hay imágenes ni videos para mostrar</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Cárgalos en <a href="{{ route('admin.files') }}" wire:navigate class="text-indigo-600 hover:underline">Archivos</a> o inserta uno por link.
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($pickerFiles as $file)
                                @php
                                    $selected = in_array($file->id, $pickerSelected, true);
                                    $added = in_array($file->id, $addedFileIds, true);
                                @endphp
                                <button
                                    type="button"
                                    wire:key="pick-{{ $file->id }}"
                                    wire:click="togglePick({{ $file->id }})"
                                    title="{{ $file->name }}"
                                    @class([
                                        'group relative aspect-video overflow-hidden rounded-xl border bg-slate-100 text-left transition focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/30',
                                        'border-indigo-500 ring-4 ring-indigo-500/25' => $selected,
                                        'border-slate-200 hover:border-indigo-400' => ! $selected,
                                    ])
                                >
                                    @if ($file->category() === 'video')
                                        <video src="{{ $file->url() }}#t=0.5" preload="metadata" muted playsinline class="size-full object-cover"></video>
                                        <span class="absolute top-2 left-2 flex items-center gap-1 rounded-full bg-slate-900/70 px-2 py-1 text-xs font-medium text-white"><x-icon name="play" class="size-3" /> Video</span>
                                    @else
                                        <img src="{{ $file->url() }}" alt="{{ $file->name }}" loading="lazy" class="size-full object-cover">
                                    @endif

                                    <span class="absolute inset-x-0 bottom-0 truncate bg-linear-to-t from-slate-950/80 to-transparent px-2.5 pt-6 pb-1.5 text-xs text-white">{{ $file->name }}</span>

                                    @if ($selected)
                                        <span class="absolute top-2 right-2 flex size-6 items-center justify-center rounded-full bg-indigo-600 text-white shadow"><x-icon name="check" class="size-4" /></span>
                                    @elseif ($added)
                                        <span class="absolute top-2 right-2 rounded-full bg-emerald-600 px-2 py-0.5 text-xs font-medium text-white shadow">En uso</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        @if ($pickerFiles->count() < $pickerTotal)
                            <div class="mt-5 text-center">
                                <button type="button" wire:click="loadMore" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium transition hover:bg-slate-50">Cargar más</button>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Insertar vía link --}}
                @if ($showExternalForm)
                    <form wire:submit="addExternal" class="space-y-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                        <div class="inline-flex rounded-xl border border-slate-300 bg-white p-1 text-sm" role="radiogroup">
                            <label @class(['cursor-pointer rounded-lg px-3 py-1.5 font-medium transition', 'bg-indigo-600 text-white' => $externalKind === 'image', 'text-slate-600' => $externalKind !== 'image'])>
                                <input type="radio" wire:model.live="externalKind" value="image" class="sr-only"> Imagen por link
                            </label>
                            <label @class(['cursor-pointer rounded-lg px-3 py-1.5 font-medium transition', 'bg-indigo-600 text-white' => $externalKind === 'youtube', 'text-slate-600' => $externalKind !== 'youtube'])>
                                <input type="radio" wire:model.live="externalKind" value="youtube" class="sr-only"> Video de YouTube
                            </label>
                        </div>

                        <div class="flex flex-wrap items-start gap-3">
                            <div class="min-w-64 flex-1">
                                <input
                                    type="text"
                                    wire:model="externalInput"
                                    aria-label="Enlace"
                                    placeholder="{{ $externalKind === 'youtube' ? 'https://www.youtube.com/watch?v=... o código <iframe>' : 'https://sitio.com/imagen.jpg' }}"
                                    @class([
                                        'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                                        'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('externalInput'),
                                        'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('externalInput'),
                                    ])
                                >
                                @error('externalInput')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @if ($externalKind === 'youtube' && ! $errors->has('externalInput'))
                                    <p class="mt-1.5 text-xs text-slate-500">Pega la URL del video o el código «Insertar» (iframe) de YouTube.</p>
                                @endif
                            </div>
                            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">Agregar</button>
                        </div>
                    </form>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" wire:click="$toggle('showExternalForm')" class="flex items-center gap-2 text-sm font-medium text-indigo-600 transition hover:underline">
                        <x-icon name="link" class="size-4" />
                        Insertar imagen o video vía link
                    </button>

                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-500">Resultados {{ $pickerFiles->count() }} de {{ $pickerTotal }}</span>
                        <button
                            type="button"
                            wire:click="addPicked"
                            @disabled(count($pickerSelected) === 0)
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Agregar{{ count($pickerSelected) > 0 ? ' ('.count($pickerSelected).')' : '' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: texto y descripción del banner --}}
    @if ($editingTextUid && $editingTextCard && $showIndicators)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" x-data x-on:keydown.escape.window="$wire.closeTextModal()">
            <div wire:click="closeTextModal" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

            <form wire:submit="saveText" class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold tracking-tight">Texto del banner</h3>
                <p class="mt-1 text-sm text-slate-500">Se mostrará sobre este banner en tu sitio web. Ambos campos son opcionales.</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label for="text-title" class="mb-1.5 block text-sm font-medium text-slate-700">Texto</label>
                        <input
                            id="text-title"
                            type="text"
                            wire:model="textTitle"
                            maxlength="120"
                            autofocus
                            placeholder="Ej. Diseñamos espacios que inspiran"
                            @class([
                                'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                                'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('textTitle'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('textTitle'),
                            ])
                        >
                        @error('textTitle')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="text-description" class="mb-1.5 block text-sm font-medium text-slate-700">Descripción</label>
                        <textarea
                            id="text-description"
                            rows="3"
                            wire:model="textDescription"
                            maxlength="500"
                            placeholder="Una frase corta que acompañe al texto"
                            @class([
                                'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                                'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('textDescription'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('textDescription'),
                            ])
                        ></textarea>
                        @error('textDescription')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-slate-500">Máximo 120 caracteres para el texto y 500 para la descripción.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeTextModal" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">Aceptar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal: enlace del banner --}}
    @if ($editingLinkUid && $editingCard)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" x-data x-on:keydown.escape.window="$wire.closeLinkModal()">
            <div wire:click="closeLinkModal" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

            <form wire:submit="saveLink" class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold tracking-tight">Enlace del banner</h3>
                <p class="mt-1 text-sm text-slate-500">Al hacer clic en el banner, el visitante irá a esta dirección. Déjalo vacío si no debe tener enlace.</p>

                <div class="mt-4">
                    <label for="link-url" class="mb-1.5 block text-sm font-medium text-slate-700">URL de destino</label>
                    <input
                        id="link-url"
                        type="text"
                        wire:model="linkUrl"
                        autofocus
                        placeholder="https://tuempresa.com/proyectos"
                        @class([
                            'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                            'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('linkUrl'),
                            'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('linkUrl'),
                        ])
                    >
                    @error('linkUrl')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeLinkModal" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">Aceptar</button>
                </div>
            </form>
        </div>
    @endif
</div>
