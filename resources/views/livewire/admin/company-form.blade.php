<div
    x-data="{ toast: false, timer: null }"
    x-on:saved.window="toast = true; clearTimeout(timer); timer = setTimeout(() => toast = false, 3000)"
>
    <form wire:submit="save" class="mx-auto max-w-5xl">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h2 class="text-xl font-semibold tracking-tight uppercase">Empresa</h2>
                <p class="mt-1 text-sm text-slate-500">Esta información se mostrará en tu sitio web. Solo el nombre es obligatorio.</p>
            </div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:ring-4 focus:ring-indigo-500/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
            >
                <svg wire:loading wire:target="save" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <x-icon wire:loading.remove wire:target="save" name="refresh" class="size-4" />
                <span wire:loading.remove wire:target="save">Actualizar datos</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        </div>

        {{-- Imágenes --}}
        <section class="mt-8">
            <x-form-section title="Imágenes" />

            <div class="grid gap-6 md:grid-cols-2">
                <x-image-upload name="logo" label="Logo" :current="$currentLogoUrl" :file="$logo" />
                <x-image-upload name="favicon" label="Favicon" :current="$currentFaviconUrl" :file="$favicon" hint="Icono de la pestaña del navegador. PNG cuadrado (512x512 recomendado), máximo 2 MB." />
            </div>
        </section>

        {{-- Datos principales --}}
        <section class="mt-10">
            <x-form-section title="Datos principales" />

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <x-field label="Nombre comercial" name="name" wire:model="name" autocomplete="organization" />
                <x-field label="Razón social" name="legal_name" wire:model="legal_name" />
                <x-field label="RUC" name="ruc" wire:model="ruc" inputmode="numeric" maxlength="11" placeholder="11 dígitos" />
                <x-field label="Lema o eslogan" name="slogan" wire:model="slogan" class="md:col-span-2 lg:col-span-3" />
            </div>
        </section>

        {{-- Contacto --}}
        <section class="mt-10">
            <x-form-section title="Contacto y ubicación" />

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <x-field label="Dirección" name="address" wire:model="address" class="lg:col-span-2" autocomplete="street-address" />
                <x-field label="Enlace de Google Maps" name="maps_url" type="url" wire:model="maps_url" placeholder="https://maps.google.com/..." />
                <x-field label="Correo" name="email" type="email" wire:model="email" placeholder="contacto@empresa.com" />
                <x-field label="Correo secundario" name="email_2" type="email" wire:model="email_2" />
                <x-field label="Teléfono fijo" name="phone" type="tel" wire:model="phone" placeholder="01 234 5678" />
                <x-field label="WhatsApp" name="whatsapp" type="tel" wire:model="whatsapp" placeholder="+51 999 999 999" />
                <x-field label="WhatsApp secundario" name="whatsapp_2" type="tel" wire:model="whatsapp_2" />
                <x-field label="Horario de atención" name="business_hours" wire:model="business_hours" placeholder="Lun a Vie 9:00 - 18:00" />
            </div>
        </section>

        {{-- Web y redes sociales --}}
        <section class="mt-10">
            <x-form-section title="Sitio web y redes sociales" />

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <x-field label="Sitio web" name="website" type="url" wire:model="website" placeholder="https://www.tuempresa.com" />
                <x-field label="Facebook" name="facebook" type="url" wire:model="facebook" placeholder="https://www.facebook.com/tu-pagina" />
                <x-field label="Instagram" name="instagram" type="url" wire:model="instagram" placeholder="https://www.instagram.com/tu-usuario" />
                <x-field label="YouTube" name="youtube" type="url" wire:model="youtube" placeholder="https://www.youtube.com/@tu-canal" />
                <x-field label="TikTok" name="tiktok" type="url" wire:model="tiktok" placeholder="https://www.tiktok.com/@tu-usuario" />
                <x-field label="LinkedIn" name="linkedin" type="url" wire:model="linkedin" placeholder="https://www.linkedin.com/company/tu-empresa" />
                <x-field label="Behance" name="behance" type="url" wire:model="behance" placeholder="https://www.behance.net/tu-usuario" />
            </div>
        </section>

        {{-- Descripción y SEO --}}
        <section class="mt-10">
            <x-form-section title="Otros datos (opcionales)" />

            <div class="grid gap-5 md:grid-cols-2">
                <x-field label="Descripción de la empresa" name="description" textarea wire:model="description" class="md:col-span-2" hint="Un breve texto sobre la empresa (máx. 2000 caracteres)." />
                <x-field label="Meta título (SEO)" name="meta_title" wire:model="meta_title" maxlength="70" hint="Título que aparece en Google (máx. 70 caracteres)." />
                <x-field label="Meta descripción (SEO)" name="meta_description" wire:model="meta_description" maxlength="160" hint="Resumen que aparece en Google (máx. 160 caracteres)." />
            </div>
        </section>
    </form>

    {{-- Aviso de guardado --}}
    <div
        x-show="toast"
        x-cloak
        x-transition
        class="fixed right-6 bottom-6 z-50 flex items-center gap-3 rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white shadow-xl"
        role="status"
    >
        <svg class="size-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
        Cambios guardados correctamente
    </div>
</div>
