@props(['label'])

<label class="flex cursor-pointer items-center justify-between gap-4 py-3.5 text-sm">
    <span>{{ $label }}</span>

    <span class="relative inline-flex shrink-0">
        <input type="checkbox" {{ $attributes }} class="peer sr-only">
        <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-indigo-600 peer-focus-visible:ring-4 peer-focus-visible:ring-indigo-500/30"></span>
        <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </span>
</label>
