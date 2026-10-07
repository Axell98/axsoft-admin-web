<div class="mx-auto max-w-7xl">
    {{-- Cabecera --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h2 class="text-xl font-semibold tracking-tight uppercase">Testimonios</h2>
            <p class="mt-1 text-sm text-slate-500">Lo que dicen tus clientes, para mostrar en tu sitio web.</p>
        </div>

        <button
            type="button"
            wire:click="create"
            class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none"
        >
            <x-icon name="plus" class="size-5" />
            Nuevo testimonio
        </button>
    </div>

    @if ($testimonials->isEmpty())
        <div class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <x-icon name="chat" class="size-7" />
            </span>
            <h4 class="mt-4 font-semibold">Aún no hay testimonios</h4>
            <p class="mt-1 max-w-sm text-sm text-slate-500">Registra el primero con la foto, el nombre, el cargo y lo que dijo tu cliente.</p>
            <button type="button" wire:click="create" class="mt-5 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">Nuevo testimonio</button>
        </div>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($testimonials as $testimonial)
                @php($photo = $testimonial->imageUrl())
                <article wire:key="testimonial-{{ $testimonial->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <div @class(['flex flex-1 flex-col gap-4 p-5', 'opacity-60' => ! $testimonial->is_published])>
                        <div class="flex items-center gap-3">
                            @if ($photo)
                                <img src="{{ $photo }}" alt="{{ $testimonial->name }}" loading="lazy" referrerpolicy="no-referrer" class="size-14 shrink-0 rounded-full object-cover ring-2 ring-slate-100">
                            @else
                                <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-lg font-semibold text-indigo-600 uppercase">{{ mb_substr($testimonial->name, 0, 1) }}</span>
                            @endif

                            <div class="min-w-0">
                                <h3 class="truncate font-semibold tracking-tight">{{ $testimonial->name }}</h3>
                                <p class="truncate text-sm text-slate-500">{{ $testimonial->role ?: 'Sin cargo' }}</p>
                            </div>

                            @unless ($testimonial->is_published)
                                <span class="ml-auto shrink-0 rounded-full bg-slate-900/80 px-2.5 py-1 text-xs font-medium text-white">Oculto</span>
                            @endunless
                        </div>

                        <p class="line-clamp-5 text-sm leading-relaxed whitespace-pre-line text-slate-600">{{ $testimonial->content }}</p>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="edit({{ $testimonial->id }})"
                                title="Editar"
                                aria-label="Editar el testimonio de {{ $testimonial->name }}"
                                class="rounded-lg border border-emerald-200 p-2 text-emerald-600 transition hover:bg-emerald-50"
                            >
                                <x-icon name="pencil" class="size-4" />
                            </button>
                            <button
                                type="button"
                                wire:click="delete({{ $testimonial->id }})"
                                wire:confirm="¿Eliminar el testimonio de «{{ $testimonial->name }}»? Esta acción no se puede deshacer."
                                title="Eliminar"
                                aria-label="Eliminar el testimonio de {{ $testimonial->name }}"
                                class="rounded-lg border border-red-200 p-2 text-red-600 transition hover:bg-red-50"
                            >
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 select-none">
                            Visible
                            <input
                                type="checkbox"
                                @checked($testimonial->is_published)
                                wire:click="toggleVisibility({{ $testimonial->id }})"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >
                        </label>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($testimonials->hasPages())
            <div class="mt-8">
                {{ $testimonials->links() }}
            </div>
        @endif
    @endif

    {{-- Modal: registrar o editar --}}
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ $editingId ? 'Editar testimonio' : 'Nuevo testimonio' }}" x-data x-on:keydown.escape.window="$wire.closeForm()">
            <div wire:click="closeForm" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

            <form wire:submit="save" class="relative flex max-h-[92vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-semibold tracking-tight">{{ $editingId ? 'Editar testimonio' : 'Nuevo testimonio' }}</h3>
                    <button type="button" wire:click="closeForm" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100" aria-label="Cerrar">
                        <x-icon name="close" />
                    </button>
                </div>

                <div class="space-y-5 overflow-y-auto p-6">
                    {{-- Imagen --}}
                    <div>
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">Imagen <span class="font-normal text-slate-400">(opcional)</span></span>

                        <div class="flex items-center gap-4">
                            @if ($imagePreview)
                                <img src="{{ $imagePreview }}" alt="Imagen del testimonio" referrerpolicy="no-referrer" class="size-20 shrink-0 rounded-full object-cover ring-2 ring-slate-100">
                            @else
                                <span class="flex size-20 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-300">
                                    <x-icon name="image" class="size-8" />
                                </span>
                            @endif

                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="chooseImage" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium transition hover:bg-slate-50">
                                    <x-icon name="search" class="size-4" />
                                    {{ $imagePreview ? 'Cambiar' : 'Buscar en archivos' }}
                                </button>
                                @if ($imageType)
                                    <button type="button" wire:click="removeImage" class="rounded-lg px-3.5 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">Quitar</button>
                                @endif
                            </div>
                        </div>

                        @error('imageType')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Nombre" name="name" wire:model="name" maxlength="120" placeholder="Ej. María Fernández" autofocus />
                        <x-field label="Cargo (opcional)" name="role" wire:model="role" maxlength="120" placeholder="Ej. Gerente de Inmobiliaria Sol" />
                    </div>

                    <x-field label="Testimonio" name="content" :textarea="true" wire:model="content" maxlength="1500" placeholder="Lo que dijo tu cliente sobre tu trabajo..." />

                    <div class="rounded-xl border border-slate-200 bg-white px-4">
                        <x-toggle label="Visible en el sitio web" wire:model="isPublished" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button type="button" wire:click="closeForm" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Guardar cambios' : 'Registrar testimonio' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <livewire:admin.media-picker />
</div>
