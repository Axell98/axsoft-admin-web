<x-layouts.app title="Dashboard">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold tracking-tight">Hola, {{ auth()->user()->name }} 👋</h2>
        <p class="mt-1 text-sm text-slate-500">Este es el resumen de tu sitio web.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <a href="{{ route($stat['route']) }}" wire:navigate class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-indigo-300 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <span class="flex size-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <x-icon :name="$stat['icon']" class="size-5" />
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold tracking-tight">{{ $stat['value'] }}</p>
            </a>
        @endforeach
    </div>
</x-layouts.app>
