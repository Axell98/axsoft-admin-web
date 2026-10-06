<?php

use App\Livewire\Admin\MediaPicker;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function pickerMedia(string $name, ?MediaFolder $folder = null): MediaFile
{
    $extension = pathinfo($name, PATHINFO_EXTENSION);

    return MediaFile::create([
        'media_folder_id' => $folder?->id,
        'name' => $name,
        'path' => "files/2026/10/{$name}",
        'extension' => $extension,
        'mime_type' => 'application/octet-stream',
        'size' => 100,
    ]);
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

test('esta cerrado hasta recibir el evento', function () {
    Livewire::test(MediaPicker::class)
        ->assertSet('open', false)
        ->assertDontSee('Seleccionar');
});

test('se abre con el contexto, el modo y las opciones recibidas', function () {
    pickerMedia('foto.jpg');

    Livewire::test(MediaPicker::class)
        ->dispatch('open-media-picker', context: 'phase', mode: 'image', multiple: true, external: ['image'], used: [5])
        ->assertSet('open', true)
        ->assertSet('context', 'phase')
        ->assertSet('mode', 'image')
        ->assertSet('multiple', true)
        ->assertSet('external', ['image'])
        ->assertSet('used', [5])
        ->assertSee('Seleccionar imagen')
        ->assertSee('foto.jpg');
});

test('ignora modos y tipos de enlace no permitidos', function () {
    Livewire::test(MediaPicker::class)
        ->call('openFor', 'x', 'documentos', false, ['image', 'ftp', 'youtube', 'html'])
        ->assertSet('mode', 'image')
        // Se compara con una función porque ['image', 'youtube'] es un «callable» válido para PHP (fachada Image).
        ->assertSet('external', fn (array $external) => $external === ['image', 'youtube']);
});

test('segun el modo solo lista imagenes, videos o ambos', function () {
    pickerMedia('foto.jpg');
    pickerMedia('video.mp4');
    pickerMedia('plano.pdf');

    $picker = Livewire::test(MediaPicker::class);

    $picker->call('openFor', 'x', 'image')
        ->assertSee('foto.jpg')->assertDontSee('video.mp4')->assertDontSee('plano.pdf');

    $picker->call('openFor', 'x', 'video')
        ->assertSee('video.mp4')->assertDontSee('foto.jpg')->assertDontSee('plano.pdf');

    $picker->call('openFor', 'x', 'all')
        ->assertSee('foto.jpg')->assertSee('video.mp4')->assertDontSee('plano.pdf')
        ->set('type', 'video')
        ->assertSee('video.mp4')->assertDontSee('foto.jpg');
});

test('filtra por nombre y por carpeta', function () {
    $folder = MediaFolder::create(['name' => 'Renders']);
    pickerMedia('fachada.jpg', $folder);
    pickerMedia('interior.jpg');

    Livewire::test(MediaPicker::class)
        ->call('openFor', 'x', 'image')
        ->set('search', 'fach')
        ->assertSee('fachada.jpg')->assertDontSee('interior.jpg')
        ->set('search', '')
        ->set('folder', (string) $folder->id)
        ->assertSee('fachada.jpg')->assertDontSee('interior.jpg');
});

test('pagina con cargar mas', function () {
    foreach (range(1, 30) as $i) {
        pickerMedia(sprintf('foto-%02d.jpg', $i));
    }

    $picker = Livewire::test(MediaPicker::class)->call('openFor', 'x', 'image');

    expect($picker->viewData('files'))->toHaveCount(24)
        ->and($picker->viewData('total'))->toBe(30);

    $picker->call('loadMore');

    expect($picker->viewData('files'))->toHaveCount(30);
});

test('con seleccion multiple permite marcar varios y desmarcar', function () {
    $a = pickerMedia('a.jpg');
    $b = pickerMedia('b.jpg');

    Livewire::test(MediaPicker::class)
        ->call('openFor', 'x', 'image', true)
        ->call('toggle', $a->id)
        ->call('toggle', $b->id)
        ->assertSet('selected', [$a->id, $b->id])
        ->call('toggle', $a->id)
        ->assertSet('selected', [$b->id]);
});

test('con seleccion simple solo queda uno marcado', function () {
    $a = pickerMedia('a.mp4');
    $b = pickerMedia('b.mp4');

    Livewire::test(MediaPicker::class)
        ->call('openFor', 'x', 'video', false)
        ->call('toggle', $a->id)
        ->call('toggle', $b->id)
        ->assertSet('selected', [$b->id])
        ->call('toggle', $b->id)
        ->assertSet('selected', []);
});

test('al confirmar emite lo elegido con el contexto y se cierra', function () {
    $a = pickerMedia('a.jpg');
    $b = pickerMedia('b.jpg');

    Livewire::test(MediaPicker::class)
        ->call('openFor', 'phase', 'image', true)
        ->call('toggle', $b->id)
        ->call('toggle', $a->id)
        ->call('confirm')
        ->assertSet('open', false)
        ->assertDispatched('media-picked', context: 'phase', items: [
            ['type' => 'file', 'media_file_id' => $b->id],
            ['type' => 'file', 'media_file_id' => $a->id],
        ]);
});

test('ignora archivos seleccionados que no corresponden al modo', function () {
    $pdf = pickerMedia('plano.pdf');

    Livewire::test(MediaPicker::class)
        ->call('openFor', 'phase', 'image', true)
        ->set('selected', [$pdf->id])
        ->call('confirm')
        ->assertNotDispatched('media-picked');
});

test('permite agregar una imagen por link', function () {
    Livewire::test(MediaPicker::class)
        ->call('openFor', 'phase', 'image', true, ['image'])
        ->set('externalKind', 'image')
        ->set('externalInput', 'https://sitio.com/render.jpg')
        ->call('addExternal')
        ->assertHasNoErrors()
        ->assertSet('open', false)
        ->assertDispatched('media-picked', context: 'phase', items: [['type' => 'image_url', 'url' => 'https://sitio.com/render.jpg']]);
});

test('rechaza links de imagen invalidos', function (string $value) {
    Livewire::test(MediaPicker::class)
        ->call('openFor', 'phase', 'image', true, ['image'])
        ->set('externalInput', $value)
        ->call('addExternal')
        ->assertHasErrors('externalInput')
        ->assertSet('open', true)
        ->assertNotDispatched('media-picked');
})->with(['vacio' => '', 'texto' => 'no es link', 'javascript' => 'javascript:alert(1)', 'data' => 'data:image/png;base64,AAAA']);

test('permite agregar un video de YouTube por url o iframe', function (string $input) {
    Livewire::test(MediaPicker::class)
        ->call('openFor', 'video', 'video', false, ['youtube'])
        ->set('externalKind', 'youtube')
        ->set('externalInput', $input)
        ->call('addExternal')
        ->assertHasNoErrors()
        ->assertDispatched('media-picked', context: 'video', items: [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']]);
})->with([
    'url' => 'https://youtu.be/dQw4w9WgXcQ',
    'iframe' => '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>',
]);

test('rechaza enlaces de YouTube invalidos', function () {
    Livewire::test(MediaPicker::class)
        ->call('openFor', 'video', 'video', false, ['youtube'])
        ->set('externalKind', 'youtube')
        ->set('externalInput', 'https://vimeo.com/123')
        ->call('addExternal')
        ->assertHasErrors('externalInput')
        ->assertNotDispatched('media-picked');
});

test('no acepta un tipo de enlace que el contexto no permite', function () {
    Livewire::test(MediaPicker::class)
        ->call('openFor', 'phase', 'image', true, ['image'])
        ->set('externalKind', 'youtube')
        ->set('externalInput', 'https://youtu.be/dQw4w9WgXcQ')
        ->call('addExternal')
        ->assertNotDispatched('media-picked');
});

test('al cerrar no emite nada', function () {
    Livewire::test(MediaPicker::class)
        ->call('openFor', 'phase', 'image', true)
        ->call('close')
        ->assertSet('open', false)
        ->assertNotDispatched('media-picked');
});
