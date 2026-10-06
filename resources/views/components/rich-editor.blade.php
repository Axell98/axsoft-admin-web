@props(['id', 'value' => '', 'model' => null, 'phaseUid' => null, 'placeholder' => ''])

{{--
    Editor de texto enriquecido (TipTap, ver resources/js/rich-text.js). El contenido se envía a
    Livewire sin hacer una petición por cada tecla: se actualiza el estado y viaja con la siguiente acción.
    Con $phaseUid se actualiza el contenido de esa fase (se busca su posición al momento,
    porque puede cambiar al reordenar); con $model, la propiedad indicada.
--}}
@php
    $tool = 'flex size-8 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40';
    $toolOn = 'bg-indigo-50 text-indigo-700';
    $svg = 'size-4.5';
    $stroke = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    $group = 'flex items-center gap-0.5 border-l border-slate-200 pl-1.5 first:border-l-0 first:pl-0';
@endphp

<div
    wire:ignore
    id="{{ $id }}"
    class="rich-editor rounded-lg border border-slate-300 bg-white focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/15"
    x-data="richEditor({
        value: @js($value),
        placeholder: @js($placeholder),
        onChange(html) {
            @if ($phaseUid)
                const index = $wire.phases.findIndex((phase) => phase.uid === @js($phaseUid));
                if (index >= 0) { $wire.$set('phases.' + index + '.content', html, false); }
            @else
                $wire.$set(@js($model), html, false);
            @endif
        },
    })"
    x-on:keydown.escape="panel = null"
    x-on:click.outside="panel = null"
>
    <div class="relative flex flex-wrap items-center gap-1.5 rounded-t-lg border-b border-slate-200 bg-slate-50 px-2 py-1.5">
        <div class="{{ $group }}">
            <button type="button" title="Negrita" class="{{ $tool }}" x-bind:class="is('bold') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="run('bold')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M7 5h6a3.5 3.5 0 0 1 0 7H7z"/><path d="M7 12h7a3.5 3.5 0 0 1 0 7H7z"/></svg>
            </button>
            <button type="button" title="Cursiva" class="{{ $tool }}" x-bind:class="is('italic') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="run('italic')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M19 4h-9M14 20H5M15 4 9 20"/></svg>
            </button>
            <button type="button" title="Subrayado" class="{{ $tool }}" x-bind:class="is('underline') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="run('underline')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M6 4v7a6 6 0 0 0 12 0V4"/><path d="M4 21h16"/></svg>
            </button>
            <button type="button" title="Tachado" class="{{ $tool }}" x-bind:class="is('strike') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="run('strike')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M16 4H9a3 3 0 0 0-2.8 4"/><path d="M14 12a4 4 0 0 1 0 8H6"/><path d="M4 12h16"/></svg>
            </button>
        </div>

        <div class="{{ $group }}">
            <button type="button" title="Color del texto" class="{{ $tool }}" x-bind:class="panel === 'color' && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="togglePanel('color')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="m7 15 5-12 5 12"/><path d="M8.8 11h6.4"/><rect x="4" y="19" width="16" height="3" fill="currentColor" stroke="none"/></svg>
            </button>
            <button type="button" title="Color de fondo" class="{{ $tool }}" x-bind:class="panel === 'background' && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="togglePanel('background')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/></svg>
            </button>
        </div>

        <div class="{{ $group }}">
            <button type="button" title="Alinear a la izquierda" class="{{ $tool }}" x-bind:class="isAligned('left') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="align('left')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M4 6h16M4 10h10M4 14h16M4 18h10"/></svg>
            </button>
            <button type="button" title="Centrar" class="{{ $tool }}" x-bind:class="isAligned('center') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="align('center')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M4 6h16M7 10h10M4 14h16M7 18h10"/></svg>
            </button>
            <button type="button" title="Alinear a la derecha" class="{{ $tool }}" x-bind:class="isAligned('right') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="align('right')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M4 6h16M10 10h10M4 14h16M10 18h10"/></svg>
            </button>
            <button type="button" title="Justificar" class="{{ $tool }}" x-bind:class="isAligned('justify') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="align('justify')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            </button>
        </div>

        <div class="{{ $group }}">
            <button type="button" title="Lista con viñetas" class="{{ $tool }}" x-bind:class="is('bulletList') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="run('bulletList')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1" fill="currentColor"/><circle cx="4.5" cy="12" r="1" fill="currentColor"/><circle cx="4.5" cy="18" r="1" fill="currentColor"/></svg>
            </button>
            <button type="button" title="Lista numerada" class="{{ $tool }}" x-bind:class="is('orderedList') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="run('orderedList')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M10 6h10M10 12h10M10 18h10M4 5l1.5-1v4M4 14.5c0-1 2.5-1 2.5.3 0 1-2.5 1.7-2.5 2.7h2.5"/></svg>
            </button>
        </div>

        <div class="{{ $group }}">
            <button type="button" title="Enlace" class="{{ $tool }}" x-bind:class="(is('link') || panel === 'link') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="togglePanel('link')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1 1"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1-1"/></svg>
            </button>
            <button type="button" title="Insertar tabla" class="{{ $tool }}" x-bind:class="(inTable || panel === 'table') && '{{ $toolOn }}'" x-on:mousedown.prevent x-on:click="togglePanel('table')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M3 15h18M9 4v16M15 4v16"/></svg>
            </button>
        </div>

        <div class="{{ $group }}">
            <button type="button" title="Deshacer" class="{{ $tool }}" x-bind:disabled="!canUndo" x-on:mousedown.prevent x-on:click="run('undo')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/></svg>
            </button>
            <button type="button" title="Rehacer" class="{{ $tool }}" x-bind:disabled="!canRedo" x-on:mousedown.prevent x-on:click="run('redo')">
                <svg class="{{ $svg }}" viewBox="0 0 24 24" {!! $stroke !!}><path d="m15 14 5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h3"/></svg>
            </button>
        </div>

        {{-- Paneles --}}
        <div x-show="panel === 'color' || panel === 'background'" x-cloak class="absolute top-full left-2 z-30 mt-1 w-56 rounded-lg border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/10">
            <p class="mb-2 text-xs font-semibold text-slate-500" x-text="panel === 'color' ? 'Color del texto' : 'Color de fondo'"></p>
            <div class="grid grid-cols-4 gap-2">
                <template x-for="[color, name] in colors" x-bind:key="color">
                    <button type="button" class="aspect-square rounded-md border border-slate-900/20 transition hover:scale-110 focus-visible:outline-2 focus-visible:outline-indigo-500" x-bind:style="{ backgroundColor: color }" x-bind:title="name" x-bind:aria-label="name" x-on:mousedown.prevent x-on:click="setColor(panel, color)"></button>
                </template>
            </div>
            <button type="button" class="mt-3 w-full rounded-md py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100" x-on:mousedown.prevent x-on:click="setColor(panel, null)">Quitar color</button>
        </div>

        <div x-show="panel === 'link'" x-cloak class="absolute top-full left-2 z-30 mt-1 w-80 max-w-[calc(100%-1rem)] rounded-lg border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/10">
            <label class="mb-1.5 block text-xs font-semibold text-slate-500">Dirección del enlace</label>
            <input type="text" x-ref="linkInput" x-model="linkUrl" x-on:keydown.enter.prevent="applyLink()" placeholder="https://..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
            <div class="mt-3 flex items-center justify-between gap-2">
                <button type="button" x-show="is('link')" x-on:click="removeLink()" class="text-xs font-medium text-red-600 hover:underline">Quitar enlace</button>
                <button type="button" x-on:click="applyLink()" class="ml-auto rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">Aplicar</button>
            </div>
        </div>

        <div x-show="panel === 'table'" x-cloak class="absolute top-full left-2 z-30 mt-1 w-64 rounded-lg border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/10">
            <div x-show="!inTable">
                <p class="mb-2 text-xs font-semibold text-slate-500">Insertar tabla (con fila de títulos)</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ([[2, 2], [3, 2], [3, 3], [4, 3], [4, 4], [5, 3]] as [$rows, $cols])
                        <button type="button" class="rounded-md border border-slate-200 px-2 py-1.5 text-xs font-medium text-slate-700 hover:border-indigo-400 hover:bg-indigo-50" x-on:mousedown.prevent x-on:click="insertTable({{ $rows }}, {{ $cols }})">{{ $rows }} filas × {{ $cols }} columnas</button>
                    @endforeach
                </div>
            </div>

            <div x-show="inTable" class="space-y-1">
                <p class="mb-2 text-xs font-semibold text-slate-500">Tabla</p>
                <button type="button" class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-slate-700 hover:bg-slate-100" x-on:mousedown.prevent x-on:click="run('addRowAfter')">Agregar fila debajo</button>
                <button type="button" class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-slate-700 hover:bg-slate-100" x-on:mousedown.prevent x-on:click="run('addColumnAfter')">Agregar columna a la derecha</button>
                <button type="button" class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-slate-700 hover:bg-slate-100" x-on:mousedown.prevent x-on:click="run('toggleHeaderRow')">Fila de títulos sí / no</button>
                <button type="button" class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-slate-700 hover:bg-slate-100" x-on:mousedown.prevent x-on:click="run('deleteRow')">Eliminar fila</button>
                <button type="button" class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-slate-700 hover:bg-slate-100" x-on:mousedown.prevent x-on:click="run('deleteColumn')">Eliminar columna</button>
                <button type="button" class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-red-600 hover:bg-red-50" x-on:mousedown.prevent x-on:click="run('deleteTable')">Eliminar tabla</button>
            </div>
        </div>
    </div>

    <div x-ref="content"></div>
</div>
