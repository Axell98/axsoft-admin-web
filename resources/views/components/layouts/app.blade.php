@props(['title' => 'Panel'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }} - {{ config('app.name') }}</title>

        <meta name="color-scheme" content="light">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="app-bg min-h-screen font-sans text-slate-900 antialiased">
        <div x-data="{ sidebar: false }" x-on:keydown.escape.window="sidebar = false">
            {{-- Fondo del sidebar en móvil --}}
            <div
                x-show="sidebar"
                x-cloak
                x-transition.opacity
                x-on:click="sidebar = false"
                class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            ></div>

            {{-- Sidebar: oscuro; la opción activa se funde con el contenido --}}
            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-950 text-slate-300 transition-transform duration-200 lg:translate-x-0"
                x-bind:class="{ 'translate-x-0': sidebar }"
            >
                <div class="flex h-16 shrink-0 items-center justify-between px-5">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 text-white">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-white">
                            <x-icon name="dashboard" class="size-5" />
                        </span>
                        <span class="text-base font-semibold tracking-tight">{{ config('app.name') }}</span>
                    </a>
                    <button type="button" x-on:click="sidebar = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Cerrar menú">
                        <x-icon name="close" />
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto py-5 pr-4 pl-4 lg:pr-0" aria-label="Administración">
                    <p class="px-4 pb-4 text-xs text-slate-500">Administración</p>

                    <ul class="space-y-2">
                        @foreach (config('menu') as $item)
                            @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                            <li>
                                <a
                                    href="{{ route($item['route']) }}"
                                    wire:navigate
                                    @class(['sidebar-link', 'is-active' => $active])
                                    @if ($active) aria-current="page" @endif
                                >
                                    <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="shrink-0 border-t border-white/10 p-4 text-xs text-slate-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}
                </div>
            </aside>

            <div class="lg:pl-64">
                {{-- Header --}}
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6">
                    <div class="flex items-center gap-3">
                        <button type="button" x-on:click="sidebar = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú">
                            <x-icon name="menu" />
                        </button>
                        <h1 class="text-base font-semibold tracking-tight sm:text-lg">{{ $title }}</h1>
                    </div>

                    <div class="flex items-center gap-2">
                    {{-- Usuario --}}
                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
                        <button
                            type="button"
                            x-on:click="open = !open"
                            class="flex items-center gap-3 rounded-xl py-1.5 pr-2 pl-1.5 transition hover:bg-slate-100"
                            aria-haspopup="menu"
                            x-bind:aria-expanded="open"
                        >
                            <span class="flex size-9 items-center justify-center rounded-full bg-linear-to-br from-indigo-500 to-fuchsia-500 text-sm font-semibold text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                            <span class="hidden text-left sm:block">
                                <span class="block text-sm leading-tight font-medium">{{ auth()->user()->name }}</span>
                                <span class="block text-xs leading-tight text-slate-500">{{ auth()->user()->email }}</span>
                            </span>
                            <x-icon name="chevron-down" class="hidden size-4 text-slate-400 transition sm:block" x-bind:class="{ 'rotate-180': open }" />
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition.origin.top.right
                            class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10"
                            role="menu"
                        >
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="p-1.5">
                                @csrf
                                <button type="submit" role="menuitem" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-600 transition hover:bg-red-50">
                                    <x-icon name="logout" class="size-4" />
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                    </div>
                </header>

                <main class="p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Aviso global: $dispatch('notify', { message: '...', type: 'success' | 'error' }) --}}
        <div
            x-data="{ show: @js(session()->has('notify')), message: @js(session('notify', '')), type: 'success', timer: null }"
            x-init="if (show) { timer = setTimeout(() => show = false, 3500) }"
            x-on:notify.window="message = $event.detail.message; type = $event.detail.type ?? 'success'; show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 3000)"
            x-show="show"
            x-cloak
            x-transition
            class="fixed right-6 bottom-6 z-50 flex items-center gap-3 rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white shadow-xl"
            role="status"
        >
            <x-icon x-show="type === 'success'" name="check" class="size-5 text-emerald-400" />
            <x-icon x-show="type === 'error'" x-cloak name="alert" class="size-5 text-red-400" />
            <span x-text="message"></span>
        </div>

        @livewireScripts
    </body>
</html>
