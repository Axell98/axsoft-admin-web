<div class="mx-auto max-w-7xl">
    {{-- Cabecera --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h2 class="text-xl font-semibold tracking-tight uppercase">Pop-up</h2>
            <p class="mt-1 text-sm text-slate-500">Ventana emergente que se muestra al entrar a tu sitio web.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                wire:click="choose"
                class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold transition hover:bg-slate-50 focus:ring-4 focus:ring-indigo-500/20 focus:outline-none"
            >
                <x-icon name="search" class="size-4" />
                Buscar en archivos
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
                <span wire:loading.remove wire:target="save">Guardar pop-up</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[19rem_minmax(0,1fr)]">
        {{-- Opciones --}}
        <aside class="self-start overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3.5 text-sm font-semibold tracking-wide uppercase">
                <x-icon name="sliders" class="size-4" />
                Opciones
            </div>

            <div class="divide-y divide-slate-200 px-5">
                <div class="py-4">
                    <x-field label="Título" name="title" wire:model.live.debounce.400ms="title" maxlength="120" placeholder="Ej. Promoción de aniversario" />
                </div>

                <x-toggle label="Ventana visible" wire:model.live="isVisible" />
                <x-toggle label="Mostrar encabezado" wire:model.live="showHeader" />
                <x-toggle label="Mostrar margen" wire:model.live="showBorder" />

                <fieldset class="py-4">
                    <legend class="mb-2 text-sm font-medium">Tipo de contenido</legend>

                    <div class="grid grid-cols-3 gap-1 rounded-lg border border-slate-300 bg-white p-1 text-sm" role="radiogroup">
                        @foreach ([['image', 'Imagen'], ['slider', 'Slider'], ['video', 'Video']] as [$value, $label])
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="displayType" value="{{ $value }}" class="peer sr-only">
                                <span class="block rounded-md px-2 py-1.5 text-center font-medium text-slate-600 transition peer-checked:bg-indigo-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500/50 hover:bg-slate-100 peer-checked:hover:bg-indigo-600">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <p class="mt-2 text-xs text-slate-500">
                        @if ($displayType === 'slider')
                            Varias imágenes que se pueden pasar con flechas (hasta {{ $maxItems }}).
                        @elseif ($displayType === 'video')
                            Un video de tus archivos o de YouTube.
                        @else
                            Una sola imagen de tus archivos o por link.
                        @endif
                    </p>
                </fieldset>

                <div class="py-4">
                    <x-field label="Enlace al hacer clic (opcional)" name="linkUrl" wire:model.live.debounce.400ms="linkUrl" maxlength="2048" placeholder="https://... o /contacto" />
                </div>
            </div>
        </aside>

        {{-- Vista previa --}}
        <section class="min-w-0">
            @error('items')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror
            @error('items.*')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 class="flex items-center gap-2 text-sm font-semibold tracking-wide uppercase">
                    Vista previa
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-medium normal-case',
                        'bg-emerald-100 text-emerald-700' => $isVisible,
                        'bg-slate-200 text-slate-600' => ! $isVisible,
                    ])>{{ $isVisible ? 'Visible en el sitio' : 'Oculto (borrador)' }}</span>
                </h3>

                @if (count($cards) > 0)
                    <button
                        type="button"
                        wire:click="clearItems"
                        wire:confirm="¿Quitar todo el contenido del pop-up? Se aplica al guardar."
                        title="Quitar contenido"
                        aria-label="Quitar contenido"
                        class="flex items-center gap-1.5 rounded-lg border border-sky-200 bg-white px-3 py-1.5 text-sm font-medium text-sky-600 transition hover:bg-sky-50"
                    >
                        <x-icon name="eraser" class="size-4" />
                        Quitar
                    </button>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-200/70 px-4 py-8 sm:px-10 sm:py-12">
                <div class="mx-auto w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-900/10">
                    @if ($showHeader)
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                            <p class="truncate text-sm font-semibold">{{ $title !== '' ? $title : 'Título del pop-up' }}</p>
                            <x-icon name="close" class="size-5 shrink-0 text-slate-400" />
                        </div>
                    @endif

                    <div @class(['p-3' => $showBorder])>
                        @if (count($cards) === 0)
                            <button type="button" wire:click="choose" class="flex aspect-[4/3] w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 text-slate-400 transition hover:border-indigo-400 hover:text-indigo-600">
                                <x-icon name="{{ $displayType === 'video' ? 'video' : 'image' }}" class="size-10" />
                                <span class="px-6 text-center text-sm font-medium">
                                    {{ $displayType === 'video' ? 'Elegir un video' : ($displayType === 'slider' ? 'Elegir las imágenes' : 'Elegir una imagen') }}
                                </span>
                            </button>
                        @elseif ($displayType === 'slider' && count($cards) > 1)
                            {{-- La llave reinicia la vista previa cuando cambia la cantidad de imágenes. --}}
                            <div wire:key="preview-slider-{{ count($cards) }}" x-data="{ i: 0, n: {{ count($cards) }} }" @class(['relative overflow-hidden', 'rounded-lg' => $showBorder])>
                                @foreach ($cards as $index => $card)
                                    <div x-show="i === {{ $index }}" @if ($index > 0) x-cloak @endif>
                                        @if ($card['src'])
                                            <img src="{{ $card['src'] }}" alt="{{ $card['name'] }}" referrerpolicy="no-referrer" class="block h-auto w-full">
                                        @else
                                            <div class="flex aspect-[4/3] items-center justify-center bg-slate-100 text-xs text-slate-400">{{ $card['name'] }}</div>
                                        @endif
                                    </div>
                                @endforeach

                                <button type="button" x-on:click="i = (i - 1 + n) % n" aria-label="Anterior" class="absolute top-1/2 left-2 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-slate-900/60 text-white transition hover:bg-slate-900/80">
                                    <x-icon name="arrow-left" class="size-4" />
                                </button>
                                <button type="button" x-on:click="i = (i + 1) % n" aria-label="Siguiente" class="absolute top-1/2 right-2 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-slate-900/60 text-white transition hover:bg-slate-900/80">
                                    <x-icon name="arrow-right" class="size-4" />
                                </button>

                                <div class="absolute inset-x-0 bottom-2 flex justify-center gap-1.5">
                                    @foreach ($cards as $index => $card)
                                        <button type="button" x-on:click="i = {{ $index }}" aria-label="Ir a la imagen {{ $index + 1 }}" class="size-2 rounded-full transition" x-bind:class="i === {{ $index }} ? 'bg-white' : 'bg-white/50'"></button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @php($card = $cards[0])
                            <div @class(['overflow-hidden', 'rounded-lg' => $showBorder])>
                                @if ($card['kind'] === 'video')
                                    <video src="{{ $card['src'] }}" controls preload="metadata" playsinline class="block h-auto w-full bg-black"></video>
                                @elseif ($card['kind'] === 'youtube')
                                    <div class="relative aspect-video bg-black">
                                        <img src="{{ $card['src'] }}" alt="{{ $card['name'] }}" referrerpolicy="no-referrer" class="size-full object-cover">
                                        <span class="absolute inset-0 flex items-center justify-center">
                                            <span class="flex size-14 items-center justify-center rounded-full bg-red-600 text-white shadow-lg"><x-icon name="play" class="size-6" /></span>
                                        </span>
                                    </div>
                                @elseif ($card['src'])
                                    <img src="{{ $card['src'] }}" alt="{{ $card['name'] }}" referrerpolicy="no-referrer" class="block h-auto w-full">
                                @else
                                    <div class="flex aspect-[4/3] flex-col items-center justify-center gap-1 bg-slate-100 text-slate-400">
                                        <x-icon name="alert" class="size-6" />
                                        <span class="text-xs">{{ $card['name'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($linkUrl !== '')
                <p class="mt-3 flex items-center gap-1.5 text-xs text-slate-500">
                    <x-icon name="link" class="size-3.5" />
                    Al hacer clic abrirá: <span class="truncate font-medium">{{ $linkUrl }}</span>
                </p>
            @endif

            {{-- Orden y quitar imágenes del slider --}}
            @if ($displayType === 'slider' && count($cards) > 0)
                <div class="mt-6">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                        @foreach ($cards as $index => $card)
                            <div wire:key="item-{{ $card['uid'] }}" class="group relative aspect-video overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                @if ($card['src'])
                                    <img src="{{ $card['src'] }}" alt="{{ $card['name'] }}" loading="lazy" referrerpolicy="no-referrer" class="size-full object-cover">
                                @else
                                    <div class="flex size-full items-center justify-center text-slate-400"><x-icon name="alert" class="size-5" /></div>
                                @endif

                                <span class="absolute top-1.5 left-1.5 flex size-5 items-center justify-center rounded-full bg-slate-900/70 text-[11px] font-semibold text-white">{{ $index + 1 }}</span>

                                <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-linear-to-t from-slate-950/80 to-transparent px-1.5 pt-6 pb-1.5 text-white">
                                    <div class="flex items-center">
                                        <button type="button" wire:click="moveItem('{{ $card['uid'] }}', 'left')" @disabled($index === 0) title="Mover antes" aria-label="Mover antes" class="rounded-md p-1 transition hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent">
                                            <x-icon name="arrow-left" class="size-3.5" />
                                        </button>
                                        <button type="button" wire:click="moveItem('{{ $card['uid'] }}', 'right')" @disabled($index === count($cards) - 1) title="Mover después" aria-label="Mover después" class="rounded-md p-1 transition hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent">
                                            <x-icon name="arrow-right" class="size-3.5" />
                                        </button>
                                    </div>
                                    <button type="button" wire:click="removeItem('{{ $card['uid'] }}')" title="Quitar" aria-label="Quitar imagen {{ $index + 1 }}" class="rounded-md p-1 transition hover:bg-white/20">
                                        <x-icon name="trash" class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-3 text-xs text-slate-500">{{ count($cards) }} de {{ $maxItems }} imágenes. El orden de esta lista es el orden del slider.</p>
                </div>
            @endif
        </section>
    </div>

    <livewire:admin.media-picker />
</div>
