<?php

use App\Livewire\Admin\PopupManager;
use App\Models\MediaFile;
use App\Models\Popup;
use App\Models\PopupItem;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function popupMedia(string $name = 'promo.jpg'): MediaFile
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

/**
 * @return array<string, mixed>
 */
function pickedFile(MediaFile $file): array
{
    return ['type' => 'file', 'media_file_id' => $file->id];
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

// --- Acceso y valores iniciales -----------------------------------------------

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.popup'))->assertRedirect('/login');
});

test('la pagina carga vacia sin escribir en la base de datos', function () {
    $this->get(route('admin.popup'))
        ->assertOk()
        ->assertSeeLivewire(PopupManager::class)
        ->assertSee('Vista previa')
        ->assertSee('Elegir una imagen')
        ->assertSee('Oculto (borrador)');

    expect(Popup::count())->toBe(0);
});

test('el menu incluye el pop-up', function () {
    expect(collect(config('menu'))->pluck('route'))->toContain('admin.popup');

    $this->get(route('dashboard'))->assertSee(route('admin.popup'));
});

test('carga los datos guardados', function () {
    $file = popupMedia();
    $popup = Popup::create(['title' => 'Promo', 'display_type' => 'slider', 'is_visible' => true, 'show_header' => true, 'show_border' => true, 'link_url' => '/contacto']);
    $popup->items()->create(['type' => 'file', 'media_file_id' => $file->id, 'position' => 0]);
    $popup->items()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg', 'position' => 1]);

    Livewire::test(PopupManager::class)
        ->assertSet('title', 'Promo')
        ->assertSet('displayType', 'slider')
        ->assertSet('isVisible', true)
        ->assertSet('showHeader', true)
        ->assertSet('showBorder', true)
        ->assertSet('linkUrl', '/contacto')
        ->assertCount('items', 2)
        ->assertSet('items.1.url', 'https://sitio.com/a.jpg');
});

// --- Elegir contenido ---------------------------------------------------------------

test('el boton buscar abre el selector con los filtros del tipo', function (string $type, string $mode, bool $multiple, string $external) {
    Livewire::test(PopupManager::class)
        ->set('displayType', $type)
        ->call('choose')
        ->assertDispatched('open-media-picker', context: 'popup', mode: $mode, multiple: $multiple, external: [$external], used: []);
})->with([
    'imagen' => ['image', 'image', false, 'image'],
    'slider' => ['slider', 'image', true, 'image'],
    'video' => ['video', 'video', false, 'youtube'],
]);

test('una imagen reemplaza a la anterior', function () {
    $first = popupMedia('uno.jpg');
    $second = popupMedia('dos.png');

    Livewire::test(PopupManager::class)
        ->dispatch('media-picked', context: 'popup', items: [pickedFile($first)])
        ->assertCount('items', 1)
        ->dispatch('media-picked', context: 'popup', items: [pickedFile($second)])
        ->assertCount('items', 1)
        ->assertSet('items.0.media_file_id', $second->id);
});

test('una imagen por link se acepta en imagen y slider', function (string $type) {
    Livewire::test(PopupManager::class)
        ->set('displayType', $type)
        ->dispatch('media-picked', context: 'popup', items: [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg']])
        ->assertCount('items', 1)
        ->assertSet('items.0.url', 'https://sitio.com/a.jpg');
})->with(['image', 'slider']);

test('el slider agrega varias imagenes y respeta el maximo', function () {
    $component = Livewire::test(PopupManager::class)->set('displayType', 'slider');

    $component->dispatch('media-picked', context: 'popup', items: [pickedFile(popupMedia('a.jpg')), pickedFile(popupMedia('b.jpg'))])
        ->assertCount('items', 2);

    $many = array_map(fn () => pickedFile(popupMedia('x.jpg')), range(1, Popup::MAX_ITEMS));

    $component->dispatch('media-picked', context: 'popup', items: $many)
        ->assertCount('items', Popup::MAX_ITEMS);
});

test('el video acepta un archivo de video o youtube', function () {
    $video = popupMedia('promo.mp4');

    Livewire::test(PopupManager::class)
        ->set('displayType', 'video')
        ->dispatch('media-picked', context: 'popup', items: [pickedFile($video)])
        ->assertSet('items.0.media_file_id', $video->id)
        ->dispatch('media-picked', context: 'popup', items: [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']])
        ->assertCount('items', 1)
        ->assertSet('items.0.type', 'youtube')
        ->assertSet('items.0.youtube_id', 'dQw4w9WgXcQ');
});

test('ignora contenido que no corresponde al tipo o es invalido', function (string $type, array $item) {
    Livewire::test(PopupManager::class)
        ->set('displayType', $type)
        ->dispatch('media-picked', context: 'popup', items: [$item])
        ->assertCount('items', 0);
})->with([
    'video en imagen' => ['image', fn () => pickedFile(popupMedia('v.mp4'))],
    'youtube en imagen' => ['image', ['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']],
    'imagen en video' => ['video', fn () => pickedFile(popupMedia('i.jpg'))],
    'link de imagen en video' => ['video', ['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg']],
    'documento' => ['image', fn () => pickedFile(popupMedia('doc.pdf'))],
    'archivo inexistente' => ['image', ['type' => 'file', 'media_file_id' => 99999]],
    'url peligrosa' => ['image', ['type' => 'image_url', 'url' => 'javascript:alert(1)']],
    'youtube invalido' => ['video', ['type' => 'youtube', 'youtube_id' => 'corto']],
    'tipo desconocido' => ['image', ['type' => 'otro']],
]);

test('ignora lo elegido por otro selector', function () {
    Livewire::test(PopupManager::class)
        ->dispatch('media-picked', context: 'phase', items: [pickedFile(popupMedia())])
        ->assertCount('items', 0);
});

// --- Cambio de tipo -----------------------------------------------------------------

test('al pasar de slider a imagen se conserva solo la primera imagen', function () {
    $component = Livewire::test(PopupManager::class)
        ->set('displayType', 'slider')
        ->dispatch('media-picked', context: 'popup', items: [pickedFile(popupMedia('a.jpg')), pickedFile(popupMedia('b.jpg')), pickedFile(popupMedia('c.jpg'))]);

    $first = $component->get('items.0.media_file_id');

    $component->set('displayType', 'image')
        ->assertCount('items', 1)
        ->assertSet('items.0.media_file_id', $first)
        ->assertDispatched('notify');
});

test('al pasar a video se quita el contenido que no es video', function () {
    Livewire::test(PopupManager::class)
        ->dispatch('media-picked', context: 'popup', items: [pickedFile(popupMedia('a.jpg'))])
        ->set('displayType', 'video')
        ->assertCount('items', 0)
        ->assertDispatched('notify');
});

test('al pasar entre imagen y slider se conserva el contenido', function () {
    Livewire::test(PopupManager::class)
        ->dispatch('media-picked', context: 'popup', items: [pickedFile(popupMedia('a.jpg'))])
        ->set('displayType', 'slider')
        ->assertCount('items', 1)
        ->assertNotDispatched('notify');
});

test('un tipo invalido vuelve a imagen', function () {
    Livewire::test(PopupManager::class)->set('displayType', 'hackeado')->assertSet('displayType', 'image');
});

// --- Quitar y ordenar -----------------------------------------------------------------

test('quita, ordena y limpia el contenido', function () {
    $component = Livewire::test(PopupManager::class)
        ->set('displayType', 'slider')
        ->dispatch('media-picked', context: 'popup', items: [pickedFile($a = popupMedia('a.jpg')), pickedFile($b = popupMedia('b.jpg')), pickedFile($c = popupMedia('c.jpg'))]);

    $uids = collect($component->get('items'))->pluck('uid')->all();

    $component->call('moveItem', $uids[0], 'right')
        ->assertSet('items.0.media_file_id', $b->id)
        ->assertSet('items.1.media_file_id', $a->id)
        ->call('moveItem', $uids[0], 'left')
        ->assertSet('items.0.media_file_id', $a->id)
        ->call('moveItem', $uids[0], 'left')
        ->assertSet('items.0.media_file_id', $a->id)
        ->call('removeItem', $uids[1])
        ->assertCount('items', 2)
        ->call('clearItems')
        ->assertCount('items', 0);
});

// --- Guardar ------------------------------------------------------------------------

test('guarda un pop-up de imagen con sus opciones', function () {
    $file = popupMedia();

    Livewire::test(PopupManager::class)
        ->set('title', '  Promo de aniversario ')
        ->set('isVisible', true)
        ->set('showHeader', true)
        ->set('showBorder', true)
        ->set('linkUrl', 'https://sitio.com/promo')
        ->dispatch('media-picked', context: 'popup', items: [pickedFile($file)])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $popup = Popup::firstOrFail();

    expect($popup->title)->toBe('Promo de aniversario')
        ->and($popup->display_type)->toBe('image')
        ->and($popup->is_visible)->toBeTrue()
        ->and($popup->show_header)->toBeTrue()
        ->and($popup->show_border)->toBeTrue()
        ->and($popup->link_url)->toBe('https://sitio.com/promo')
        ->and($popup->items)->toHaveCount(1)
        ->and($popup->items->first()->media_file_id)->toBe($file->id);
});

test('guarda un slider en el orden elegido y un video de youtube', function () {
    $a = popupMedia('a.jpg');
    $b = popupMedia('b.jpg');

    $component = Livewire::test(PopupManager::class)
        ->set('displayType', 'slider')
        ->set('isVisible', true)
        ->dispatch('media-picked', context: 'popup', items: [pickedFile($a), pickedFile($b), ['type' => 'image_url', 'url' => 'https://sitio.com/c.jpg']])
        ->call('save')
        ->assertHasNoErrors();

    expect(Popup::first()->items->map(fn (PopupItem $item) => $item->media_file_id ?? $item->external_url)->all())
        ->toBe([$a->id, $b->id, 'https://sitio.com/c.jpg']);

    $component->set('displayType', 'video')
        ->dispatch('media-picked', context: 'popup', items: [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']])
        ->call('save')
        ->assertHasNoErrors();

    $popup = Popup::first()->load('items');

    expect(Popup::count())->toBe(1)
        ->and($popup->display_type)->toBe('video')
        ->and($popup->items)->toHaveCount(1)
        ->and($popup->items->first()->youtube_id)->toBe('dQw4w9WgXcQ')
        ->and(PopupItem::count())->toBe(1);
});

test('guardar varias veces actualiza el mismo pop-up', function () {
    $component = Livewire::test(PopupManager::class)->set('title', 'Uno')->call('save')->assertHasNoErrors();
    $component->set('title', 'Dos')->call('save')->assertHasNoErrors();

    expect(Popup::count())->toBe(1)->and(Popup::first()->title)->toBe('Dos');
});

test('se guarda como borrador sin contenido cuando no esta visible', function () {
    Livewire::test(PopupManager::class)->set('isVisible', false)->call('save')->assertHasNoErrors();

    expect(Popup::first()->is_visible)->toBeFalse();
});

test('si esta visible exige el contenido segun el tipo', function (string $type, array $items, ?string $message) {
    $component = Livewire::test(PopupManager::class)->set('isVisible', true)->set('displayType', $type);

    foreach ($items as $item) {
        $component->dispatch('media-picked', context: 'popup', items: [value($item)]);
    }

    $component->call('save');

    if ($message === null) {
        $component->assertHasNoErrors();
        expect(Popup::count())->toBe(1);
    } else {
        $component->assertHasErrors(['items'])->assertSee($message);
        expect(Popup::count())->toBe(0);
    }
})->with([
    'imagen vacia' => ['image', [], 'Elige la imagen del pop-up'],
    'video vacio' => ['video', [], 'Elige el video del pop-up'],
    'slider vacio' => ['slider', [], 'al menos 2 imágenes'],
    'slider con una' => ['slider', [fn () => pickedFile(popupMedia('a.jpg'))], 'al menos 2 imágenes'],
    'slider con dos' => ['slider', [fn () => pickedFile(popupMedia('a.jpg')), fn () => pickedFile(popupMedia('b.jpg'))], null],
    'imagen con una' => ['image', [fn () => pickedFile(popupMedia('a.jpg'))], null],
]);

test('el encabezado necesita un titulo', function () {
    Livewire::test(PopupManager::class)
        ->set('showHeader', true)
        ->set('title', '   ')
        ->call('save')
        ->assertHasErrors(['title']);

    expect(Popup::count())->toBe(0);
});

test('valida los campos', function () {
    Livewire::test(PopupManager::class)
        ->set('title', str_repeat('a', 121))
        ->set('linkUrl', 'javascript:alert(1)')
        ->call('save')
        ->assertHasErrors(['title' => 'max', 'linkUrl' => 'regex']);

    foreach (['https://sitio.com/x', '/contacto', '#nosotros'] as $link) {
        Livewire::test(PopupManager::class)->set('linkUrl', $link)->call('save')->assertHasNoErrors();
    }
});

test('no confia en los datos manipulados desde el navegador', function (array $items, string $type) {
    Livewire::test(PopupManager::class)
        ->set('displayType', $type)
        ->set('items', array_map(fn ($item) => array_merge(['uid' => Str::random(8), 'id' => null, 'type' => 'file', 'media_file_id' => null, 'url' => null, 'youtube_id' => null], value($item)), $items))
        ->call('save')
        ->assertHasErrors();

    expect(Popup::count())->toBe(0);
})->with([
    'archivo inexistente' => [[['media_file_id' => 99999]], 'image'],
    'documento como imagen' => [[fn () => ['media_file_id' => popupMedia('doc.pdf')->id]], 'image'],
    'imagen como video' => [[fn () => ['media_file_id' => popupMedia('a.jpg')->id]], 'video'],
    'youtube en imagen' => [[['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']], 'image'],
    'dos imagenes en tipo imagen' => [[fn () => ['media_file_id' => popupMedia('a.jpg')->id], fn () => ['media_file_id' => popupMedia('b.jpg')->id]], 'image'],
    'url invalida' => [[['type' => 'image_url', 'url' => 'ftp://sitio.com/a.jpg']], 'image'],
    'youtube invalido' => [[['type' => 'youtube', 'youtube_id' => 'x']], 'video'],
    'tipo de item desconocido' => [[['type' => 'otro']], 'image'],
]);

test('no permite mas imagenes que el maximo', function () {
    $items = array_map(fn () => ['uid' => Str::random(8), 'id' => null, 'type' => 'image_url', 'media_file_id' => null, 'url' => 'https://sitio.com/a.jpg', 'youtube_id' => null], range(1, Popup::MAX_ITEMS + 1));

    Livewire::test(PopupManager::class)->set('displayType', 'slider')->set('items', $items)->call('save')->assertHasErrors(['items' => 'max']);
});

test('al quitar contenido y guardar se borra de la base de datos', function () {
    $popup = Popup::create(['display_type' => 'slider']);
    $popup->items()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg', 'position' => 0]);
    $popup->items()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/b.jpg', 'position' => 1]);

    $component = Livewire::test(PopupManager::class);
    $component->call('removeItem', $component->get('items.0.uid'))->call('save')->assertHasNoErrors();

    expect(PopupItem::count())->toBe(1)
        ->and(PopupItem::first()->external_url)->toBe('https://sitio.com/b.jpg');
});

test('eliminar un archivo del gestor lo quita del pop-up', function () {
    $file = popupMedia();
    $popup = Popup::create(['display_type' => 'image']);
    $popup->items()->create(['type' => 'file', 'media_file_id' => $file->id, 'position' => 0]);

    $file->delete();

    expect(PopupItem::count())->toBe(0);
});

// --- Vista previa -------------------------------------------------------------------

test('la vista previa refleja el encabezado, el margen y el estado', function () {
    Livewire::test(PopupManager::class)
        ->set('showHeader', true)
        ->set('title', 'Gran promoción')
        ->assertSee('Gran promoción')
        ->assertSee('Oculto (borrador)')
        ->set('isVisible', true)
        ->assertSee('Visible en el sitio')
        ->set('showHeader', false)
        ->assertDontSee('Gran promoción');
});

test('la vista previa muestra la imagen elegida y el enlace', function () {
    $file = popupMedia('promo.jpg');

    Livewire::test(PopupManager::class)
        ->set('linkUrl', 'https://sitio.com/promo')
        ->dispatch('media-picked', context: 'popup', items: [pickedFile($file)])
        ->assertSeeHtml(Storage::disk('public')->url($file->path))
        ->assertSee('Al hacer clic abrirá')
        ->assertSee('https://sitio.com/promo');
});

test('la vista previa de youtube usa la miniatura', function () {
    Livewire::test(PopupManager::class)
        ->set('displayType', 'video')
        ->dispatch('media-picked', context: 'popup', items: [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']])
        ->assertSeeHtml('https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
});

test('la vista previa del slider muestra todas las imagenes y controles', function () {
    Livewire::test(PopupManager::class)
        ->set('displayType', 'slider')
        ->dispatch('media-picked', context: 'popup', items: [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg'], ['type' => 'image_url', 'url' => 'https://sitio.com/b.jpg']])
        ->assertSeeHtml('https://sitio.com/a.jpg')
        ->assertSeeHtml('https://sitio.com/b.jpg')
        ->assertSee('Siguiente')
        ->assertSee('2 de '.Popup::MAX_ITEMS.' imágenes');
});
