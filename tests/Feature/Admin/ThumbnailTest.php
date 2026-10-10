<?php

use App\Livewire\Admin\BannerManager;
use App\Livewire\Admin\FileManager;
use App\Livewire\Admin\MediaPicker;
use App\Models\BannerSlider;
use App\Models\MediaFile;
use App\Models\User;
use App\Support\Thumbnail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Crea un archivo guardado en disco y en base de datos. Con $width se guarda una
 * imagen real de ese tamaño; sin él, un archivo con contenido cualquiera.
 */
function thumbFile(string $name = 'foto.jpg', ?int $width = 1200, int $height = 800): MediaFile
{
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    $path = 'files/2026/10/'.fake()->unique()->lexify('??????????').'.'.$extension;

    $content = $width !== null
        ? file_get_contents(UploadedFile::fake()->image($name, $width, $height)->getPathname())
        : 'contenido';

    Storage::disk('public')->put($path, $content);

    return MediaFile::create([
        'name' => $name,
        'path' => $path,
        'extension' => $extension,
        'mime_type' => 'application/octet-stream',
        'size' => strlen((string) $content),
    ]);
}

/**
 * Ancho y alto de una imagen guardada en el disco público.
 *
 * @return array{0: int, 1: int}
 */
function thumbSize(string $path): array
{
    [$width, $height] = getimagesize(Storage::disk('public')->path($path));

    return [$width, $height];
}

beforeEach(function () {
    if (! function_exists('imagecreatetruecolor')) {
        $this->markTestSkipped('Las miniaturas necesitan la extensión GD de PHP.');
    }

    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

// --- Generar ---------------------------------------------------------------------------

test('genera una miniatura reducida que conserva la proporcion', function () {
    $file = thumbFile('foto.jpg', 1200, 800);

    $path = Thumbnail::generate($file);

    expect($path)->toMatch('#^thumbs/2026/10/[a-z]+\.(webp|jpg)$#')
        ->and($path)->toBe(Thumbnail::path($file))
        ->and(pathinfo((string) $path, PATHINFO_FILENAME))->toBe(pathinfo($file->path, PATHINFO_FILENAME));

    Storage::disk('public')->assertExists($path);
    Storage::disk('public')->assertExists($file->path);

    expect(thumbSize($path))->toBe([Thumbnail::MAX_SIDE, 320])
        ->and(Storage::disk('public')->size($path))->toBeLessThan(Storage::disk('public')->size($file->path));
});

test('el lado mayor manda tambien en imagenes verticales', function () {
    $path = Thumbnail::generate(thumbFile('vertical.png', 600, 1200));

    expect(thumbSize($path))->toBe([240, Thumbnail::MAX_SIDE]);
});

test('no agranda las imagenes pequeñas', function () {
    $path = Thumbnail::generate(thumbFile('icono.png', 100, 50));

    expect(thumbSize($path))->toBe([100, 50]);
});

test('no genera miniatura de lo que no es una imagen valida', function (string $name, ?int $width) {
    $file = thumbFile($name, $width);

    expect(Thumbnail::generate($file))->toBeNull()
        ->and(Thumbnail::ensure($file))->toBeNull();

    expect(Storage::disk('public')->allFiles('thumbs'))->toBe([]);
})->with([
    'documento' => ['plano.pdf', null],
    'video' => ['clip.mp4', null],
    'imagen dañada' => ['rota.jpg', null],
]);

test('no falla si el archivo original ya no esta en el disco', function () {
    $file = thumbFile();
    Storage::disk('public')->delete($file->path);

    expect(Thumbnail::generate($file))->toBeNull();
});

test('ensure crea la miniatura una sola vez', function () {
    $file = thumbFile();

    $path = Thumbnail::ensure($file);
    $modified = Storage::disk('public')->lastModified($path);

    expect(Thumbnail::ensure($file))->toBe($path)
        ->and(Storage::disk('public')->lastModified($path))->toBe($modified)
        ->and(Storage::disk('public')->allFiles('thumbs'))->toHaveCount(1);
});

// --- URL -------------------------------------------------------------------------------

test('la url apunta a la ruta que crea la miniatura hasta que existe', function () {
    $file = thumbFile();

    expect($file->thumbnailUrl())->toBe(route('admin.files.thumbnail', $file));

    $path = Thumbnail::generate($file);

    expect($file->thumbnailUrl())->toBe(Storage::disk('public')->url($path));
});

test('lo que no es una imagen usa la url del original', function () {
    $file = thumbFile('clip.mp4', null);

    expect($file->thumbnailUrl())->toBe($file->url());
});

// --- Ruta ------------------------------------------------------------------------------

test('la ruta crea la miniatura y redirige a ella', function () {
    $file = thumbFile();

    $this->get(route('admin.files.thumbnail', $file))
        ->assertRedirect(Storage::disk('public')->url(Thumbnail::path($file)));

    Storage::disk('public')->assertExists(Thumbnail::path($file));
});

test('la ruta redirige al original si no se puede crear la miniatura', function (string $name) {
    $file = thumbFile($name, null);

    $this->get(route('admin.files.thumbnail', $file))->assertRedirect($file->url());

    expect(Storage::disk('public')->allFiles('thumbs'))->toBe([]);
})->with(['rota.jpg', 'plano.pdf']);

test('la ruta requiere autenticacion y un archivo existente', function () {
    $file = thumbFile();

    $this->get(route('admin.files.thumbnail', 99999))->assertNotFound();

    auth()->logout();

    $this->get(route('admin.files.thumbnail', $file))->assertRedirect('/login');

    expect(Storage::disk('public')->allFiles('thumbs'))->toBe([]);
});

// --- Subir y eliminar ------------------------------------------------------------------

test('al subir una imagen se crea su miniatura', function () {
    Livewire::test(FileManager::class)
        ->set('upload', UploadedFile::fake()->image('foto.jpg', 1000, 500))
        ->call('storeUpload')
        ->assertReturned(['ok' => true]);

    $file = MediaFile::firstOrFail();

    Storage::disk('public')->assertExists($file->path);
    Storage::disk('public')->assertExists(Thumbnail::path($file));

    expect(thumbSize(Thumbnail::path($file)))->toBe([Thumbnail::MAX_SIDE, 240]);
});

test('subir un documento o una imagen dañada no crea miniatura ni falla', function (string $name) {
    Livewire::test(FileManager::class)
        ->set('upload', UploadedFile::fake()->create($name, 50))
        ->call('storeUpload')
        ->assertReturned(['ok' => true]);

    expect(MediaFile::count())->toBe(1)
        ->and(Storage::disk('public')->allFiles('thumbs'))->toBe([]);
})->with(['plano.pdf', 'rota.png']);

test('al eliminar el archivo se elimina su miniatura', function () {
    $file = thumbFile();
    $other = thumbFile('otra.jpg');
    $path = Thumbnail::generate($file);
    $otherPath = Thumbnail::generate($other);

    $file->delete();

    Storage::disk('public')->assertMissing($path);
    Storage::disk('public')->assertMissing($file->path);
    Storage::disk('public')->assertExists($otherPath);
});

// --- Vistas ----------------------------------------------------------------------------

test('el selector de archivos muestra la miniatura y guarda el original como respaldo', function () {
    $ready = thumbFile('lista.jpg');
    $pending = thumbFile('pendiente.jpg');
    $path = Thumbnail::generate($ready);

    Livewire::test(MediaPicker::class)
        ->call('openFor', 'prueba')
        ->assertSee('src="'.Storage::disk('public')->url($path).'"', false)
        ->assertSee('data-original="'.$ready->url().'"', false)
        ->assertSee('src="'.route('admin.files.thumbnail', $pending).'"', false)
        ->assertSee('data-original="'.$pending->url().'"', false);
});

test('el banner muestra miniaturas en el selector y en los banners agregados', function () {
    $file = thumbFile('portada.jpg');
    $path = Thumbnail::generate($file);
    $thumb = 'src="'.Storage::disk('public')->url($path).'"';

    BannerSlider::main()->slides()->create(['type' => 'file', 'media_file_id' => $file->id, 'position' => 0]);

    Livewire::test(BannerManager::class)
        ->assertSee($thumb, false)
        ->assertSee('data-original="'.$file->url().'"', false)
        ->assertDontSee('src="'.$file->url().'"', false)
        ->call('openPicker')
        ->assertSee($thumb, false);
});
