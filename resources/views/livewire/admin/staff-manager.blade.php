<div class="mx-auto max-w-7xl">
    {{-- Cabecera --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h2 class="text-xl font-semibold tracking-tight uppercase">Staff</h2>
            <p class="mt-1 text-sm text-slate-500">El equipo de tu empresa. Se muestra en tu sitio web en el orden que indiques.</p>
        </div>

        <button
            type="button"
            wire:click="create"
            class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none"
        >
            <x-icon name="plus" class="size-5" />
            Nuevo integrante
        </button>
    </div>

    @if ($members->isEmpty())
        <div class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <x-icon name="users" class="size-7" />
            </span>
            <h4 class="mt-4 font-semibold">Aún no hay integrantes</h4>
            <p class="mt-1 max-w-sm text-sm text-slate-500">Agrega al primero con su foto, su nombre y su cargo.</p>
            <button type="button" wire:click="create" class="mt-5 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">Nuevo integrante</button>
        </div>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($members as $member)
                @php($photo = $member->imageUrl())
                <article wire:key="member-{{ $member->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="relative aspect-square bg-slate-100">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="{{ $member->name }}" loading="lazy" referrerpolicy="no-referrer" class="size-full object-cover">
                        @else
                            <div class="flex size-full items-center justify-center text-5xl font-semibold text-slate-300 uppercase">{{ mb_substr($member->name, 0, 1) }}</div>
                        @endif

                        <span class="absolute top-3 left-3 flex min-w-7 items-center justify-center rounded-full bg-slate-900/75 px-2 py-1 text-xs font-semibold text-white" title="Orden">{{ $member->sort_order }}</span>
                    </div>

                    <div class="flex-1 p-5">
                        <h3 class="truncate font-semibold tracking-tight">{{ $member->name }}</h3>
                        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $member->role ?: 'Sin cargo' }}</p>
                    </div>

                    <div class="flex items-center gap-2 border-t border-slate-200 px-5 py-3.5">
                        <button
                            type="button"
                            wire:click="edit({{ $member->id }})"
                            title="Editar"
                            aria-label="Editar a {{ $member->name }}"
                            class="rounded-lg border border-emerald-200 p-2 text-emerald-600 transition hover:bg-emerald-50"
                        >
                            <x-icon name="pencil" class="size-4" />
                        </button>
                        <button
                            type="button"
                            wire:click="delete({{ $member->id }})"
                            wire:confirm="¿Eliminar a «{{ $member->name }}» del staff? Esta acción no se puede deshacer."
                            title="Eliminar"
                            aria-label="Eliminar a {{ $member->name }}"
                            class="rounded-lg border border-red-200 p-2 text-red-600 transition hover:bg-red-50"
                        >
                            <x-icon name="trash" class="size-4" />
                        </button>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($members->hasPages())
            <div class="mt-8">
                {{ $members->links() }}
            </div>
        @endif
    @endif

    {{-- Modal: registrar o editar --}}
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ $editingId ? 'Editar integrante' : 'Nuevo integrante' }}" x-data x-on:keydown.escape.window="$wire.closeForm()">
            <div wire:click="closeForm" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

            <form wire:submit="save" class="relative flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-semibold tracking-tight">{{ $editingId ? 'Editar integrante' : 'Nuevo integrante' }}</h3>
                    <button type="button" wire:click="closeForm" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100" aria-label="Cerrar">
                        <x-icon name="close" />
                    </button>
                </div>

                <div class="space-y-5 overflow-y-auto p-6">
                    {{-- Foto --}}
                    <div>
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">Foto <span class="font-normal text-slate-400">(opcional)</span></span>

                        <div class="flex items-center gap-4">
                            @if ($imagePreview)
                                <img src="{{ $imagePreview }}" alt="Foto del integrante" referrerpolicy="no-referrer" class="size-24 shrink-0 rounded-xl object-cover ring-2 ring-slate-100">
                            @else
                                <span class="flex size-24 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-300">
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

                    <x-field label="Nombre" name="name" wire:model="name" maxlength="120" placeholder="Ej. Carlos Ruiz" autofocus />
                    <x-field label="Cargo (opcional)" name="role" wire:model="role" maxlength="120" placeholder="Ej. Arquitecto principal" />
                    <x-field
                        label="Orden"
                        name="sortOrder"
                        type="number"
                        min="0"
                        max="9999"
                        wire:model="sortOrder"
                        hint="Los números más bajos se muestran primero. Si dos tienen el mismo, va primero el que se registró antes."
                    />
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button type="button" wire:click="closeForm" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Guardar cambios' : 'Registrar integrante' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <livewire:admin.media-picker />
</div>
