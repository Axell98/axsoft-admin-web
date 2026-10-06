<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 py-12">
    {{-- Fondo decorativo --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-indigo-300/30 blur-3xl"></div>
        <div class="absolute -right-32 bottom-0 size-96 rounded-full bg-fuchsia-300/25 blur-3xl"></div>
        <div class="absolute bottom-10 -left-32 size-80 rounded-full bg-sky-300/25 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(15,23,42,0.09)_1px,transparent_0)] bg-size-[24px_24px] [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>
    </div>

    <main class="animate-fade-up relative w-full max-w-md">
        <div class="rounded-3xl border border-white bg-white/80 p-8 shadow-2xl ring-1 shadow-slate-900/10 ring-slate-900/5 backdrop-blur-xl sm:p-10">
            <div class="flex flex-col items-center text-center">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $name }}" class="h-14 max-w-48 object-contain">
                @else
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-linear-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/30">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                    </span>
                @endif

                <h1 class="mt-6 text-2xl font-semibold tracking-tight">Bienvenido</h1>
                <p class="mt-2 text-sm text-slate-500">Ingresa tus credenciales para acceder al panel de {{ $name }}.</p>
            </div>

            <form wire:submit="login" class="mt-8 space-y-5" x-data="{ show: false }">
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Correo electrónico</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input
                            id="email"
                            type="email"
                            wire:model="email"
                            autocomplete="email"
                            autofocus
                            placeholder="tu@empresa.com"
                            @class([
                                'w-full rounded-lg border bg-white py-2.5 pr-3.5 pl-11 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                                'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('email'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('email'),
                            ])
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600">
                            <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Contraseña</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <input
                            id="password"
                            x-bind:type="show ? 'text' : 'password'"
                            wire:model="password"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            @class([
                                'w-full rounded-lg border bg-white py-2.5 pr-11 pl-11 text-sm shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-4',
                                'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('password'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('password'),
                            ])
                        >
                        <button type="button" x-on:click="show = !show" class="absolute top-1/2 right-3 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:text-slate-600" x-bind:aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                            <svg x-show="!show" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 6.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4"/><path d="M6.6 6.7C3.9 8.4 2 12 2 12s3.6 7 10 7c1.7 0 3.2-.5 4.5-1.2"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600">
                            <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 select-none">
                    <input type="checkbox" wire:model="remember" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Mantener mi sesión iniciada
                </label>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="group flex w-full items-center justify-center gap-2 rounded-xl bg-linear-to-r from-indigo-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 transition hover:shadow-xl hover:shadow-indigo-600/30 hover:brightness-110 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <svg wire:loading wire:target="login" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    <span wire:loading.remove wire:target="login">Iniciar sesión</span>
                    <span wire:loading wire:target="login">Ingresando...</span>
                    <svg wire:loading.remove wire:target="login" class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">&copy; {{ date('Y') }} {{ $name }}. Todos los derechos reservados.</p>
    </main>
</div>
