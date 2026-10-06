@props(['label', 'name', 'type' => 'text', 'textarea' => false, 'hint' => null])

@php
    $invalid = $errors->has($name);
    $inputClass = \Illuminate\Support\Arr::toCssClasses([
        'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
        'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $invalid,
        'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $invalid,
    ]);
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>

    @if ($textarea)
        <textarea id="{{ $name }}" rows="4" {{ $attributes->except('class') }} class="{{ $inputClass }}"></textarea>
    @else
        <input id="{{ $name }}" type="{{ $type }}" {{ $attributes->except('class') }} class="{{ $inputClass }}">
    @endif

    @if ($hint && ! $invalid)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
