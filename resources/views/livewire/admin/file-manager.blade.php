<div class="mx-auto max-w-7xl">
    {{-- Cabecera --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h2 class="text-xl font-semibold tracking-tight uppercase">Archivos</h2>
            <p class="mt-1 text-sm text-slate-500">Organiza en carpetas las imágenes, videos y documentos de tu sitio web.</p>
        </div>

        <div class="flex w-full items-center gap-3 sm:w-auto">
            <div class="relative flex-1 sm:w-64 sm:flex-none">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar archivo"
                    class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15"
                >
            </div>

            <button
                type="button"
                x-on:click="$dispatch('open-uploader')"
                class="flex shrink-0 items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none"
            >
                <x-icon name="cloud-upload" class="size-5" />
                <span class="hidden sm:inline">Cargar archivos</span>
                <span class="sm:hidden">Cargar</span>
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
        {{-- Carpetas --}}
        <aside>
            <h3 class="mb-3 px-1 text-sm font-semibold text-indigo-600">Carpetas</h3>

            <ul class="space-y-1">
                <li>
                    <button
                        type="button"
                        wire:click="selectFolder"
                        @class([
                            'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition',
                            'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' => $folderId === null,
                            'text-slate-600 hover:bg-slate-100' => $folderId !== null,
                        ])
                    >
                        <x-icon name="folder" class="size-5 shrink-0" />
                        <span class="flex-1 truncate">Todos los archivos</span>
                        <span class="text-xs opacity-70">{{ $totalFiles }}</span>
                    </button>
                </li>

                @foreach ($folders as $folder)
                    <li wire:key="folder-{{ $folder->id }}" class="group relative">
                        <button
                            type="button"
                            wire:click="selectFolder({{ $folder->id }})"
                            @class([
                                'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition',
                                'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' => $folderId === $folder->id,
                                'text-slate-600 hover:bg-slate-100' => $folderId !== $folder->id,
                            ])
                        >
                            <x-icon name="folder" class="size-5 shrink-0" />
                            <span class="flex-1 truncate">{{ $folder->name }}</span>
                            <span class="text-xs opacity-70 lg:group-hover:hidden lg:group-focus-within:hidden">{{ $folder->files_count }}</span>
                        </button>

                        <div class="absolute top-1/2 right-2 flex -translate-y-1/2 items-center gap-0.5 lg:hidden lg:group-focus-within:flex lg:group-hover:flex">
                            <button type="button" wire:click="openFolderModal({{ $folder->id }})" title="Renombrar" aria-label="Renombrar carpeta" class="rounded-md p-1.5 transition hover:bg-black/10 {{ $folderId === $folder->id ? 'text-white' : 'text-slate-500 ' }}">
                                <x-icon name="pencil" class="size-4" />
                            </button>
                            <button
                                type="button"
                                wire:click="deleteFolder({{ $folder->id }})"
                                wire:confirm="¿Eliminar la carpeta «{{ $folder->name }}»?{{ $folder->files_count ? ' Se eliminarán también sus '.$folder->files_count.' archivo(s).' : '' }}"
                                title="Eliminar"
                                aria-label="Eliminar carpeta"
                                class="rounded-md p-1.5 transition hover:bg-black/10 {{ $folderId === $folder->id ? 'text-white' : 'text-red-500' }}"
                            >
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <button
                type="button"
                wire:click="openFolderModal"
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:border-indigo-400 hover:text-indigo-600"
            >
                <x-icon name="folder-plus" class="size-5" />
                Nueva carpeta
            </button>
        </aside>

        {{-- Listado --}}
        <section class="min-w-0">
            @if (count($selected) > 0)
                <div class="mb-3 flex items-center justify-between rounded-xl bg-indigo-50 px-4 py-2.5 text-sm">
                    <span class="font-medium text-indigo-700">{{ count($selected) }} seleccionado(s)</span>
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="$set('selected', [])" class="rounded-lg px-3 py-1.5 font-medium text-slate-600 transition hover:bg-white/60">Cancelar</button>
                        <button type="button" wire:click="openMoveModal" class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-medium text-indigo-700 transition hover:bg-white/60">
                            <x-icon name="folder-move" class="size-4" />
                            Mover
                        </button>
                        <button
                            type="button"
                            wire:click="deleteSelected"
                            wire:confirm="¿Eliminar {{ count($selected) }} archivo(s) seleccionado(s)? Esta acción no se puede deshacer."
                            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-medium text-red-600 transition hover:bg-red-100"
                        >
                            <x-icon name="trash" class="size-4" />
                            Eliminar
                        </button>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                @if ($files->isEmpty())
                    <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                            <x-icon name="{{ $search !== '' ? 'search' : 'cloud-upload' }}" class="size-7" />
                        </span>
                        @if ($search !== '')
                            <h4 class="mt-4 font-semibold">Sin resultados</h4>
                            <p class="mt-1 text-sm text-slate-500">No hay archivos que coincidan con «{{ $search }}».</p>
                        @else
                            <h4 class="mt-4 font-semibold">{{ $folderId ? 'Esta carpeta está vacía' : 'Aún no hay archivos' }}</h4>
                            <p class="mt-1 text-sm text-slate-500">Carga imágenes, videos o documentos para usarlos en tu sitio web.</p>
                            <button type="button" x-on:click="$dispatch('open-uploader')" class="mt-5 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                Cargar archivos
                            </button>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                    <th class="w-12 py-3 pr-2 pl-4">
                                        <input
                                            type="checkbox"
                                            aria-label="Seleccionar todos"
                                            class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            x-on:change="$wire.selected = $event.target.checked ? @js($pageIds) : []"
                                            x-bind:checked="$wire.selected.length > 0 && @js($pageIds).every((id) => $wire.selected.map(Number).includes(id))"
                                        >
                                    </th>
                                    <th class="px-3 py-3">Nombre</th>
                                    <th class="hidden px-3 py-3 md:table-cell">Tipo</th>
                                    <th class="hidden px-3 py-3 md:table-cell">Tamaño</th>
                                    <th class="hidden px-3 py-3 lg:table-cell">Modificación</th>
                                    <th class="px-4 py-3 text-right">Opciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($files as $file)
                                    <tr wire:key="file-{{ $file->id }}" class="transition hover:bg-slate-50">
                                        <td class="py-3 pr-2 pl-4">
                                            <input
                                                type="checkbox"
                                                wire:model.live="selected"
                                                value="{{ $file->id }}"
                                                aria-label="Seleccionar {{ $file->name }}"
                                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            >
                                        </td>
                                        <td class="max-w-0 px-3 py-3 md:max-w-sm lg:max-w-md xl:max-w-lg">
                                            <a href="{{ $file->url() }}" target="_blank" rel="noopener" class="flex items-center gap-3">
                                                @if ($file->isImage())
                                                    <img src="{{ $file->url() }}" alt="" loading="lazy" class="size-10 shrink-0 rounded-lg border border-slate-200 bg-slate-50 object-cover">
                                                @else
                                                    <x-file-icon :category="$file->category()" />
                                                @endif
                                                <span class="min-w-0">
                                                    <span class="block truncate font-medium text-indigo-600 hover:underline">{{ $file->name }}</span>
                                                    @if ($folderId === null && $file->folder)
                                                        <span class="block truncate text-xs text-slate-400">{{ $file->folder->name }}</span>
                                                    @endif
                                                </span>
                                            </a>
                                        </td>
                                        <td class="hidden px-3 py-3 text-slate-600 uppercase md:table-cell">{{ $file->extension }}</td>
                                        <td class="hidden px-3 py-3 whitespace-nowrap text-slate-600 md:table-cell">{{ Number::fileSize($file->size, precision: 1) }}</td>
                                        <td class="hidden px-3 py-3 whitespace-nowrap text-slate-600 lg:table-cell">{{ $file->created_at?->translatedFormat('d M Y H:i') }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button
                                                    type="button"
                                                    x-on:click="navigator.clipboard.writeText(@js($file->url())).then(() => $dispatch('notify', { message: 'Enlace copiado' }), () => $dispatch('notify', { message: 'No se pudo copiar el enlace', type: 'error' }))"
                                                    title="Copiar enlace"
                                                    aria-label="Copiar enlace"
                                                    class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                                >
                                                    <x-icon name="copy" class="size-4" />
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="openMoveModal({{ $file->id }})"
                                                    title="Mover a otra carpeta"
                                                    aria-label="Mover a otra carpeta"
                                                    class="rounded-lg border border-indigo-200 p-2 text-indigo-600 transition hover:bg-indigo-50"
                                                >
                                                    <x-icon name="folder-move" class="size-4" />
                                                </button>
                                                <a
                                                    href="{{ route('admin.files.download', $file) }}"
                                                    title="Descargar"
                                                    aria-label="Descargar"
                                                    class="rounded-lg border border-sky-200 p-2 text-sky-600 transition hover:bg-sky-50"
                                                >
                                                    <x-icon name="download" class="size-4" />
                                                </a>
                                                <button
                                                    type="button"
                                                    wire:click="deleteFile({{ $file->id }})"
                                                    wire:confirm="¿Eliminar «{{ $file->name }}»? Esta acción no se puede deshacer."
                                                    title="Eliminar"
                                                    aria-label="Eliminar"
                                                    class="rounded-lg border border-red-200 p-2 text-red-600 transition hover:bg-red-50"
                                                >
                                                    <x-icon name="trash" class="size-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($files->hasPages())
                        <div class="border-t border-slate-200 px-4 py-3">
                            {{ $files->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>

    {{-- Modal: cargar archivos --}}
    <div
        x-data="uploader({ maxKb: @js($maxKb), extensions: @js($extensions) })"
        x-on:open-uploader.window="show()"
        x-on:keydown.escape.window="if (open) close()"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Cargar archivos"
    >
        <div x-show="open" x-transition.opacity x-on:click="close()" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

        <div x-show="open" x-transition class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold tracking-tight">Cargar archivos</h3>
                <button type="button" x-on:click="close()" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100" aria-label="Cerrar">
                    <x-icon name="close" />
                </button>
            </div>

            <div class="space-y-5 overflow-y-auto px-6 py-5">
                <div>
                    <label for="upload-folder" class="mb-1.5 block text-sm font-medium text-slate-700">Carpeta de destino</label>
                    <select
                        id="upload-folder"
                        wire:model="uploadFolder"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15"
                    >
                        <option value="">Sin carpeta</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" wire:key="upload-folder-{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Zona para soltar archivos --}}
                <label
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="drop($event)"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-8 text-center transition"
                    x-bind:class="dragging ? 'border-indigo-500 bg-indigo-50 ' : 'border-slate-300 hover:border-indigo-400 hover:bg-slate-50 '"
                >
                    <x-icon name="cloud-upload" class="size-10 text-indigo-500" />
                    <p class="mt-3 text-sm font-medium">Arrastra tus archivos aquí o <span class="text-indigo-600">haz clic para seleccionarlos</span></p>
                    <p class="mt-1 text-xs text-slate-500">
                        Máximo <span x-text="format(@js($maxKb) * 1024)"></span> por archivo. Imágenes, videos, PDF, Word, Excel, PowerPoint, TXT, CSV y ZIP.
                    </p>
                    <input type="file" multiple class="sr-only" x-on:change="pick($event)">
                </label>

                {{-- Cola de subida --}}
                <ul x-show="items.length" class="space-y-2">
                    <template x-for="item in items" :key="item.id">
                        <li class="rounded-xl border border-slate-200 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium" x-text="item.name"></p>
                                    <p class="text-xs text-slate-500">
                                        <span x-text="format(item.size)"></span>
                                        <span x-show="item.status === 'queued'"> · En espera</span>
                                        <span x-show="item.status === 'uploading'" x-text="' · Subiendo ' + item.progress + '%'"></span>
                                        <span x-show="item.status === 'done'" class="text-emerald-600"> · Completado</span>
                                    </p>
                                </div>

                                <x-icon x-show="item.status === 'done'" x-cloak name="check" class="size-5 shrink-0 text-emerald-500" />
                                <button x-show="item.status === 'error' && item.retryable" x-cloak type="button" x-on:click="retry(item)" class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50">Reintentar</button>
                                <button x-show="item.status === 'queued' || item.status === 'error'" x-cloak type="button" x-on:click="remove(item)" class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Quitar de la lista">
                                    <x-icon name="close" class="size-4" />
                                </button>
                            </div>

                            <div x-show="item.status === 'uploading' || item.status === 'queued' || item.status === 'done'" class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full transition-all duration-200"
                                    x-bind:class="item.status === 'done' ? 'bg-emerald-500' : 'bg-indigo-500'"
                                    x-bind:style="'width: ' + item.progress + '%'"
                                ></div>
                            </div>

                            <p x-show="item.status === 'error'" x-cloak class="mt-1 flex items-start gap-1.5 text-xs text-red-600" x-text="item.error"></p>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-6 py-4">
                <p class="text-sm text-slate-500">
                    <span x-show="total === 0">Aún no has elegido archivos.</span>
                    <span x-show="total > 0">
                        <span x-text="completed"></span> de <span x-text="total"></span> completados<span x-show="failed > 0" class="text-red-600" x-text="' · ' + failed + ' con error'"></span>
                    </span>
                </p>
                <div class="flex items-center gap-2">
                    <button type="button" x-show="items.some((i) => i.status === 'done' || i.status === 'error')" x-cloak x-on:click="clearFinished()" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Limpiar lista</button>
                    <button type="button" x-on:click="close()" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        <span x-text="running ? 'Cerrar (sigue subiendo)' : 'Listo'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: mover archivos --}}
    @if ($showMoveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" x-data x-on:keydown.escape.window="$wire.closeMoveModal()">
            <div wire:click="closeMoveModal" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

            <form wire:submit="moveFiles" class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold tracking-tight">Mover {{ count($moveIds) === 1 ? 'archivo' : count($moveIds).' archivos' }}</h3>

                <div class="mt-4">
                    <label for="move-folder" class="mb-1.5 block text-sm font-medium text-slate-700">Carpeta de destino</label>
                    <select
                        id="move-folder"
                        wire:model="moveTo"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15"
                    >
                        <option value="">Sin carpeta</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" wire:key="move-folder-{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                    @error('moveTo')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeMoveModal" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">Mover</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal: crear / renombrar carpeta --}}
    @if ($showFolderModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" x-data x-on:keydown.escape.window="$wire.closeFolderModal()">
            <div wire:click="closeFolderModal" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

            <form wire:submit="saveFolder" class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold tracking-tight">{{ $editingFolderId ? 'Renombrar carpeta' : 'Nueva carpeta' }}</h3>

                <div class="mt-4">
                    <label for="folder-name" class="mb-1.5 block text-sm font-medium text-slate-700">Nombre</label>
                    <input
                        id="folder-name"
                        type="text"
                        wire:model="folderName"
                        maxlength="60"
                        autofocus
                        placeholder="Ej. Proyectos 2026"
                        @class([
                            'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                            'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('folderName'),
                            'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('folderName'),
                        ])
                    >
                    @error('folderName')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeFolderModal" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        {{ $editingFolderId ? 'Guardar' : 'Crear carpeta' }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
