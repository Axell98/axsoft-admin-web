<div>
    @if ($open)
        @php
            $title = match ($mode) {
                'video' => 'Seleccionar video',
                'all' => 'Seleccionar imagen o video',
                default => 'Seleccionar imagen',
            };
            $kindLabels = ['image' => 'Imagen por link', 'youtube' => 'Video de YouTube'];
        @endphp

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ $title }}" x-data x-on:keydown.escape.window="$wire.close()">
            <div wire:click="close" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

            <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-semibold tracking-tight">{{ $title }}</h3>
                    <button type="button" wire:click="close" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100" aria-label="Cerrar">
                        <x-icon name="close" />
                    </button>
                </div>

                {{-- Filtros --}}
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-6 py-3">
                    <div class="relative min-w-48 flex-1">
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Buscar por nombre"
                            class="w-full rounded-lg border border-slate-300 bg-white py-2 pr-3.5 pl-10 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15"
                        >
                    </div>

                    @if ($mode === 'all')
                        <select wire:model.live="type" aria-label="Tipo" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
                            <option value="all">Imágenes y videos</option>
                            <option value="image">Solo imágenes</option>
                            <option value="video">Solo videos</option>
                        </select>
                    @endif

                    <select wire:model.live="folder" aria-label="Carpeta" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
                        <option value="">Todas las carpetas</option>
                        @foreach ($folders as $folderItem)
                            <option value="{{ $folderItem->id }}" wire:key="mp-folder-{{ $folderItem->id }}">{{ $folderItem->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Archivos --}}
                <div class="overflow-y-auto p-6">
                    @if ($files->isEmpty())
                        <div class="flex flex-col items-center py-12 text-center">
                            <x-icon name="image" class="size-10 text-slate-300" />
                            <p class="mt-3 text-sm font-medium">No hay archivos para mostrar</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Cárgalos en <a href="{{ route('admin.files') }}" wire:navigate class="text-indigo-600 hover:underline">Archivos</a>{{ count($external) ? ' o inserta uno por link.' : '.' }}
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($files as $file)
                                @php
                                    $isSelected = in_array($file->id, $selected, true);
                                    $isUsed = in_array($file->id, $used, true);
                                @endphp
                                <button
                                    type="button"
                                    wire:key="mp-file-{{ $file->id }}"
                                    wire:click="toggle({{ $file->id }})"
                                    title="{{ $file->name }}"
                                    @class([
                                        'group relative aspect-video overflow-hidden rounded-xl border bg-slate-100 text-left transition focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/30',
                                        'border-indigo-500 ring-4 ring-indigo-500/25' => $isSelected,
                                        'border-slate-200 hover:border-indigo-400' => ! $isSelected,
                                    ])
                                >
                                    @if ($file->category() === 'video')
                                        <video src="{{ $file->url() }}#t=0.5" preload="metadata" muted playsinline class="size-full object-cover"></video>
                                        <span class="absolute top-2 left-2 flex items-center gap-1 rounded-full bg-slate-900/70 px-2 py-1 text-xs font-medium text-white"><x-icon name="play" class="size-3" /> Video</span>
                                    @else
                                        <img src="{{ $file->url() }}" alt="{{ $file->name }}" loading="lazy" class="size-full object-cover">
                                    @endif

                                    <span class="absolute inset-x-0 bottom-0 truncate bg-linear-to-t from-slate-950/80 to-transparent px-2.5 pt-6 pb-1.5 text-xs text-white">{{ $file->name }}</span>

                                    @if ($isSelected)
                                        <span class="absolute top-2 right-2 flex size-6 items-center justify-center rounded-full bg-indigo-600 text-white shadow"><x-icon name="check" class="size-4" /></span>
                                    @elseif ($isUsed)
                                        <span class="absolute top-2 right-2 rounded-full bg-emerald-600 px-2 py-0.5 text-xs font-medium text-white shadow">En uso</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        @if ($files->count() < $total)
                            <div class="mt-5 text-center">
                                <button type="button" wire:click="loadMore" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium transition hover:bg-slate-50">Cargar más</button>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Insertar vía link --}}
                @if ($showExternal && count($external))
                    <form wire:submit="addExternal" class="space-y-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                        @if (count($external) > 1)
                            <div class="inline-flex rounded-xl border border-slate-300 bg-white p-1 text-sm" role="radiogroup">
                                @foreach ($external as $kind)
                                    <label wire:key="mp-kind-{{ $kind }}" @class(['cursor-pointer rounded-lg px-3 py-1.5 font-medium transition', 'bg-indigo-600 text-white' => $externalKind === $kind, 'text-slate-600' => $externalKind !== $kind])>
                                        <input type="radio" wire:model.live="externalKind" value="{{ $kind }}" class="sr-only"> {{ $kindLabels[$kind] }}
                                    </label>
                                @endforeach
                            </div>
                        @endif

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
                    @if (count($external))
                        <button type="button" wire:click="$toggle('showExternal')" class="flex items-center gap-2 text-sm font-medium text-indigo-600 transition hover:underline">
                            <x-icon name="link" class="size-4" />
                            {{ in_array('youtube', $external, true) && ! in_array('image', $external, true) ? 'Insertar video de YouTube' : (in_array('youtube', $external, true) ? 'Insertar imagen o video vía link' : 'Insertar imagen vía link') }}
                        </button>
                    @else
                        <span></span>
                    @endif

                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-500">Resultados {{ $files->count() }} de {{ $total }}</span>
                        <button
                            type="button"
                            wire:click="confirm"
                            @disabled(count($selected) === 0)
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ $multiple ? 'Agregar'.(count($selected) > 0 ? ' ('.count($selected).')' : '') : 'Seleccionar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
