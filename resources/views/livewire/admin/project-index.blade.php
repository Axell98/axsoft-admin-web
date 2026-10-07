<div class="mx-auto max-w-7xl">
    {{-- Cabecera --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h2 class="text-xl font-semibold tracking-tight uppercase">Proyectos</h2>
            <p class="mt-1 text-sm text-slate-500">Los proyectos que has realizado y que se muestran en tu sitio web.</p>
        </div>

        <div class="flex w-full items-center gap-3 sm:w-auto">
            <div class="relative flex-1 sm:w-64 sm:flex-none">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar proyecto"
                    class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15"
                >
            </div>

            <a
                href="{{ route('admin.projects.create') }}"
                wire:navigate
                class="flex shrink-0 items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none"
            >
                <x-icon name="plus" class="size-5" />
                Nuevo proyecto
            </a>
        </div>
    </div>

    @if ($projects->isEmpty())
        <div class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <x-icon name="{{ $search !== '' ? 'search' : 'layers' }}" class="size-7" />
            </span>
            @if ($search !== '')
                <h4 class="mt-4 font-semibold">Sin resultados</h4>
                <p class="mt-1 text-sm text-slate-500">No hay proyectos que coincidan con «{{ $search }}».</p>
            @else
                <h4 class="mt-4 font-semibold">Aún no hay proyectos</h4>
                <p class="mt-1 max-w-sm text-sm text-slate-500">Crea tu primer proyecto con sus fases, imágenes y video.</p>
                <a href="{{ route('admin.projects.create') }}" wire:navigate class="mt-5 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">Nuevo proyecto</a>
            @endif
        </div>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($projects as $project)
                @php($cover = $project->coverUrl())
                <article wire:key="project-{{ $project->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                        @if ($cover)
                            <img src="{{ $cover }}" alt="{{ $project->title }}" loading="lazy" referrerpolicy="no-referrer" @class(['size-full object-cover transition', 'opacity-60 grayscale' => ! $project->is_published])>
                        @else
                            <div class="flex size-full items-center justify-center text-slate-300">
                                <x-icon name="image" class="size-12" />
                            </div>
                        @endif

                        @unless ($project->is_published)
                            <span class="absolute top-3 left-3 rounded-full bg-slate-900/80 px-2.5 py-1 text-xs font-medium text-white">Oculto</span>
                        @endunless

                        @if ($project->phases->count() > 1)
                            <span class="absolute right-3 bottom-3 flex items-center gap-1 rounded-full bg-slate-900/70 px-2.5 py-1 text-xs font-medium text-white">
                                <x-icon name="image" class="size-3.5" /> {{ $project->phases->count() }} fases
                            </span>
                        @endif
                    </a>

                    <div class="flex-1 space-y-2 p-5">
                        <p class="text-xs font-semibold tracking-wide text-indigo-600 uppercase">{{ $project->category?->name ?: 'Sin categoría' }}</p>
                        <h3 class="text-lg leading-snug font-semibold tracking-tight uppercase">
                            <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate class="transition hover:text-indigo-600">{{ $project->title }}</a>
                        </h3>

                        <p class="text-sm text-slate-500">
                            {{ collect([$project->location, $project->year])->filter()->implode(' · ') ?: 'Sin ubicación ni año' }}
                        </p>

                        @if ($project->execution_percentage !== null)
                            <div class="flex items-center gap-3">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $project->execution_percentage }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-500">{{ $project->execution_percentage }}%</span>
                            </div>
                        @endif

                        <div class="flex items-center gap-4 pt-1 text-sm text-slate-500">
                            <span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4" /> {{ $project->created_at?->format('d-m-Y') }}</span>
                            <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-4" /> {{ $project->created_at?->format('H:i') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('admin.projects.edit', $project) }}"
                                wire:navigate
                                title="Editar"
                                aria-label="Editar {{ $project->title }}"
                                class="rounded-lg border border-emerald-200 p-2 text-emerald-600 transition hover:bg-emerald-50"
                            >
                                <x-icon name="pencil" class="size-4" />
                            </a>
                            <button
                                type="button"
                                wire:click="delete({{ $project->id }})"
                                wire:confirm="¿Eliminar el proyecto «{{ $project->title }}» y todas sus fases? Esta acción no se puede deshacer."
                                title="Eliminar"
                                aria-label="Eliminar {{ $project->title }}"
                                class="rounded-lg border border-red-200 p-2 text-red-600 transition hover:bg-red-50"
                            >
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 select-none">
                            Visible
                            <input
                                type="checkbox"
                                @checked($project->is_published)
                                wire:click="toggleVisibility({{ $project->id }})"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >
                        </label>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($projects->hasPages())
            <div class="mt-8">
                {{ $projects->links() }}
            </div>
        @endif
    @endif
</div>
