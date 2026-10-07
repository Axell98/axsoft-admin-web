<?php

use App\Livewire\Admin\BannerManager;
use App\Models\BannerSlide;
use App\Models\BannerSlider;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function mediaFile(string $name = 'portada.jpg'): MediaFile
{
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    $path = 'files/2026/10/'.fake()->unique()->lexify('??????????').'.'.$extension;
    Storage::disk('public')->put($path, 'contenido');

    return MediaFile::create([
        'name' => $name,
        'path' => $path,
        'extension' => $extension,
        'mime_type' => 'application/octet-stream',
        'size' => 1024,
    ]);
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

// --- Acceso y valores iniciales -----------------------------------------------

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.banners'))->assertRedirect('/login');
});

test('la pagina carga con las opciones por defecto', function () {
    $this->get(route('admin.banners'))
        ->assertOk()
        ->assertSeeLivewire(BannerManager::class)
        ->assertSee('Aún no hay banners');

    $slider = BannerSlider::first();

    expect($slider->screen_percentage)->toBe(100)
        ->and($slider->show_arrows)->toBeTrue()
        ->and($slider->show_indicators)->toBeTrue();
});

// --- Opciones ---------------------------------------------------------------------

test('se guardan las opciones del slider', function () {
    Livewire::test(BannerManager::class)
        ->set('screenPercentage', '70')
        ->set('showArrows', false)
        ->set('showIndicators', false)
        ->assertSet('dirty', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('dirty', false)
        ->assertDispatched('notify');

    $slider = BannerSlider::first();

    expect($slider->screen_percentage)->toBe(70)
        ->and($slider->show_arrows)->toBeFalse()
        ->and($slider->show_indicators)->toBeFalse();
});

test('valida el porcentaje de pantalla', function (string $value) {
    Livewire::test(BannerManager::class)
        ->set('screenPercentage', $value)
        ->call('save')
        ->assertHasErrors('screenPercentage');

    expect(BannerSlider::first()->screen_percentage)->toBe(100);
})->with(['vacio' => '', 'cero' => '0', 'muy bajo' => '9', 'muy alto' => '101', 'decimal' => '50.5', 'texto' => 'abc']);

test('acepta los limites del porcentaje', function (string $value) {
    Livewire::test(BannerManager::class)
        ->set('screenPercentage', $value)
        ->call('save')
        ->assertHasNoErrors();

    expect(BannerSlider::first()->screen_percentage)->toBe((int) $value);
})->with(['10', '100']);

test('los cambios no se guardan hasta pulsar guardar', function () {
    Livewire::test(BannerManager::class)
        ->set('screenPercentage', '50')
        ->set('showArrows', false);

    $slider = BannerSlider::first();

    expect($slider->screen_percentage)->toBe(100)
        ->and($slider->show_arrows)->toBeTrue();
});

// --- Agregar desde archivos -------------------------------------------------------

test('se pueden agregar imagenes del gestor de archivos', function () {
    $image = mediaFile('fachada.jpg');
    $other = mediaFile('interior.png');

    Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->assertSet('showPicker', true)
        ->assertSee('fachada.jpg')
        ->assertSee('interior.png')
        ->call('togglePick', $image->id)
        ->call('togglePick', $other->id)
        ->call('addPicked')
        ->assertSet('showPicker', false)
        ->assertSet('dirty', true)
        ->call('save')
        ->assertHasNoErrors();

    $slides = BannerSlider::first()->slides;

    expect($slides)->toHaveCount(2)
        ->and($slides[0]->media_file_id)->toBe($image->id)
        ->and($slides[0]->kind())->toBe('image')
        ->and($slides[1]->media_file_id)->toBe($other->id)
        ->and($slides[1]->kind())->toBe('image')
        ->and($slides->pluck('position')->all())->toBe([0, 1]);
});

test('el selector solo muestra imagenes en el slider y videos en el tipo video', function () {
    mediaFile('plano.pdf');
    mediaFile('hoja.xlsx');
    mediaFile('foto.png');
    mediaFile('recorrido.mp4');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->assertSee('foto.png')
        ->assertDontSee('recorrido.mp4')
        ->assertDontSee('plano.pdf')
        ->assertDontSee('hoja.xlsx')
        ->set('displayType', 'video')
        ->call('openPicker')
        ->assertSee('recorrido.mp4')
        ->assertDontSee('foto.png')
        ->assertDontSee('plano.pdf');

    expect($component->get('externalKind'))->toBe('youtube');
});

test('el selector filtra por nombre', function () {
    mediaFile('fachada.jpg');
    mediaFile('interior.jpg');

    Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->set('pickerSearch', 'fach')
        ->assertSee('fachada.jpg')
        ->assertDontSee('interior.jpg');
});

test('el selector pagina con cargar mas', function () {
    foreach (range(1, 30) as $i) {
        mediaFile(sprintf('foto-%02d.jpg', $i));
    }

    $component = Livewire::test(BannerManager::class)->call('openPicker');

    expect($component->viewData('pickerFiles'))->toHaveCount(24)
        ->and($component->viewData('pickerTotal'))->toBe(30);

    $component->call('loadMore');

    expect($component->viewData('pickerFiles'))->toHaveCount(30);
});

test('no se pueden agregar archivos que no son imagen o video', function () {
    $pdf = mediaFile('plano.pdf');

    Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $pdf->id)
        ->call('addPicked')
        ->assertSet('slides', []);
});

test('un archivo manipulado que no es multimedia es rechazado al guardar', function () {
    $pdf = mediaFile('plano.pdf');

    Livewire::test(BannerManager::class)
        ->set('slides', [['uid' => 'x', 'id' => null, 'type' => 'file', 'media_file_id' => $pdf->id, 'url' => null, 'youtube_id' => null, 'link_url' => null]])
        ->call('save')
        ->assertHasErrors('slides');

    expect(BannerSlide::count())->toBe(0);
});

// --- Agregar por link ---------------------------------------------------------------

test('se puede agregar una imagen por link', function () {
    Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->set('showExternalForm', true)
        ->set('externalKind', 'image')
        ->set('externalInput', 'https://sitio.com/banner.jpg')
        ->call('addExternal')
        ->assertHasNoErrors()
        ->assertSet('showPicker', false)
        ->call('save');

    $slide = BannerSlide::first();

    expect($slide->type)->toBe('image_url')
        ->and($slide->external_url)->toBe('https://sitio.com/banner.jpg')
        ->and($slide->sourceUrl())->toBe('https://sitio.com/banner.jpg')
        ->and($slide->kind())->toBe('image');
});

test('rechaza links de imagen invalidos', function (string $value) {
    Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->set('externalKind', 'image')
        ->set('externalInput', $value)
        ->call('addExternal')
        ->assertHasErrors('externalInput')
        ->assertSet('slides', []);
})->with(['vacio' => '', 'texto' => 'no es un link', 'javascript' => 'javascript:alert(1)', 'data' => 'data:image/png;base64,AAAA', 'ftp' => 'ftp://sitio.com/a.jpg']);

test('se puede agregar un video de YouTube por url o por iframe', function (string $input) {
    Livewire::test(BannerManager::class)
        ->set('displayType', 'video')
        ->call('openPicker')
        ->set('externalInput', $input)
        ->call('addExternal')
        ->assertHasNoErrors()
        ->call('save');

    $slide = BannerSlide::first();

    expect($slide->type)->toBe('youtube')
        ->and($slide->youtube_id)->toBe('dQw4w9WgXcQ')
        ->and($slide->external_url)->toBeNull()
        ->and($slide->kind())->toBe('youtube')
        ->and($slide->embedUrl())->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
})->with([
    'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'iframe' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" allowfullscreen></iframe>',
]);

test('rechaza enlaces de YouTube invalidos y no guarda html del usuario', function () {
    Livewire::test(BannerManager::class)
        ->set('displayType', 'video')
        ->call('openPicker')
        ->set('externalInput', '<iframe src="https://evil.com/x"></iframe><script>alert(1)</script>')
        ->call('addExternal')
        ->assertHasErrors('externalInput')
        ->assertSet('slides', []);
});

// --- Gestión de banners -------------------------------------------------------------

test('se puede reordenar los banners', function () {
    $a = mediaFile('a.jpg');
    $b = mediaFile('b.jpg');
    $c = mediaFile('c.jpg');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $a->id)
        ->call('togglePick', $b->id)
        ->call('togglePick', $c->id)
        ->call('addPicked');

    $uids = array_column($component->get('slides'), 'uid');

    $component
        ->call('moveSlide', $uids[2], 'left')   // a, c, b
        ->call('moveSlide', $uids[0], 'right')  // c, a, b
        ->call('save');

    expect(BannerSlider::first()->slides->pluck('media_file_id')->all())->toBe([$c->id, $a->id, $b->id]);
});

test('mover mas alla de los extremos no hace nada', function () {
    $a = mediaFile('a.jpg');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $a->id)
        ->call('addPicked');

    $uid = $component->get('slides')[0]['uid'];

    $component->call('moveSlide', $uid, 'left')->call('moveSlide', $uid, 'right');

    expect($component->get('slides'))->toHaveCount(1);
});

test('se puede eliminar un banner y se guarda al pulsar guardar', function () {
    $a = mediaFile('a.jpg');
    $b = mediaFile('b.jpg');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $a->id)
        ->call('togglePick', $b->id)
        ->call('addPicked')
        ->call('save');

    $uid = $component->get('slides')[0]['uid'];

    $component->call('removeSlide', $uid);
    expect(BannerSlide::count())->toBe(2);

    $component->call('save');

    expect(BannerSlide::pluck('media_file_id')->all())->toBe([$b->id]);
    Storage::disk('public')->assertExists($a->path);
});

test('al guardar de nuevo se actualizan los banners existentes sin duplicarlos', function () {
    $a = mediaFile('a.jpg');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $a->id)
        ->call('addPicked')
        ->call('save')
        ->call('save');

    expect(BannerSlide::count())->toBe(1);
});

test('no se pueden superar los 20 banners', function () {
    $files = collect(range(1, 21))->map(fn ($i) => mediaFile("f{$i}.jpg"));

    $component = Livewire::test(BannerManager::class)->call('openPicker');

    foreach ($files as $file) {
        $component->call('togglePick', $file->id);
    }

    $component->call('addPicked');

    expect($component->get('slides'))->toHaveCount(20);
});

test('eliminar un archivo del gestor quita su banner', function () {
    $file = mediaFile('a.jpg');

    Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $file->id)
        ->call('addPicked')
        ->call('save');

    $file->delete();

    expect(BannerSlide::count())->toBe(0);
});

// --- Enlaces ------------------------------------------------------------------------

test('se puede asignar y quitar el enlace de un banner', function () {
    $a = mediaFile('a.jpg');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $a->id)
        ->call('addPicked');

    $uid = $component->get('slides')[0]['uid'];

    $component
        ->call('openLinkModal', $uid)
        ->assertSet('editingLinkUid', $uid)
        ->set('linkUrl', 'https://miempresa.com/proyectos')
        ->call('saveLink')
        ->assertHasNoErrors()
        ->assertSet('editingLinkUid', null)
        ->call('save');

    expect(BannerSlide::first()->link_url)->toBe('https://miempresa.com/proyectos');

    $component->call('openLinkModal', $component->get('slides')[0]['uid'])
        ->assertSet('linkUrl', 'https://miempresa.com/proyectos')
        ->set('linkUrl', '')
        ->call('saveLink')
        ->call('save');

    expect(BannerSlide::first()->link_url)->toBeNull();
});

test('acepta enlaces internos y anclas', function (string $link) {
    $a = mediaFile('a.jpg');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $a->id)
        ->call('addPicked');

    $component
        ->call('openLinkModal', $component->get('slides')[0]['uid'])
        ->set('linkUrl', $link)
        ->call('saveLink')
        ->assertHasNoErrors();
})->with(['/contacto', '#nosotros', 'http://sitio.com', 'https://sitio.com/a?b=1']);

test('rechaza enlaces peligrosos o mal formados', function (string $link) {
    $a = mediaFile('a.jpg');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $a->id)
        ->call('addPicked');

    $component
        ->call('openLinkModal', $component->get('slides')[0]['uid'])
        ->set('linkUrl', $link)
        ->call('saveLink')
        ->assertHasErrors('linkUrl');
})->with(['javascript' => 'javascript:alert(1)', 'data' => 'data:text/html,<script>', 'sin esquema' => 'sitio.com', 'con espacios' => 'https://sitio.com/a b']);

test('un enlace peligroso manipulado en el estado es rechazado al guardar', function () {
    $a = mediaFile('a.jpg');

    Livewire::test(BannerManager::class)
        ->set('slides', [['uid' => 'x', 'id' => null, 'type' => 'file', 'media_file_id' => $a->id, 'url' => null, 'youtube_id' => null, 'link_url' => 'javascript:alert(1)']])
        ->call('save')
        ->assertHasErrors('slides.0.link_url');

    expect(BannerSlide::count())->toBe(0);
});

test('no se pueden modificar banners de otro slider con un id manipulado', function () {
    $a = mediaFile('a.jpg');
    BannerSlider::main();
    $other = BannerSlider::create(['name' => 'Otro']);
    $foreign = $other->slides()->create(['type' => 'file', 'media_file_id' => $a->id, 'position' => 0]);

    Livewire::test(BannerManager::class)
        ->set('slides', [['uid' => 'x', 'id' => $foreign->id, 'type' => 'file', 'media_file_id' => $a->id, 'url' => null, 'youtube_id' => null, 'link_url' => 'https://hack.com']])
        ->call('save');

    expect($foreign->refresh()->link_url)->toBeNull();
});

// --- Texto y descripción ------------------------------------------------------------

function bannerWithSlide(): array
{
    $file = mediaFile('a.jpg');

    $component = Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $file->id)
        ->call('addPicked');

    return [$component, $component->get('slides')[0]['uid']];
}

test('se puede agregar texto y descripcion a un banner', function () {
    [$component, $uid] = bannerWithSlide();

    $component
        ->call('openTextModal', $uid)
        ->assertSet('editingTextUid', $uid)
        ->set('textTitle', 'Diseñamos espacios')
        ->set('textDescription', 'Arquitectura a tu medida.')
        ->call('saveText')
        ->assertHasNoErrors()
        ->assertSet('editingTextUid', null)
        ->assertSet('dirty', true)
        ->call('save');

    $slide = BannerSlide::first();

    expect($slide->title)->toBe('Diseñamos espacios')
        ->and($slide->description)->toBe('Arquitectura a tu medida.');
});

test('el modal de texto carga los valores guardados y permite vaciarlos', function () {
    [$component, $uid] = bannerWithSlide();

    $component->call('openTextModal', $uid)->set('textTitle', 'Hola')->set('textDescription', 'Mundo')->call('saveText')->call('save');

    $uid = $component->get('slides')[0]['uid'];

    $component
        ->call('openTextModal', $uid)
        ->assertSet('textTitle', 'Hola')
        ->assertSet('textDescription', 'Mundo')
        ->set('textTitle', '  ')
        ->set('textDescription', '')
        ->call('saveText')
        ->call('save');

    $slide = BannerSlide::first();

    expect($slide->title)->toBeNull()
        ->and($slide->description)->toBeNull();
});

test('valida la longitud del texto y la descripcion', function () {
    [$component, $uid] = bannerWithSlide();

    $component
        ->call('openTextModal', $uid)
        ->set('textTitle', str_repeat('a', 121))
        ->set('textDescription', str_repeat('a', 501))
        ->call('saveText')
        ->assertHasErrors(['textTitle' => 'max', 'textDescription' => 'max'])
        ->assertSet('editingTextUid', $uid);

    $component
        ->set('textTitle', str_repeat('a', 120))
        ->set('textDescription', str_repeat('a', 500))
        ->call('saveText')
        ->assertHasNoErrors();
});

test('sin indicadores activos no se puede editar el texto', function () {
    [$component, $uid] = bannerWithSlide();

    $component
        ->set('showIndicators', false)
        ->call('openTextModal', $uid)
        ->assertSet('editingTextUid', null);
});

test('si se desactivan los indicadores con el modal abierto, el texto no se guarda', function () {
    [$component, $uid] = bannerWithSlide();

    $component
        ->call('openTextModal', $uid)
        ->set('textTitle', 'No debería guardarse')
        ->set('showIndicators', false)
        ->call('saveText')
        ->call('save');

    expect(BannerSlide::first()->title)->toBeNull();
});

test('los textos se conservan al desactivar y volver a activar los indicadores', function () {
    [$component, $uid] = bannerWithSlide();

    $component->call('openTextModal', $uid)->set('textTitle', 'Conservado')->call('saveText')->call('save');

    $component->set('showIndicators', false)->call('save');
    expect(BannerSlide::first()->title)->toBe('Conservado');

    $component->set('showIndicators', true)->call('save');
    expect(BannerSlide::first()->title)->toBe('Conservado');
});

test('el texto se muestra en la tarjeta solo con indicadores activos', function () {
    [$component, $uid] = bannerWithSlide();

    $component->call('openTextModal', $uid)->set('textTitle', 'Texto visible')->call('saveText')
        ->assertSee('Texto visible')
        ->set('showIndicators', false)
        ->assertDontSee('Texto visible');
});

test('un texto demasiado largo manipulado en el estado es rechazado al guardar', function () {
    $a = mediaFile('a.jpg');

    Livewire::test(BannerManager::class)
        ->set('slides', [['uid' => 'x', 'id' => null, 'type' => 'file', 'media_file_id' => $a->id, 'url' => null, 'youtube_id' => null, 'title' => str_repeat('a', 121), 'description' => null, 'link_url' => null]])
        ->call('save')
        ->assertHasErrors('slides.0.title');

    expect(BannerSlide::count())->toBe(0);
});

// --- Tipo de banner: slider o video -----------------------------------------------------

/**
 * @return array<string, mixed>
 */
function bannerSlide(array $attributes = []): array
{
    return array_merge(['uid' => Str::random(8), 'id' => null, 'type' => 'file', 'media_file_id' => null, 'url' => null, 'youtube_id' => null, 'title' => null, 'description' => null, 'link_url' => null], $attributes);
}

test('por defecto es un slider de imagenes y se guarda el tipo', function () {
    $component = Livewire::test(BannerManager::class)->assertSet('displayType', 'slider');

    $component->set('displayType', 'video')->call('save')->assertHasNoErrors();

    expect(BannerSlider::first()->display_type)->toBe('video');

    Livewire::test(BannerManager::class)->assertSet('displayType', 'video');
});

test('un tipo invalido vuelve a slider', function () {
    Livewire::test(BannerManager::class)->set('displayType', 'hackeado')->assertSet('displayType', 'slider');
});

test('la pagina cambia sus textos segun el tipo', function () {
    Livewire::test(BannerManager::class)
        ->assertSee('Slider de imágenes')
        ->assertSee('Mostrar flechas')
        ->assertSee('Mostrar indicadores')
        ->assertSee('Buscar imágenes')
        ->set('displayType', 'video')
        ->assertSee('Buscar video')
        ->assertSee('Aún no hay un video')
        ->assertDontSee('Mostrar flechas')
        ->assertDontSee('Mostrar indicadores');
});

test('en el tipo video se elige un solo video del gestor y el nuevo reemplaza al anterior', function () {
    $first = mediaFile('uno.mp4');
    $second = mediaFile('dos.webm');

    Livewire::test(BannerManager::class)
        ->set('displayType', 'video')
        ->call('openPicker')
        ->call('togglePick', $first->id)
        ->call('togglePick', $second->id)
        ->assertSet('pickerSelected', [$second->id])
        ->call('addPicked')
        ->assertCount('slides', 1)
        ->call('openPicker')
        ->call('togglePick', $first->id)
        ->call('addPicked')
        ->assertCount('slides', 1)
        ->assertSet('slides.0.media_file_id', $first->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(BannerSlide::count())->toBe(1)->and(BannerSlide::first()->media_file_id)->toBe($first->id);
});

test('en el tipo video un video de YouTube reemplaza al archivo', function () {
    $video = mediaFile('uno.mp4');

    Livewire::test(BannerManager::class)
        ->set('displayType', 'video')
        ->call('openPicker')
        ->call('togglePick', $video->id)
        ->call('addPicked')
        ->call('openPicker')
        ->set('externalInput', 'https://youtu.be/dQw4w9WgXcQ')
        ->call('addExternal')
        ->assertHasNoErrors()
        ->assertCount('slides', 1)
        ->assertSet('slides.0.type', 'youtube');
});

test('el slider no acepta videos ni youtube y el tipo video no acepta imagenes ni links de imagen', function () {
    $video = mediaFile('uno.mp4');
    $image = mediaFile('foto.jpg');

    Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $video->id)
        ->call('addPicked')
        ->assertSet('slides', [])
        ->set('externalKind', 'youtube')
        ->set('externalInput', 'https://youtu.be/dQw4w9WgXcQ')
        ->call('addExternal')
        ->assertSet('externalKind', 'image')
        ->assertSet('slides.0.type', 'image_url');

    Livewire::test(BannerManager::class)
        ->set('displayType', 'video')
        ->call('openPicker')
        ->call('togglePick', $image->id)
        ->call('addPicked')
        ->assertSet('slides', [])
        ->set('externalKind', 'image')
        ->set('externalInput', 'https://sitio.com/a.jpg')
        ->call('addExternal')
        ->assertHasErrors('externalInput')
        ->assertSet('slides', []);
});

test('al pasar a video se quitan las imagenes y al pasar a slider se quita el video', function () {
    $image = mediaFile('foto.jpg');
    $video = mediaFile('uno.mp4');

    Livewire::test(BannerManager::class)
        ->call('openPicker')
        ->call('togglePick', $image->id)
        ->call('addPicked')
        ->assertCount('slides', 1)
        ->set('displayType', 'video')
        ->assertCount('slides', 0)
        ->assertSet('dirty', true)
        ->assertDispatched('notify')
        ->call('openPicker')
        ->call('togglePick', $video->id)
        ->call('addPicked')
        ->assertCount('slides', 1)
        ->set('displayType', 'slider')
        ->assertCount('slides', 0);
});

test('un slider con contenido de video guardado se muestra marcado y no deja guardar', function () {
    $image = mediaFile('foto.jpg');
    $slider = BannerSlider::create(['name' => 'Principal', 'screen_percentage' => 100, 'show_arrows' => true, 'show_indicators' => true]);
    $slider->slides()->create(['type' => 'file', 'media_file_id' => $image->id, 'position' => 0]);
    $slider->slides()->create(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ', 'position' => 1]);

    Livewire::test(BannerManager::class)
        ->assertCount('slides', 2)
        ->assertSee('Solo imágenes')
        ->call('save')
        ->assertHasErrors('slides')
        ->assertSee('Un slider solo puede tener imágenes');

    expect(BannerSlide::count())->toBe(2);
});

test('el tipo video no se puede manipular para guardar varios videos o imagenes', function () {
    $a = mediaFile('a.mp4');
    $b = mediaFile('b.mp4');
    $image = mediaFile('foto.jpg');

    Livewire::test(BannerManager::class)
        ->set('displayType', 'video')
        ->set('slides', [bannerSlide(['media_file_id' => $a->id]), bannerSlide(['media_file_id' => $b->id])])
        ->call('save')
        ->assertHasErrors('slides');

    Livewire::test(BannerManager::class)
        ->set('displayType', 'video')
        ->set('slides', [bannerSlide(['media_file_id' => $image->id])])
        ->call('save')
        ->assertHasErrors('slides');

    Livewire::test(BannerManager::class)
        ->set('displayType', 'slider')
        ->set('slides', [bannerSlide(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ'])])
        ->call('save')
        ->assertHasErrors('slides');

    expect(BannerSlide::count())->toBe(0);
});

test('el tipo video no permite editar el texto de un banner', function () {
    $video = mediaFile('a.mp4');

    $component = Livewire::test(BannerManager::class)
        ->set('displayType', 'video')
        ->set('showIndicators', true)
        ->call('openPicker')
        ->call('togglePick', $video->id)
        ->call('addPicked');

    $uid = $component->get('slides.0.uid');

    $component->call('openTextModal', $uid)->assertSet('editingTextUid', null);
});
