@props(['category' => 'other'])

@php
    [$icon, $color] = match ($category) {
        'image' => ['image', 'bg-sky-50 text-sky-600'],
        'video' => ['video', 'bg-fuchsia-50 text-fuchsia-600'],
        'pdf' => ['file', 'bg-red-50 text-red-600'],
        'word' => ['file', 'bg-blue-50 text-blue-600'],
        'excel' => ['table', 'bg-emerald-50 text-emerald-600'],
        'powerpoint' => ['presentation', 'bg-orange-50 text-orange-600'],
        'archive' => ['archive', 'bg-amber-50 text-amber-600'],
        default => ['file', 'bg-slate-100 text-slate-500'],
    };
@endphp

<span {{ $attributes->class(['flex size-10 shrink-0 items-center justify-center rounded-lg', $color]) }}>
    <x-icon :name="$icon" class="size-5" />
</span>
