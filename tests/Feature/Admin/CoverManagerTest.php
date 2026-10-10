<?php

use App\Livewire\Admin\CoverManager;
use App\Models\MediaFile;
use App\Models\PageCover;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function coverImage(string $name = 'portada.jpg'): MediaFile
{
    $extension = pathinfo($name, PATHINFO_EXTENSION);

    return MediaFile::create([
        'name' => $name,
        'path' => 'files/2026/10/'.fake()->unique()->lexify('??????????').'.'.$extension,
        'extension' => $extension,
        'mime_type' => 'application/octet-stream',
        'size' => 1024,
    ]);
}

function makeCover(array $attributes = []): PageCover
{
    return PageCover::create(array_merge([
        'page' => 'nosotros',
        'title' => 'Conoce nuestro estudio',
        'image_type' => 'image_url',
        'external_url' => 'https://sitio.com/portada.jpg',
    ], $attributes));
}

beforeEach(function () {
    Storage::fake('public');
    // Las páginas internas dependen de cada cliente: los tests usan una lista fija.
    config(['covers.pages' => ['nosotros' => 'Nosotros', 'servicios' => 'Servicios', 'contacto' => 'Contacto']]);
    $this->actingAs(User::factory()->create());
});

// --- Acceso ------------------------------------------------------------------------

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.covers'))->assertRedirect('/login');
});

test('el menu incluye las portadas', function () {
    expect(collect(config('menu'))->pluck('route'))->toContain('admin.covers');

    $this->get(route('dashboard'))->assertSee(route('admin.covers'));
});

test('abre la primera pagina interna con el formulario vacio', function () {
    $this->get(route('admin.covers'))
        ->assertOk()
        ->assertSeeLivewire(CoverManager::class);

    Livewire::test(CoverManager::class)
        ->assertSet('page', 'nosotros')
        ->assertSet('title', '')
        ->assertSet('imageType', null)
        ->assertSet('isActive', true)
        ->assertSee(['Nosotros', 'Servicios', 'Contacto'])
        ->assertSee('Buscar en archivos o pegar un enlace')
        ->assertSee('1920x400');

    expect(PageCover::count())->toBe(0);
});

test('avisa cuando no hay paginas internas configuradas', function () {
    config(['covers.pages' => []]);

    Livewire::test(CoverManager::class)
        ->assertSet('page', '')
        ->assertSee('Aún no hay páginas internas configuradas');
});

// --- Elegir la página ------------------------------------------------------------------

test('carga la portada de la pagina elegida', function () {
    $image = coverImage();
    makeCover(['page' => 'servicios', 'title' => 'Lo que hacemos', 'image_type' => 'file', 'media_file_id' => $image->id, 'external_url' => null, 'is_active' => false]);

    Livewire::test(CoverManager::class)
        ->assertSet('imageType', null)
        ->set('page', 'servicios')
        ->assertSet('title', 'Lo que hacemos')
        ->assertSet('imageType', 'file')
        ->assertSet('mediaFileId', $image->id)
        ->assertSet('isActive', false)
        ->assertSee(Storage::disk('public')->url($image->path))
        ->set('page', 'contacto')
        ->assertSet('title', '')
        ->assertSet('imageType', null)
        ->assertSet('mediaFileId', null)
        ->assertSet('isActive', true);
});

test('la pagina elegida se puede indicar en la url', function () {
    $this->get(route('admin.covers', ['pagina' => 'contacto']))
        ->assertOk()
        ->assertSee('wire:key="cover-contacto"', false);
});

test('una pagina desconocida vuelve a la primera', function () {
    Livewire::test(CoverManager::class)
        ->set('page', 'inexistente')
        ->assertSet('page', 'nosotros');

    $this->get(route('admin.covers', ['pagina' => 'inexistente']))
        ->assertOk()
        ->assertSee('wire:key="cover-nosotros"', false);
});

test('el resumen muestra el estado de cada pagina', function () {
    makeCover(['page' => 'nosotros']);
    makeCover(['page' => 'servicios', 'is_active' => false]);

    Livewire::test(CoverManager::class)
        ->assertSeeInOrder(['Activa', 'Oculta', 'Sin portada']);
});

// --- Imagen ----------------------------------------------------------------------------

test('el boton de imagen abre el selector solo con imagenes', function () {
    Livewire::test(CoverManager::class)
        ->call('chooseImage')
        ->assertDispatched('open-media-picker', context: 'cover', mode: 'image', multiple: false, external: ['image'], used: []);
});

test('acepta una imagen por link y la reemplaza al elegir otra', function () {
    $image = coverImage();

    Livewire::test(CoverManager::class)
        ->dispatch('media-picked', context: 'cover', items: [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg']])
        ->assertSet('imageType', 'image_url')
        ->assertSet('externalUrl', 'https://sitio.com/a.jpg')
        ->dispatch('media-picked', context: 'cover', items: [['type' => 'file', 'media_file_id' => $image->id]])
        ->assertSet('imageType', 'file')
        ->assertSet('mediaFileId', $image->id)
        ->assertSet('externalUrl', null);
});

test('ignora imagenes invalidas o de otro selector', function (array|Closure $item, string $context) {
    Livewire::test(CoverManager::class)
        ->dispatch('media-picked', context: $context, items: [value($item)])
        ->assertSet('imageType', null);
})->with([
    'otro selector' => [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg'], 'testimonial'],
    'video' => [fn () => ['type' => 'file', 'media_file_id' => coverImage('clip.mp4')->id], 'cover'],
    'documento' => [fn () => ['type' => 'file', 'media_file_id' => coverImage('doc.pdf')->id], 'cover'],
    'archivo inexistente' => [['type' => 'file', 'media_file_id' => 99999], 'cover'],
    'url peligrosa' => [['type' => 'image_url', 'url' => 'javascript:alert(1)'], 'cover'],
    'youtube' => [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ'], 'cover'],
]);

// --- Guardar ---------------------------------------------------------------------------

test('guarda la portada de una pagina con todos sus datos', function () {
    $image = coverImage();

    Livewire::test(CoverManager::class)
        ->set('page', 'servicios')
        ->set('title', '  Lo que hacemos ')
        ->dispatch('media-picked', context: 'cover', items: [['type' => 'file', 'media_file_id' => $image->id]])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $cover = PageCover::firstOrFail();

    expect(PageCover::count())->toBe(1)
        ->and($cover->page)->toBe('servicios')
        ->and($cover->title)->toBe('Lo que hacemos')
        ->and($cover->image_type)->toBe('file')
        ->and($cover->media_file_id)->toBe($image->id)
        ->and($cover->imageUrl())->toBe(Storage::disk('public')->url($image->path))
        ->and($cover->is_active)->toBeTrue();
});

test('el titulo es opcional y la portada se puede guardar inactiva', function () {
    Livewire::test(CoverManager::class)
        ->dispatch('media-picked', context: 'cover', items: [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg']])
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    $cover = PageCover::firstOrFail();

    expect($cover->page)->toBe('nosotros')
        ->and($cover->title)->toBeNull()
        ->and($cover->image_type)->toBe('image_url')
        ->and($cover->external_url)->toBe('https://sitio.com/a.jpg')
        ->and($cover->media_file_id)->toBeNull()
        ->and($cover->is_active)->toBeFalse();
});

test('guardar de nuevo actualiza la portada de esa pagina sin duplicarla', function () {
    $cover = makeCover(['page' => 'nosotros']);
    makeCover(['page' => 'contacto', 'title' => 'Escríbenos']);
    $image = coverImage();

    Livewire::test(CoverManager::class)
        ->assertSet('title', 'Conoce nuestro estudio')
        ->set('title', 'Quiénes somos')
        ->dispatch('media-picked', context: 'cover', items: [['type' => 'file', 'media_file_id' => $image->id]])
        ->call('save')
        ->assertHasNoErrors();

    $cover->refresh();

    expect(PageCover::count())->toBe(2)
        ->and($cover->title)->toBe('Quiénes somos')
        ->and($cover->image_type)->toBe('file')
        ->and($cover->media_file_id)->toBe($image->id)
        ->and($cover->external_url)->toBeNull()
        ->and(PageCover::where('page', 'contacto')->value('title'))->toBe('Escríbenos');
});

test('la imagen es obligatoria', function () {
    Livewire::test(CoverManager::class)
        ->set('title', 'Sin imagen')
        ->call('save')
        ->assertHasErrors('imageType');

    expect(PageCover::count())->toBe(0);
});

test('valida el largo del titulo', function () {
    Livewire::test(CoverManager::class)
        ->dispatch('media-picked', context: 'cover', items: [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg']])
        ->set('title', str_repeat('a', 151))
        ->call('save')
        ->assertHasErrors(['title' => 'max']);

    expect(PageCover::count())->toBe(0);
});

test('rechaza una imagen manipulada al guardar', function (string $type, ?int $fileId, ?string $url) {
    Livewire::test(CoverManager::class)
        ->set('imageType', $type)
        ->set('mediaFileId', $fileId)
        ->set('externalUrl', $url)
        ->call('save')
        ->assertHasErrors('imageType');

    expect(PageCover::count())->toBe(0);
})->with([
    'archivo inexistente' => ['file', 99999, null],
    'url invalida' => ['image_url', null, 'ftp://sitio.com/a.jpg'],
    'tipo desconocido' => ['otro', null, null],
]);

test('al eliminar el archivo del gestor la portada se conserva y pide otra imagen', function () {
    $image = coverImage();
    $cover = makeCover(['image_type' => 'file', 'media_file_id' => $image->id, 'external_url' => null]);

    $image->delete();

    expect($cover->refresh()->media_file_id)->toBeNull()
        ->and($cover->imageUrl())->toBeNull();

    Livewire::test(CoverManager::class)
        ->assertSet('imageType', 'file')
        ->assertSee('La imagen ya no está disponible')
        ->assertSee('Sin portada')
        ->call('save')
        ->assertHasErrors('imageType');
});
