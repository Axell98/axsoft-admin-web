<div class="mx-auto max-w-7xl">
    {{-- Cabecera --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h2 class="text-xl font-semibold tracking-tight uppercase">Portadas</h2>
            <p class="mt-1 text-sm text-slate-500">Imagen de cabecera de cada página interna de tu sitio web.</p>
        </div>

        @if ($pages !== [])
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
            >
                <svg wire:loading wire:target="save" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <x-icon wire:loading.remove wire:target="save" name="save" class="size-4" />
                <span wire:loading.remove wire:target="save">Guardar portada</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        @endif
    </div>

    @if ($pages === [])
        <div class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <x-icon name="presentation" class="size-7" />
            </span>
            <h4 class="mt-4 font-semibold">Aún no hay páginas internas configuradas</h4>
            <p class="mt-1 max-w-sm text-sm text-slate-500">Las páginas que llevan portada se definen en la configuración del panel.</p>
        </div>
    @else
        {{-- Aviso: tamaño y formatos de la imagen --}}
        <div class="mt-6 flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800" role="note">
            <x-icon name="image" class="mt-0.5 size-5 shrink-0 text-sky-600" />
            <p>
                <span class="font-semibold">Tamaño recomendado: {{ $size['width'] }}x{{ $size['height'] }} píxeles.</span>
                Formatos soportados: <span class="uppercase">{{ implode(', ', $formats) }}</span>.
            </p>
        </div>

        <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            {{-- Formulario --}}
            <form wire:submit="save" class="self-start rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                <div class="space-y-5">
                    <div>
                        <label for="page" class="mb-1.5 block text-sm font-medium text-slate-700">Página interna</label>
                        <select
                            id="page"
                            wire:model.live="page"
                            @class([
                                'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition focus:ring-4',
                                'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('page'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('page'),
                            ])
                        >
                            @foreach ($pages as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('page')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-xs text-slate-500">Al cambiar de página se carga su portada. Guarda antes los cambios que hayas hecho.</p>
                        @enderror
                    </div>

                    {{-- La llave reinicia los campos al cambiar de página. --}}
                    <div wire:key="cover-{{ $page }}" class="space-y-5">
                        <x-field
                            label="Título de la portada (opcional)"
                            name="title"
                            wire:model="title"
                            maxlength="150"
                            placeholder="Ej. Conoce nuestro estudio"
                            hint="Título que se muestra sobre la portada."
                        />

                        {{-- Imagen --}}
                        <div>
                            <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
                                <span class="block text-sm font-medium text-slate-700">Imagen</span>

                                @if ($imageType)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" wire:click="chooseImage" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium transition hover:bg-slate-50">
                                            <x-icon name="search" class="size-4" />
                                            Cambiar imagen
                                        </button>
                                        <button type="button" wire:click="removeImage" class="flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                                            <x-icon name="trash" class="size-4" />
                                            Quitar imagen
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @if ($imagePreview)
                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100" style="aspect-ratio: {{ (int) $size['width'] }} / {{ (int) $size['height'] }}">
                                    <img src="{{ $imagePreview }}" alt="Portada de {{ $pages[$page] ?? '' }}" referrerpolicy="no-referrer" class="size-full object-cover">
                                </div>
                                <p class="mt-1.5 text-xs text-slate-500">Vista previa con la proporción recomendada.</p>
                            @else
                                <button
                                    type="button"
                                    wire:click="chooseImage"
                                    @class([
                                        'flex w-full flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed bg-slate-50 px-6 py-10 text-slate-400 transition hover:border-indigo-400 hover:text-indigo-600',
                                        'border-slate-300' => ! $errors->has('imageType'),
                                        'border-red-300' => $errors->has('imageType'),
                                    ])
                                >
                                    <x-icon name="{{ $imageType ? 'alert' : 'image' }}" class="size-10" />
                                    <span class="text-center text-sm font-medium">
                                        {{ $imageType ? 'La imagen ya no está disponible. Elige otra.' : 'Buscar en archivos o pegar un enlace' }}
                                    </span>
                                </button>
                                @unless ($imageType)
                                    <p class="mt-1.5 text-xs text-slate-500">Sin imagen, la página queda sin portada y tu sitio web muestra la suya por defecto.</p>
                                @endunless
                            @endif

                            @error('imageType')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white px-4">
                            <x-toggle label="Activa (visible en la web)" wire:model="isActive" />
                        </div>
                    </div>
                </div>
            </form>

            <aside class="self-start">
                {{-- Estado de cada página --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3.5 text-sm font-semibold tracking-wide uppercase">
                        <x-icon name="presentation" class="size-4" />
                        Páginas
                    </div>

                    <ul class="divide-y divide-slate-100">
                        @foreach ($pages as $key => $label)
                            @php($cover = $covers->get($key))
                            @php($hasImage = $cover?->imageUrl() !== null)
                            <li wire:key="page-{{ $key }}">
                                <button
                                    type="button"
                                    wire:click="$set('page', @js((string) $key))"
                                    @class([
                                        'flex w-full items-center justify-between gap-3 px-5 py-3 text-left text-sm transition',
                                        'bg-indigo-50 font-semibold text-indigo-700' => (string) $key === $page,
                                        'text-slate-700 hover:bg-slate-50' => (string) $key !== $page,
                                    ])
                                    @if ((string) $key === $page) aria-current="true" @endif
                                >
                                    <span class="truncate">{{ $label }}</span>
                                    <span @class([
                                        'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        'bg-emerald-100 text-emerald-700' => $hasImage && $cover->is_active,
                                        'bg-slate-200 text-slate-600' => $hasImage && ! $cover->is_active,
                                        'bg-amber-100 text-amber-700' => ! $hasImage,
                                    ])>{{ ! $hasImage ? 'Sin portada' : ($cover->is_active ? 'Activa' : 'Oculta') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    @endif

    <livewire:admin.media-picker />
</div>
