<div class="mx-auto max-w-3xl">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h2 class="text-xl font-semibold tracking-tight uppercase">Categorías</h2>
            <p class="mt-1 text-sm text-slate-500">Las categorías que puedes elegir al crear un proyecto.</p>
        </div>

        <a href="{{ route('admin.projects') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-slate-500 transition hover:text-indigo-600">
            <x-icon name="arrow-left" class="size-4" />
            Volver a proyectos
        </a>
    </div>

    {{-- Nueva categoría --}}
    <form wire:submit="add" class="mt-8">
        <label for="newName" class="mb-1.5 block text-sm font-medium text-slate-700">Nueva categoría</label>
        <div class="flex gap-3">
            <input
                id="newName"
                type="text"
                wire:model="newName"
                maxlength="100"
                placeholder="Ej. Residencial"
                @class([
                    'min-w-0 flex-1 rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                    'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('newName'),
                    'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('newName'),
                ])
            >
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="add"
                class="flex shrink-0 items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
            >
                <x-icon name="plus" class="size-5" />
                Agregar
            </button>
        </div>
        @error('newName')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </form>

    {{-- Listado --}}
    @if ($categories->isEmpty())
        <div class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <x-icon name="tag" class="size-7" />
            </span>
            <h4 class="mt-4 font-semibold">Aún no hay categorías</h4>
            <p class="mt-1 max-w-sm text-sm text-slate-500">Agrega la primera arriba y luego elígela al crear o editar un proyecto.</p>
        </div>
    @else
        <ul class="mt-8 divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @foreach ($categories as $category)
                <li wire:key="category-{{ $category->id }}" class="flex flex-wrap items-center gap-3 px-5 py-3.5">
                    @if ($editingId === $category->id)
                        <div class="min-w-0 flex-1">
                            <input
                                type="text"
                                wire:model="editingName"
                                wire:keydown.enter.prevent="saveEdit"
                                wire:keydown.escape="cancelEdit"
                                maxlength="100"
                                aria-label="Nombre de la categoría"
                                autofocus
                                @class([
                                    'w-full rounded-lg border bg-white px-3 py-2 text-sm shadow-xs outline-none transition focus:ring-4',
                                    'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('editingName'),
                                    'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('editingName'),
                                ])
                            >
                            @error('editingName')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="saveEdit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-500">Guardar</button>
                            <button type="button" wire:click="cancelEdit" class="rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                        </div>
                    @else
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $category->name }}</p>
                            <p class="text-xs text-slate-500">{{ $category->projects_count === 1 ? '1 proyecto' : $category->projects_count.' proyectos' }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="startEdit({{ $category->id }})"
                                title="Cambiar nombre"
                                aria-label="Cambiar nombre de {{ $category->name }}"
                                class="rounded-lg border border-emerald-200 p-2 text-emerald-600 transition hover:bg-emerald-50"
                            >
                                <x-icon name="pencil" class="size-4" />
                            </button>
                            <button
                                type="button"
                                wire:click="delete({{ $category->id }})"
                                wire:confirm="¿Eliminar la categoría «{{ $category->name }}»? {{ $category->projects_count > 0 ? 'Sus proyectos quedarán sin categoría.' : '' }}"
                                title="Eliminar"
                                aria-label="Eliminar {{ $category->name }}"
                                class="rounded-lg border border-red-200 p-2 text-red-600 transition hover:bg-red-50"
                            >
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
