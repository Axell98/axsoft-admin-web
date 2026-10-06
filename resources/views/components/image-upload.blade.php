@props(['name', 'label', 'current' => null, 'file' => null, 'hint' => 'PNG, JPG o WEBP, máximo 2 MB.'])

<div class="flex items-center gap-5">
    <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-2">
        @if ($file && $file->isPreviewable())
            <img src="{{ $file->temporaryUrl() }}" alt="Vista previa" class="max-h-full max-w-full object-contain">
        @elseif ($current)
            <img src="{{ $current }}" alt="{{ $label }} actual" class="max-h-full max-w-full object-contain">
        @else
            <x-icon name="image" class="size-8 text-slate-300" />
        @endif
    </div>

    <div class="space-y-2">
        <p class="text-sm font-medium text-slate-700">{{ $label }}</p>

        <div class="flex flex-wrap items-center gap-2">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium transition hover:bg-slate-50">
                <x-icon name="upload" class="size-4" />
                {{ $current || $file ? 'Cambiar' : 'Subir' }}
                <input type="file" wire:model="{{ $name }}" accept="image/png,image/jpeg,image/webp" class="sr-only">
            </label>

            @if ($current && ! $file)
                <button
                    type="button"
                    wire:click="removeImage('{{ $name }}')"
                    wire:confirm="¿Quitar {{ Str::lower($label) }} actual?"
                    class="rounded-xl px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                >
                    Quitar
                </button>
            @endif

            <span wire:loading wire:target="{{ $name }}" class="text-sm text-slate-500">Subiendo...</span>
        </div>

        <p class="text-xs text-slate-500">
            {{ $hint }}
            @if ($file) Se aplicará al pulsar "Actualizar datos". @endif
        </p>

        @error($name)
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
